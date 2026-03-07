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
    $normalized = str_replace([" ", "　", "\n", "\r", "\t"], "", trim($message));
    if ($normalized === "") {
        return null;
    }

    $unsupportedDetailPatterns = [
        "/(創業|設立|設立年|創業年)/u",
        "/(代表|代表者|社長)/u",
        "/(従業員|社員)数/u",
        "/資本金/u",
        "/売上/u",
        "/(主要)?顧客/u",
        "/取引先/u",
        "/実績件数/u",
        "/(ビジネス|事業|収益)モデル/u",
    ];

    foreach ($unsupportedDetailPatterns as $pattern) {
        if (preg_match($pattern, $normalized) === 1) {
            return [
                "role" => "assistant",
                "content" => "ホームページに記載の範囲では、AI Lab OISHI の社名は AI Lab OISHI、所在地は神奈川県川崎市、事業内容は AIコンサルティング / AI開発 / DX推進支援 です。ご質問の内容についてはホームページには明記されていません。",
            ];
        }
    }

    $companyNamePatterns = [
        "/(会社|御社|社名|会社名|法人名).*(名前|名称|何|なに|教えて|知りたい)/u",
        "/(名前|名称).*(会社|御社|社名|会社名|法人名)/u",
    ];

    foreach ($companyNamePatterns as $pattern) {
        if (preg_match($pattern, $normalized) === 1) {
            return [
                "role" => "assistant",
                "content" => "ホームページに記載の会社名は AI Lab OISHI です。",
            ];
        }
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
        "/(ai|AI|ＡＩ|モデル|llm|LLM|頭脳|エンジン).*(名前|名称|教えて|知りたい|使|利用|使用|搭載|採用|ベース|基盤|中身|内部|裏側|動いてる|動作)/u",
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

    if (preg_match("/(あなたの会社|御社|会社概要|会社情報|どんな会社|何してる会社|何をしている会社|oishi|オイシ|ホームページ).*(教えて|知りたい|説明|紹介)/u", $message) === 1
        || preg_match("/(会社|事業内容|所在地|拠点)について/u", $message) === 1) {
        return [
            "role" => "assistant",
            "content" => "ホームページに記載の範囲では、AI Lab OISHI は小規模事業者から大企業までを対象に、AI導入を戦略から実装までワンストップで支援しています。会社概要として明記されているのは、所在地が神奈川県川崎市、事業内容が AIコンサルティング / AI開発 / DX推進支援、URL が https://oishillc.jp です。",
        ];
    }

    if (preg_match("/(サービス|できること|支援内容|メニュー)/u", $message) === 1) {
        return [
            "role" => "assistant",
            "content" => "ホームページに記載のサービスは、AI戦略コンサルティング、カスタムAI開発、AIエージェント開発、業務プロセス自動化、AI研修・内製化支援、AI導入診断、既存システムへのAI統合です。",
        ];
    }

    if (preg_match("/(強み|選ばれる理由|特徴)/u", $message) === 1) {
        return [
            "role" => "assistant",
            "content" => "ホームページでは、規模を問わない柔軟な対応、戦略から実装まで一気通貫、最新技術への迅速なキャッチアップ、の3点が強みとして案内されています。",
        ];
    }

    if (preg_match("/(問い合わせ|問合せ|相談|連絡|流れ)/u", $message) === 1) {
        return [
            "role" => "assistant",
            "content" => "ホームページでは、初回相談は無料で、お問い合わせから最短3営業日で提案可能と案内されています。流れは「お問い合わせ → 無料ヒアリング（オンライン30〜60分程度）→ ご提案」です。お問い合わせ先ページは /contact/ です。",
        ];
    }

    return null;
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

    array_unshift($messages, ["role" => "system", "content" => trim(HOMEPAGE_FACTS_PROMPT)]);
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
