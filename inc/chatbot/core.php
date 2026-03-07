<?php
declare(strict_types=1);

function isStreamingRequest(): bool
{
    return (string) ($_GET["stream"] ?? "") === "1";
}

function handleStreamingRequest(
    string $message,
    string $apiBase,
    string $apiKey,
    string $model,
    string $fallbackModel,
    string $systemPrompt,
    int $maxHistory,
    int $requestTimeout
): void {
    sendStreamHeaders();

    if ($message === "") {
        emitStreamEvent("error", ["message" => "メッセージを入力してください。"]);
        emitStreamEvent("done", ["ok" => false, "model" => $model]);
        return;
    }

    $pendingMessages = $_SESSION["chat_messages"];
    $pendingMessages[] = ["role" => "user", "content" => $message];
    $assistantOverride = buildAssistantOverride($message);
    if ($assistantOverride !== null) {
        storeCompletedExchange($pendingMessages, $assistantOverride, PUBLIC_MODEL_NAME);
        emitStreamEvent("content", ["delta" => (string) $assistantOverride["content"]]);
        emitStreamEvent(
            "done",
            [
                "ok" => true,
                "model" => PUBLIC_MODEL_NAME,
                "reasoning" => "",
                "content" => (string) $assistantOverride["content"],
            ]
        );
        return;
    }

    $requestMessages = buildChatRequestMessages($pendingMessages, $systemPrompt, $maxHistory);
    emitStreamEvent("meta", ["model" => $model]);

    try {
        [$assistantMessage, $activeModel] = streamAssistantReplyWithFallback(
            $apiBase,
            $apiKey,
            $model,
            $fallbackModel,
            $requestMessages,
            $requestTimeout,
            static function (string $event, string $delta) use ($model): void {
                if ($delta === "") {
                    return;
                }

                emitStreamEvent($event, ["delta" => $delta, "model" => $model]);
            }
        );

        storeCompletedExchange($pendingMessages, $assistantMessage, $activeModel);
        emitStreamEvent(
            "done",
            [
                "ok" => true,
                "model" => $activeModel,
                "reasoning" => (string) ($assistantMessage["reasoning"] ?? ""),
                "content" => (string) ($assistantMessage["content"] ?? ""),
            ]
        );
    } catch (RuntimeException $exception) {
        emitStreamEvent("error", ["message" => $exception->getMessage()]);
        emitStreamEvent("done", ["ok" => false, "model" => $model]);
    }
}

function sendStreamHeaders(): void
{
    ignore_user_abort(true);
    set_time_limit(0);
    header("Content-Type: text/event-stream; charset=UTF-8");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("X-Accel-Buffering: no");

    while (ob_get_level() > 0) {
        ob_end_flush();
    }

    echo ":" . str_repeat(" ", 2048) . "\n\n";
    flushStream();
}

function emitStreamEvent(string $event, array $payload): void
{
    echo "event: {$event}\n";
    echo "data: " . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    flushStream();
}

function flushStream(): void
{
    @ob_flush();
    flush();
}

function loadChatbotEnv(): void
{
    $configuredPath = trim((string) (getenv("CHATBOT_ENV_FILE") ?: ""));
    $candidateFiles = [];
    if ($configuredPath !== "") {
        $candidateFiles[] = $configuredPath;
    }

    $themeRoot = chatbotThemeRoot();
    $candidateFiles[] = dirname($themeRoot, 4) . "/.oishi-ai-chatbot.env";
    $candidateFiles[] = $themeRoot . "/.env";
    $candidateFiles[] = $themeRoot . "/.env.local";

    foreach ($candidateFiles as $filePath) {
        if (!is_string($filePath) || $filePath === "" || !is_readable($filePath)) {
            continue;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            continue;
        }

        foreach ($lines as $line) {
            if (!is_string($line)) {
                continue;
            }

            $trimmed = trim($line);
            if ($trimmed === "" || str_starts_with($trimmed, "#")) {
                continue;
            }

            $separator = strpos($trimmed, "=");
            if ($separator === false) {
                continue;
            }

            $name = trim(substr($trimmed, 0, $separator));
            if ($name === "" || getenv($name) !== false) {
                continue;
            }

            $value = trim(substr($trimmed, $separator + 1));
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            putenv($name . "=" . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function chatbotThemeRoot(): string
{
    return dirname(__DIR__, 2);
}

function readChatbotConfig(string $name, string $default = ""): string
{
    $value = getenv($name);
    if ($value === false) {
        return $default;
    }

    $trimmed = trim((string) $value);
    return $trimmed === "" ? $default : $trimmed;
}

function buildThemeAssetUrl(string $fileName): string
{
    $normalizedPath = ltrim(str_replace("\\", "/", $fileName), "/");
    $fullPath = chatbotThemeRoot() . "/" . $normalizedPath;
    $version = file_exists($fullPath) ? (string) filemtime($fullPath) : "1";
    $encodedPath = implode("/", array_map("rawurlencode", explode("/", $normalizedPath)));

    return "/wp-content/themes/oishi-ai/" . $encodedPath . "?v=" . rawurlencode($version);
}

function buildAssistantOverride(string $message): ?array
{
    $normalized = normalizeIntentText($message);
    if ($normalized === "") {
        return null;
    }

    $homepageIntent = resolveHomepageIntent($normalized);
    if ($homepageIntent !== null) {
        return buildHomepageIntentReply($homepageIntent);
    }

    $isModelQuestion = false;
    $explicitModelPatterns = [
        "/モデル名/u",
        "/何のモデル/u",
        "/なんのモデル/u",
        "/どんなモデル/u",
        "/どのモデル/u",
        "/ベースモデル/u",
        "/基盤モデル/u",
        "/内部モデル/u",
        "/裏側のモデル/u",
        "/(利用|使用|搭載)モデル/u",
        "/モデル(は|って)?(何|なに|どれ|どの|教えて|知りたい)/u",
        "/^(この|ここの|利用|使用|搭載|採用|ベース|基盤|内部|裏側)?(ai|AI|ＡＩ|モデル|llm|LLM)(は|って)?[？?]$/u",
        "/(何|なに|なん|どれ|どの|どんな).*(ai|AI|ＡＩ|モデル|llm|LLM|頭脳|エンジン)/u",
        "/(使ってる|使っている|利用している|使用している|搭載している).*(ai|AI|ＡＩ|モデル|llm|LLM)/u",
        "/(採用している|採用してる).*(ai|AI|ＡＩ|モデル|llm|LLM)/u",
        "/(ai|AI|ＡＩ).*(使ってる|使っている|利用している|使用している|搭載している|モデル|llm|LLM|名前|名称|ベース|基盤|内部|裏側)/u",
        "/(チャット|チャットボット|bot|ＢＯＴ|アシスタント|システム).*(ai|AI|ＡＩ|モデル|llm|LLM|頭脳|エンジン)/u",
        "/(ai|AI|ＡＩ|モデル|llm|LLM|頭脳|エンジン).*(名前|名称|使|利用|使用|搭載|採用|ベース|基盤|中身|内部|裏側|動いてる|動作)/u",
        "/\bllm\b/i",
        "/^(誰|だれ|何者|なにもの|自己紹介|名前は|お名前は|中身は)[？?]?$/u",
        "/^(あなた|きみ|君|おまえ|お前)(は|って)?(誰|だれ|何者|なにもの|正体|何|なに|なん)[？?]?$/u",
        "/(あなた|きみ|君|おまえ|お前|チャット|チャットボット|bot|ＢＯＴ|アシスタント|システム).*(名前|名称|呼び名|自己紹介|正体)/u",
        "/(chatgpt|ChatGPT|GPT|gpt|Gemini|gemini|Claude|claude|Llama|llama|ラマ|Copilot|copilot|Bard|bard)(です|なの|ですか|なんですか|使って|ベース|搭載|[？?])/ui",
        "/(chatgpt|ChatGPT|GPT|gpt|Gemini|gemini|Claude|claude|Llama|llama|ラマ|Copilot|copilot|Bard|bard)$/ui",
    ];

    foreach ($explicitModelPatterns as $pattern) {
        if (preg_match($pattern, $normalized) === 1) {
            $isModelQuestion = true;
            break;
        }
    }

    $referencesThisAssistant = preg_match("/(この|ここの|あなた|君|きみ|チャット|チャットボット|bot|ＢＯＴ|アシスタント|システム)/u", $normalized) === 1;
    $mentionsModelConcept = preg_match("/(ai|AI|ＡＩ|モデル|llm|LLM|頭脳|エンジン)/u", $normalized) === 1;
    $asksIdentityOrUsage = preg_match("/(何|なに|なん|どれ|どの|どんな|名前|名称|教えて|知りたい|使|利用|使用|搭載|採用|ベース|基盤|中身|内部|裏側|動いてる|動作|答えて)/u", $normalized) === 1;
    $asksWhatItUses = preg_match("/(何|なに|なん).*(使って|利用して|使用して|搭載して|採用して)/u", $normalized) === 1;

    if (!$isModelQuestion
        && preg_match("/モデル/u", $normalized) === 1
        && preg_match("/(何|なに|どれ|どの|名前|名称|教えて|知りたい|使|利用|使用|搭載|内部|裏側|ベース|基盤)/u", $normalized) === 1) {
        $isModelQuestion = true;
    }

    if (!$isModelQuestion && $referencesThisAssistant && ($mentionsModelConcept || $asksWhatItUses) && $asksIdentityOrUsage) {
        $isModelQuestion = true;
    }

    $asksIdentity = preg_match("/(誰|だれ|何者|なにもの|正体|自己紹介|名前|名称|呼び名|中身)/u", $normalized) === 1;
    if (!$isModelQuestion && $referencesThisAssistant && $asksIdentity) {
        $isModelQuestion = true;
    }

    if ($isModelQuestion) {
        return [
            "role" => "assistant",
            "content" => PUBLIC_MODEL_NAME . "です。",
        ];
    }

    $liveInfoReply = buildLiveInfoReply($message, $normalized);
    if ($liveInfoReply !== null) {
        return $liveInfoReply;
    }

    return null;
}

function buildLiveInfoReply(string $message, string $normalized): ?array
{
    $intent = resolveLiveInfoIntent($message, $normalized);
    if ($intent === null) {
        return null;
    }

    try {
        $type = (string) ($intent["type"] ?? "");
        if ($type === "weather") {
            return buildWeatherReply($intent);
        }

        if ($type === "news") {
            return buildNewsReply($intent);
        }
    } catch (RuntimeException $exception) {
        return [
            "role" => "assistant",
            "content" => $exception->getMessage(),
        ];
    }

    return null;
}

function resolveLiveInfoIntent(string $message, string $normalized): ?array
{
    if (isWeatherIntent($normalized)) {
        return [
            "type" => "weather",
            "location" => extractWeatherLocation($message),
        ];
    }

    return resolveNewsIntent($message, $normalized);
}

function isWeatherIntent(string $normalized): bool
{
    return preg_match("/(天気|気温|降水確率|予報)/u", $normalized) === 1;
}

function extractWeatherLocation(string $message): string
{
    $trimmed = trim(preg_replace("/[？?！!。]+/u", "", $message));
    $defaultLocation = readChatbotConfig("CHATBOT_DEFAULT_WEATHER_LOCATION", "");
    $patterns = [
        "/^(.+?)(?:の|での)?(?:今|今日|明日)?(?:の)?(?:天気|気温|降水確率|予報).*$/u",
        "/^(?:今|今日|明日)?(?:の)?(?:天気|気温|降水確率|予報)[:：は]?(.+)$/u",
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $trimmed, $matches) !== 1) {
            continue;
        }

        $candidate = cleanWeatherLocationCandidate((string) ($matches[1] ?? ""));
        if ($candidate !== "") {
            return $candidate;
        }
    }

    return $defaultLocation;
}

function cleanWeatherLocationCandidate(string $value): string
{
    $cleaned = trim($value);
    $cleaned = preg_replace("/(の?(天気|気温|降水確率|予報).*)$/u", "", $cleaned);
    $cleaned = preg_replace("/(を教えてください|教えてください|を教えて|教えて|知りたい|しりたい|ってどう|ってなに|はどう|は？|ですか)$/u", "", $cleaned);
    return trim((string) $cleaned, " 　,、");
}

function buildWeatherReply(array $intent): array
{
    $location = trim((string) ($intent["location"] ?? ""));
    if ($location === "") {
        return [
            "role" => "assistant",
            "content" => "どの地域の天気を知りたいか教えてください。例: 川崎市、東京都千代田区、大阪市",
        ];
    }

    $geo = geocodeWeatherLocation($location);
    $current = fetchWeatherSnapshot((float) $geo["latitude"], (float) $geo["longitude"]);
    $locationLabel = buildWeatherLocationLabel($geo);
    $fetchedAt = formatJstTimestamp();

    return [
        "role" => "assistant",
        "content" => $locationLabel . "の現在の天気です（取得: " . $fetchedAt . "）。\n\n"
            . "- 天気: " . describeWeatherCode((int) ($current["weather_code"] ?? -1))
            . "\n- 気温: " . number_format((float) ($current["temperature_2m"] ?? 0), 1) . "°C"
            . "\n- 風速: " . number_format((float) ($current["wind_speed_10m"] ?? 0), 1) . " km/h"
            . "\n- 取得元: Open-Meteo",
    ];
}

function geocodeWeatherLocation(string $location): array
{
    $url = "https://nominatim.openstreetmap.org/search?" . http_build_query(
        [
            "q" => $location,
            "format" => "jsonv2",
            "limit" => 1,
        ],
        "",
        "&",
        PHP_QUERY_RFC3986
    );
    $body = fetchExternalBody($url);

    try {
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException("地域情報の解析に失敗しました。少し時間をおいて再度お試しください。");
    }

    $result = $decoded[0] ?? null;
    if (!is_array($result)) {
        throw new RuntimeException("地域名を特定できませんでした。都道府県や市区町村まで含めて教えてください。");
    }

    return $result;
}

function buildWeatherLocationLabel(array $geo): string
{
    $displayName = trim((string) ($geo["display_name"] ?? ""));
    if ($displayName !== "") {
        return $displayName;
    }

    $parts = [];
    foreach (["name", "admin1", "country"] as $field) {
        $value = trim((string) ($geo[$field] ?? ""));
        if ($value !== "" && !in_array($value, $parts, true)) {
            $parts[] = $value;
        }
    }

    return $parts === [] ? "指定地点" : implode(" / ", $parts);
}

function fetchWeatherSnapshot(float $latitude, float $longitude): array
{
    $url = "https://api.open-meteo.com/v1/forecast?" . http_build_query(
        [
            "latitude" => $latitude,
            "longitude" => $longitude,
            "current" => "temperature_2m,weather_code,wind_speed_10m",
            "forecast_days" => 1,
            "timezone" => "Asia/Tokyo",
        ],
        "",
        "&",
        PHP_QUERY_RFC3986
    );
    $body = fetchExternalBody($url);

    try {
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException("天気情報の解析に失敗しました。少し時間をおいて再度お試しください。");
    }

    $current = $decoded["current"] ?? null;
    if (!is_array($current)) {
        throw new RuntimeException("現在の天気情報を取得できませんでした。少し時間をおいて再度お試しください。");
    }

    return $current;
}

function describeWeatherCode(int $code): string
{
    return match ($code) {
        0 => "快晴",
        1 => "晴れ",
        2 => "一部くもり",
        3 => "くもり",
        45, 48 => "霧",
        51, 53, 55 => "霧雨",
        56, 57 => "凍結性の霧雨",
        61, 63, 65 => "雨",
        66, 67 => "凍結性の雨",
        71, 73, 75, 77 => "雪",
        80, 81, 82 => "にわか雨",
        85, 86 => "にわか雪",
        95 => "雷雨",
        96, 99 => "ひょうを伴う雷雨",
        default => "不明",
    };
}

function resolveNewsIntent(string $message, string $normalized): ?array
{
    $hasNewsCue = preg_match("/(ニュース|最新|時事|現況|状況|動向|今どう|いまどう)/u", $normalized) === 1;
    $hasAiCue = preg_match("/(AI|ＡＩ|生成AI|人工知能|LLM|llm)/u", $message) === 1;
    $hasWarCue = preg_match("/(戦争|戦況|紛争|停戦|軍事侵攻|ウクライナ|ガザ|中東情勢)/u", $message) === 1;

    if (!$hasNewsCue && !$hasAiCue && !$hasWarCue) {
        return null;
    }

    if (preg_match("/(今日のニュース|最新ニュース|時事ニュース|ニュースを教えて|最新のニュース)/u", $normalized) === 1) {
        return [
            "type" => "news",
            "query" => "最新ニュース",
            "label" => "今日の主要ニュース",
        ];
    }

    if ($hasAiCue && ($hasNewsCue || preg_match("/(AI事情|生成AI事情)/u", $normalized) === 1)) {
        return [
            "type" => "news",
            "query" => "生成AI 最新",
            "label" => "最新のAI関連ニュース",
        ];
    }

    if ($hasWarCue) {
        $topic = extractNewsTopic($message);
        return [
            "type" => "news",
            "query" => ($topic === "" ? "戦争" : $topic) . " 最新",
            "label" => "最新の戦況関連ニュース",
        ];
    }

    if ($hasNewsCue) {
        $topic = extractNewsTopic($message);
        if ($topic === "") {
            return [
                "type" => "news",
                "query" => "最新ニュース",
                "label" => "最新ニュース",
            ];
        }

        return [
            "type" => "news",
            "query" => $topic . " 最新",
            "label" => "「" . $topic . "」の最新ニュース",
        ];
    }

    return null;
}

function extractNewsTopic(string $message): string
{
    $cleaned = trim(preg_replace("/[？?！!。]+/u", "", $message));
    $patterns = [
        "/(を教えてください|教えてください|を教えて|教えて|知りたい|しりたい|ってどう|ってなに|ですか)$/u",
        "/(今日の|最新の|最近の|いまの|今の)/u",
        "/(ニュース|最新情報|時事ニュース|AI事情|事情|現況|状況|状態|動向)/u",
    ];

    foreach ($patterns as $pattern) {
        $cleaned = preg_replace($pattern, "", $cleaned);
    }

    return trim((string) $cleaned, " 　の");
}

function buildNewsReply(array $intent): array
{
    $query = trim((string) ($intent["query"] ?? ""));
    $label = trim((string) ($intent["label"] ?? "最新情報"));
    $items = fetchNewsItems($query, 3);
    if ($items === []) {
        throw new RuntimeException("最新ニュースを取得できませんでした。少し時間をおいて再度お試しください。");
    }

    $lines = [];
    foreach ($items as $index => $item) {
        $line = ($index + 1) . ". " . $item["title"];
        if ($item["source"] !== "") {
            $line .= "\n- 媒体: " . $item["source"];
        }
        if ($item["published_at"] !== "") {
            $line .= "\n- 公開: " . $item["published_at"];
        }
        if ($item["link"] !== "") {
            $line .= "\n- リンク: " . $item["link"];
        }
        $lines[] = $line;
    }

    return [
        "role" => "assistant",
        "content" => $label . "です（取得: " . formatJstTimestamp() . "）。\n\n"
            . implode("\n\n", $lines)
            . "\n\n- 取得元: Google News",
    ];
}

function fetchNewsItems(string $query, int $limit = 3): array
{
    $url = $query === "最新ニュース"
        ? "https://news.google.com/rss?hl=ja&gl=JP&ceid=JP:ja"
        : "https://news.google.com/rss/search?" . http_build_query(
            [
                "q" => $query,
                "hl" => "ja",
                "gl" => "JP",
                "ceid" => "JP:ja",
            ],
            "",
            "&",
            PHP_QUERY_RFC3986
        );
    $body = fetchExternalBody($url);

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NOCDATA);
    libxml_clear_errors();

    if (!$xml instanceof SimpleXMLElement || !isset($xml->channel->item)) {
        throw new RuntimeException("最新ニュースの解析に失敗しました。少し時間をおいて再度お試しください。");
    }

    $items = [];
    foreach ($xml->channel->item as $item) {
        $title = trim(html_entity_decode((string) ($item->title ?? ""), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"));
        if ($title === "") {
            continue;
        }

        $items[] = [
            "title" => $title,
            "source" => trim((string) ($item->source ?? "")),
            "published_at" => formatNewsTimestamp((string) ($item->pubDate ?? "")),
            "link" => trim((string) ($item->link ?? "")),
        ];

        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

function formatJstTimestamp(?string $rawTimestamp = null): string
{
    $timezone = new DateTimeZone("Asia/Tokyo");
    $date = $rawTimestamp === null || trim($rawTimestamp) === ""
        ? new DateTimeImmutable("now", $timezone)
        : new DateTimeImmutable($rawTimestamp);

    return $date->setTimezone($timezone)->format("Y-m-d H:i") . " JST";
}

function formatNewsTimestamp(string $rawTimestamp): string
{
    if (trim($rawTimestamp) === "") {
        return "";
    }

    try {
        return formatJstTimestamp($rawTimestamp);
    } catch (Exception $exception) {
        return "";
    }
}

function fetchExternalBody(string $url, int $timeout = 10): string
{
    if (!function_exists("curl_init")) {
        throw new RuntimeException("最新情報の取得に必要なcURL拡張が無効です。");
    }

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException("最新情報の取得を初期化できませんでした。");
    }

    curl_setopt_array(
        $curl,
        [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json, application/xml, text/xml, */*",
                "Accept-Language: ja,en-US;q=0.8",
                "User-Agent: OISHI-AI/1.0",
            ],
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_ENCODING => "",
        ]
    );

    $body = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    if ($body === false || $statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException("最新情報の取得に失敗しました。少し時間をおいて再度お試しください。");
    }

    return (string) $body;
}

function resolveHomepageIntent(string $normalized): ?array
{
    $lastSubject = getLastHomepageSubject();

    if (matchesServicesIntent($normalized, $lastSubject)) {
        return ["intent" => "services_overview", "subject" => "services"];
    }

    if (matchesStrengthsIntent($normalized, $lastSubject)) {
        return ["intent" => "strengths_overview", "subject" => "strengths"];
    }

    if (matchesContactIntent($normalized, $lastSubject)) {
        return ["intent" => "contact_overview", "subject" => "contact"];
    }

    $companyIntent = resolveCompanyIntent($normalized, $lastSubject);
    if ($companyIntent !== null) {
        return ["intent" => $companyIntent, "subject" => "company"];
    }

    return null;
}

function resolveCompanyIntent(string $normalized, string $lastSubject): ?string
{
    $hasExplicitCompanyCue = preg_match(
        "/(会社概要|会社情報|会社名|社名|法人名|所在地|拠点|住所|事業内容|どんな会社|何してる会社|何をしている会社|会社のこと|御社について|URL|url|ホームページ|サイト|webサイト|ウェブサイト)/u",
        $normalized
    ) === 1;
    $hasCompanyReference = hasHomepageReference($normalized);
    $isCompanyFollowUp = $lastSubject === "company"
        && preg_match("/^(概要|会社概要|会社情報|名前|会社名|社名|所在地|場所|どこ|拠点|住所|事業内容|何してる|何をしている|URL|url|ホームページ|サイト|創業|設立|設立年|創業年|代表|代表者|社長|従業員|社員数|資本金|売上|主要顧客|取引先|実績件数|株式会社|合同会社|法人格)(は|って|です|ですよ)?[？?]?$/u", $normalized) === 1;

    if (!$hasExplicitCompanyCue && !$hasCompanyReference && !$isCompanyFollowUp) {
        return null;
    }

    if (preg_match("/(創業|設立|設立年|創業年|代表|代表者|社長|従業員|社員数|資本金|売上|主要顧客|取引先|実績件数|株式会社|合同会社|法人格)/u", $normalized) === 1) {
        return "company_unsupported_detail";
    }

    if (preg_match("/(会社名|社名|法人名)/u", $normalized) === 1
        || (preg_match("/(名前|名称|呼び名)/u", $normalized) === 1 && ($hasCompanyReference || preg_match("/(会社|御社|法人)/u", $normalized) === 1))) {
        return "company_name";
    }

    if (preg_match("/(所在地|場所|どこ|拠点|住所)/u", $normalized) === 1) {
        return "company_location";
    }

    if (preg_match("/(事業内容|何してる|何をしている|事業は|業務は)/u", $normalized) === 1) {
        return "company_business";
    }

    if (preg_match("/(URL|url|ホームページ|サイト|webサイト|ウェブサイト)/u", $normalized) === 1) {
        return "company_url";
    }

    return "company_overview";
}

function matchesServicesIntent(string $normalized, string $lastSubject): bool
{
    if (preg_match("/(主なサービス|サービス一覧|どんなサービス|提供サービス|できること|支援内容|メニュー)/u", $normalized) === 1) {
        return true;
    }

    if (hasHomepageReference($normalized) && preg_match("/サービス/u", $normalized) === 1) {
        return true;
    }

    return $lastSubject === "services"
        && preg_match("/^(サービス|支援内容|できること|一覧|詳しく|メニュー)(は|って|です|ですよ)?[？?]?$/u", $normalized) === 1;
}

function matchesStrengthsIntent(string $normalized, string $lastSubject): bool
{
    if (preg_match("/(強み|選ばれる理由|特徴|他社との違い)/u", $normalized) === 1 && (hasHomepageReference($normalized) || preg_match("/(強み|選ばれる理由)/u", $normalized) === 1)) {
        return true;
    }

    return $lastSubject === "strengths"
        && preg_match("/^(強み|特徴|選ばれる理由|違い)(は|って|です|ですよ)?[？?]?$/u", $normalized) === 1;
}

function matchesContactIntent(string $normalized, string $lastSubject): bool
{
    if (preg_match("/(お問い合わせ|問い合わせ|問合せ|連絡先|無料相談|申し込み|申込|ヒアリング|相談の流れ|問い合わせの流れ|連絡方法)/u", $normalized) === 1) {
        return true;
    }

    if (hasHomepageReference($normalized) && preg_match("/(問い合わせ|問合せ|お問い合わせ|連絡|流れ)/u", $normalized) === 1) {
        return true;
    }

    return $lastSubject === "contact"
        && preg_match("/^(問い合わせ|問合せ|お問い合わせ|連絡先|無料相談|申し込み|申込|流れ|ヒアリング)(は|って|です|ですよ)?[？?]?$/u", $normalized) === 1;
}

function buildHomepageIntentReply(array $resolvedIntent): array
{
    $facts = HOMEPAGE_FACTS;
    $intent = (string) ($resolvedIntent["intent"] ?? "");
    $subject = (string) ($resolvedIntent["subject"] ?? "");

    switch ($intent) {
        case "company_name":
            $content = "ホームページに記載の会社名は " . $facts["company"]["name"] . " です。";
            break;

        case "company_location":
            $content = "ホームページに記載の所在地は " . $facts["company"]["location"] . " です。";
            break;

        case "company_business":
            $content = "ホームページに記載の事業内容は " . $facts["company"]["business"] . " です。";
            break;

        case "company_url":
            $content = "ホームページに記載のURLは " . $facts["company"]["url"] . " です。";
            break;

        case "company_unsupported_detail":
            $content = "ホームページに明記されている会社情報は、社名・所在地・事業内容・URLまでです。ご質問の内容についてはホームページには明記されていません。";
            break;

        case "services_overview":
            $serviceLines = [];
            foreach ($facts["services"] as $index => $service) {
                $serviceLines[] = ($index + 1) . ". " . $service["name"] . ": " . $service["summary"];
            }
            $content = "ホームページに記載の主なサービスは次の" . count($serviceLines) . "つです。\n\n" . implode("\n", $serviceLines);
            break;

        case "strengths_overview":
            $strengthLines = [];
            foreach ($facts["strengths"] as $index => $strength) {
                $strengthLines[] = ($index + 1) . ". " . $strength;
            }
            $content = "ホームページで案内している強みは次の" . count($strengthLines) . "点です。\n\n" . implode("\n", $strengthLines);
            break;

        case "contact_overview":
            $flowLines = [];
            foreach ($facts["contact"]["flow"] as $index => $step) {
                $flowLines[] = ($index + 1) . ". " . $step;
            }
            $content = "お問い合わせの流れは次のとおりです。\n\n"
                . implode("\n", $flowLines)
                . "\n\n補足:\n- " . $facts["contact"]["is_free"]
                . "\n- " . $facts["contact"]["proposal_timeline"]
                . "\n- お問い合わせ先ページは " . $facts["contact"]["page"] . " です。";
            break;

        case "company_overview":
        default:
            $content = "ホームページに記載の会社概要は次のとおりです。\n\n"
                . "- 社名: " . $facts["company"]["name"]
                . "\n- 所在地: " . $facts["company"]["location"]
                . "\n- 事業内容: " . $facts["company"]["business"]
                . "\n- URL: " . $facts["company"]["url"]
                . "\n- 補足: " . $facts["company"]["summary"];
            break;
    }

    return [
        "role" => "assistant",
        "content" => $content,
        "site_subject" => $subject,
    ];
}

function buildHomepageFactsPrompt(): string
{
    static $prompt = null;
    if (is_string($prompt)) {
        return $prompt;
    }

    $facts = HOMEPAGE_FACTS;
    $lines = [
        "以下は AI Lab OISHI のホームページ記載情報です。サイトや会社に関する回答は必ずこの範囲だけを使ってください。",
        "",
        "- サイト説明:",
    ];

    foreach ($facts["site_summary"] as $summary) {
        $lines[] = "  - " . $summary;
    }

    $lines[] = "";
    $lines[] = "- 会社概要:";
    $lines[] = "  - 社名: " . $facts["company"]["name"];
    $lines[] = "  - 所在地: " . $facts["company"]["location"];
    $lines[] = "  - 事業内容: " . $facts["company"]["business"];
    $lines[] = "  - URL: " . $facts["company"]["url"];
    $lines[] = "  - 補足: " . $facts["company"]["summary"];
    $lines[] = "  - メッセージ:";
    foreach ($facts["company"]["messages"] as $companyMessage) {
        $lines[] = "    - " . $companyMessage;
    }

    $lines[] = "";
    $lines[] = "- サービス:";
    foreach ($facts["services"] as $service) {
        $lines[] = "  - " . $service["name"] . ": " . $service["summary"];
    }

    $lines[] = "";
    $lines[] = "- 選ばれる理由:";
    foreach ($facts["strengths"] as $strength) {
        $lines[] = "  - " . $strength;
    }

    $lines[] = "";
    $lines[] = "- 問い合わせ:";
    $lines[] = "  - " . $facts["contact"]["is_free"];
    $lines[] = "  - " . $facts["contact"]["proposal_timeline"];
    $lines[] = "  - 流れ: " . implode(" → ", $facts["contact"]["flow"]);
    $lines[] = "  - お問い合わせページ: " . $facts["contact"]["page"];

    $lines[] = "";
    $lines[] = "- 注意:";
    $lines[] = "  - " . implode("、", $facts["unsupported_company_fields"]) . " はホームページ記載情報には含まれていない";
    $lines[] = "  - それらを聞かれたら「ホームページには明記されていません」と答える";

    $prompt = implode("\n", $lines);
    return $prompt;
}

function hasHomepageReference(string $normalized): bool
{
    return preg_match("/(御社|そちら|貴社|あなたの会社|AILabOISHI|AI Lab OISHI|オイシ|ホームページ|この会社|こちらの会社)/u", $normalized) === 1;
}

function normalizeIntentText(string $value): string
{
    return str_replace([" ", "　", "\n", "\r", "\t"], "", trim($value));
}

function getLastHomepageSubject(): string
{
    $subject = $_SESSION["chatbot_last_site_subject"] ?? "";
    return is_string($subject) ? $subject : "";
}

function buildChatRequestMessages(array $history, string $systemPrompt, int $maxHistory): array
{
    $messages = [];
    foreach (array_slice($history, -$maxHistory) as $item) {
        if (!is_array($item)) {
            continue;
        }

        $role = (string) ($item["role"] ?? "");
        $content = trim((string) ($item["content"] ?? ""));
        if (($role !== "user" && $role !== "assistant") || $content === "") {
            continue;
        }

        $messages[] = ["role" => $role, "content" => $content];
    }

    array_unshift($messages, ["role" => "system", "content" => trim($systemPrompt)]);
    return $messages;
}

function fetchAssistantReply(
    string $apiBase,
    string $apiKey,
    string $model,
    array $messages,
    int $requestTimeout
): array {
    if (!function_exists("curl_init")) {
        throw new RuntimeException("PHPのcURL拡張が無効です。");
    }

    $url = $apiBase . "/chat/completions";
    $payload = json_encode(
        [
            "model" => $model,
            "messages" => $messages,
            "temperature" => 0.4,
            "stream" => false,
        ],
        JSON_THROW_ON_ERROR
    );

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException("通信の初期化に失敗しました。");
    }

    curl_setopt_array(
        $curl,
        [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => buildApiHeaders($apiKey, false),
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => $requestTimeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]
    );

    $raw = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

    if ($raw === false) {
        throw new RuntimeException("AIへの接続に失敗しました: " . $curlError);
    }

    try {
        $decoded = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException("AIレスポンスの解析に失敗しました。");
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        $apiErrorMessage = normalizeApiError($decoded, $model);
        throw new RuntimeException("AI応答に失敗しました（{$statusCode}）: {$apiErrorMessage}");
    }

    return normalizeAssistantMessage($decoded["choices"][0]["message"] ?? null);
}

function fetchAssistantReplyWithFallback(
    string $apiBase,
    string $apiKey,
    string $primaryModel,
    string $fallbackModel,
    array $messages,
    int $requestTimeout
): array {
    try {
        return [
            fetchAssistantReply($apiBase, $apiKey, $primaryModel, $messages, $requestTimeout),
            $primaryModel,
        ];
    } catch (RuntimeException $exception) {
        if (!shouldRetryWithFallback($exception->getMessage(), $primaryModel, $fallbackModel)) {
            throw $exception;
        }
    }

    return [
        fetchAssistantReply($apiBase, $apiKey, $fallbackModel, $messages, $requestTimeout),
        $fallbackModel,
    ];
}

function streamAssistantReplyWithFallback(
    string $apiBase,
    string $apiKey,
    string $primaryModel,
    string $fallbackModel,
    array $messages,
    int $requestTimeout,
    callable $onDelta
): array {
    try {
        return [
            streamAssistantReply($apiBase, $apiKey, $primaryModel, $messages, $requestTimeout, $onDelta),
            $primaryModel,
        ];
    } catch (RuntimeException $exception) {
        if (!shouldRetryWithFallback($exception->getMessage(), $primaryModel, $fallbackModel)) {
            throw $exception;
        }
    }

    emitStreamEvent("meta", ["model" => $fallbackModel]);
    return [
        streamAssistantReply($apiBase, $apiKey, $fallbackModel, $messages, $requestTimeout, $onDelta),
        $fallbackModel,
    ];
}

function streamAssistantReply(
    string $apiBase,
    string $apiKey,
    string $model,
    array $messages,
    int $requestTimeout,
    callable $onDelta
): array {
    if (!function_exists("curl_init")) {
        throw new RuntimeException("PHPのcURL拡張が無効です。");
    }

    $url = $apiBase . "/chat/completions";
    $payload = json_encode(
        [
            "model" => $model,
            "messages" => $messages,
            "temperature" => 0.4,
            "stream" => true,
        ],
        JSON_THROW_ON_ERROR
    );

    $assistantContent = "";
    $assistantReasoning = "";
    $eventBuffer = "";
    $rawResponse = "";

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException("通信の初期化に失敗しました。");
    }

    curl_setopt_array(
        $curl,
        [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HTTPHEADER => buildApiHeaders($apiKey, true),
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => $requestTimeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_WRITEFUNCTION => static function ($curlHandle, string $chunk) use (
                &$assistantContent,
                &$assistantReasoning,
                &$eventBuffer,
                &$rawResponse,
                $onDelta
            ): int {
                $rawResponse .= $chunk;
                $eventBuffer .= str_replace(["\r\n", "\r"], "\n", $chunk);

                while (($separator = strpos($eventBuffer, "\n\n")) !== false) {
                    $block = substr($eventBuffer, 0, $separator);
                    $eventBuffer = substr($eventBuffer, $separator + 2);
                    processUpstreamStreamBlock($block, $assistantContent, $assistantReasoning, $onDelta);
                }

                return strlen($chunk);
            },
        ]
    );

    $result = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

    if ($result === false) {
        throw new RuntimeException("AIへの接続に失敗しました: " . $curlError);
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        $decoded = [];
        try {
            $decoded = json_decode($rawResponse, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $decoded = [];
        }

        $apiErrorMessage = normalizeApiError($decoded, $model);
        throw new RuntimeException("AI応答に失敗しました（{$statusCode}）: {$apiErrorMessage}");
    }

    if ($assistantContent === "") {
        throw new RuntimeException("AIから空の応答が返されました。");
    }

    return [
        "role" => "assistant",
        "reasoning" => trim($assistantReasoning),
        "content" => trim($assistantContent),
    ];
}

function processUpstreamStreamBlock(
    string $block,
    string &$assistantContent,
    string &$assistantReasoning,
    callable $onDelta
): void {
    $dataLines = [];
    foreach (explode("\n", $block) as $line) {
        $trimmedLine = trim($line);
        if ($trimmedLine === "" || !str_starts_with($trimmedLine, "data:")) {
            continue;
        }

        $dataLines[] = ltrim(substr($trimmedLine, 5));
    }

    if ($dataLines === []) {
        return;
    }

    $payload = implode("\n", $dataLines);
    if ($payload === "[DONE]") {
        return;
    }

    try {
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        return;
    }

    $delta = $decoded["choices"][0]["delta"] ?? null;
    if (!is_array($delta)) {
        return;
    }

    $reasoning = normalizeMessageText($delta["reasoning"] ?? "");
    if ($reasoning !== "") {
        $assistantReasoning .= $reasoning;
        $onDelta("reasoning", $reasoning);
    }

    $content = normalizeMessageText($delta["content"] ?? "");
    if ($content !== "") {
        $assistantContent .= $content;
        $onDelta("content", $content);
    }
}

function buildApiHeaders(string $apiKey, bool $streaming): array
{
    $headers = [
        "Content-Type: application/json",
    ];

    if ($streaming) {
        $headers[] = "Accept: text/event-stream";
    }

    if ($apiKey !== "") {
        $headers[] = "Authorization: Bearer " . $apiKey;
    }

    return $headers;
}

function shouldRetryWithFallback(string $message, string $primaryModel, string $fallbackModel): bool
{
    if ($fallbackModel === "" || $fallbackModel === $primaryModel) {
        return false;
    }

    return str_contains($message, "モデル {$primaryModel} が見つかりません");
}

function normalizeApiError(array $decoded, string $model): string
{
    $message = trim((string) ($decoded["error"]["message"] ?? ""));
    if ($message === "") {
        return "不明なAPIエラー";
    }

    $lower = strtolower($message);
    if (str_contains($lower, "model") && str_contains($lower, "not found")) {
        return "モデル {$model} が見つかりません。";
    }

    if (str_contains($lower, "connection refused") || str_contains($lower, "failed to connect")) {
        return "AIエンドポイントへ接続できません。";
    }

    return $message;
}

function normalizeAssistantMessage(mixed $message): array
{
    if (!is_array($message)) {
        throw new RuntimeException("AIレスポンスの解析に失敗しました。");
    }

    $content = normalizeMessageText($message["content"] ?? "");
    $reasoning = normalizeMessageText($message["reasoning"] ?? "");

    if ($content === "") {
        throw new RuntimeException("AIから空の応答が返されました。");
    }

    return [
        "role" => "assistant",
        "reasoning" => $reasoning,
        "content" => $content,
    ];
}

function normalizeMessageText(mixed $content): string
{
    if (is_string($content)) {
        return $content;
    }

    if (!is_array($content)) {
        return "";
    }

    $parts = [];
    foreach ($content as $item) {
        if (!is_array($item)) {
            continue;
        }

        $type = (string) ($item["type"] ?? "");
        if ($type === "text" && isset($item["text"]) && is_string($item["text"])) {
            $parts[] = $item["text"];
        }
    }

    return implode("\n", array_filter($parts, static fn ($part): bool => $part !== ""));
}

function storeCompletedExchange(array $pendingMessages, array $assistantMessage, string $activeModel): void
{
    $_SESSION["chat_messages"] = $pendingMessages;

    $storedAssistantMessage = [
        "role" => "assistant",
        "content" => (string) ($assistantMessage["content"] ?? ""),
    ];

    $reasoning = trim((string) ($assistantMessage["reasoning"] ?? ""));
    if ($reasoning !== "") {
        $storedAssistantMessage["reasoning"] = $reasoning;
    }

    $siteSubject = trim((string) ($assistantMessage["site_subject"] ?? ""));
    if ($siteSubject !== "") {
        $storedAssistantMessage["site_subject"] = $siteSubject;
        $_SESSION["chatbot_last_site_subject"] = $siteSubject;
    } else {
        unset($_SESSION["chatbot_last_site_subject"]);
    }

    $_SESSION["chat_messages"][] = $storedAssistantMessage;
    $_SESSION["chatbot_active_model"] = $activeModel;
}

function getIntroGreetingCopyForJst(?int $hour = null): array
{
    $resolvedHour = $hour;
    if ($resolvedHour === null) {
        $resolvedHour = (int) (new DateTimeImmutable("now", new DateTimeZone("Asia/Tokyo")))->format("G");
    }

    if ($resolvedHour >= 5 && $resolvedHour < 11) {
        return ["おはようございます", "今日はどんなことを進めますか？"];
    }

    if ($resolvedHour >= 11 && $resolvedHour < 18) {
        return ["こんにちは", "何かお手伝いできることはありますか？"];
    }

    return ["こんばんは", "何かお手伝いできることはありますか？"];
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}
