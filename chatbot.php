<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const DEFAULT_MODEL = "gpt-oss:20b-cloud";
const DEFAULT_API_BASE = "https://ollama.com/v1";
const DEFAULT_MAX_HISTORY = 12;
const DEFAULT_REQUEST_TIMEOUT = 120;
const DEFAULT_SYSTEM_PROMPT = <<<PROMPT
あなたはOISHI LLCのAI導入アドバイザーです。
原則として日本語で、具体性のある回答をしてください。
要件が曖昧なときは、前提を決めつけずに不足情報を1つずつ確認してください。
業務改善、AI導入、PoC、チャットボット、自動化の相談に強いアシスタントとして振る舞ってください。
PROMPT;

loadChatbotEnv();

$apiBase = rtrim(readChatbotConfig("CHATBOT_API_BASE_URL", DEFAULT_API_BASE), "/");
$apiKey = readChatbotConfig("CHATBOT_API_KEY", "");
$model = readChatbotConfig("CHATBOT_MODEL", DEFAULT_MODEL);
$fallbackModel = readChatbotConfig("CHATBOT_FALLBACK_MODEL", "");
$systemPrompt = readChatbotConfig("CHATBOT_SYSTEM_PROMPT", DEFAULT_SYSTEM_PROMPT);
$maxHistory = max(4, (int) readChatbotConfig("CHATBOT_MAX_HISTORY", (string) DEFAULT_MAX_HISTORY));
$requestTimeout = max(15, (int) readChatbotConfig("CHATBOT_REQUEST_TIMEOUT", (string) DEFAULT_REQUEST_TIMEOUT));
$logo56Url = buildThemeAssetUrl("logo-56.png");
$logo112Url = buildThemeAssetUrl("logo-112.png");
$error = null;
$draftMessage = "";

if (!isset($_SESSION["chat_messages"]) || !is_array($_SESSION["chat_messages"])) {
    $_SESSION["chat_messages"] = [];
}
$_SESSION["chatbot_active_model"] = (string) ($_SESSION["chatbot_active_model"] ?? $model);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (isset($_POST["reset"])) {
        $_SESSION["chat_messages"] = [];
        $_SESSION["chatbot_active_model"] = $model;
        header("Location: " . strtok((string) ($_SERVER["REQUEST_URI"] ?? ""), "?"), true, 303);
        exit;
    }

    $draftMessage = trim((string) ($_POST["message"] ?? ""));

    if (isStreamingRequest()) {
        handleStreamingRequest(
            $draftMessage,
            $apiBase,
            $apiKey,
            $model,
            $fallbackModel,
            $systemPrompt,
            $maxHistory,
            $requestTimeout
        );
        exit;
    }

    if ($draftMessage === "") {
        $error = "メッセージを入力してください。";
    } else {
        $pendingMessages = $_SESSION["chat_messages"];
        $pendingMessages[] = ["role" => "user", "content" => $draftMessage];
        $requestMessages = buildChatRequestMessages($pendingMessages, $systemPrompt, $maxHistory);

        try {
            [$assistantMessage, $activeModel] = fetchAssistantReplyWithFallback(
                $apiBase,
                $apiKey,
                $model,
                $fallbackModel,
                $requestMessages,
                $requestTimeout
            );
            storeCompletedExchange($pendingMessages, $assistantMessage, $activeModel);
            header("Location: " . strtok((string) ($_SERVER["REQUEST_URI"] ?? ""), "?"), true, 303);
            exit;
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}

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

    $candidateFiles[] = dirname(__DIR__, 4) . "/.oishi-ai-chatbot.env";
    $candidateFiles[] = __DIR__ . "/.env";
    $candidateFiles[] = __DIR__ . "/.env.local";

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
    $fullPath = __DIR__ . "/" . $fileName;
    $version = file_exists($fullPath) ? (string) filemtime($fullPath) : "1";
    return "/wp-content/themes/oishi-ai/" . rawurlencode($fileName) . "?v=" . rawurlencode($version);
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

    $_SESSION["chat_messages"][] = $storedAssistantMessage;
    $_SESSION["chatbot_active_model"] = $activeModel;
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OISHI AI</title>
  <link rel="icon" href="/wp-content/themes/oishi-ai/site-icon.png" type="image/png">
  <style>
    :root {
      --bg: #f4f4ef;
      --card: #fffdf7;
      --ink: #1d232c;
      --accent: #006d77;
      --muted: #5f6b76;
      --user: #d8f3dc;
      --assistant: #ffffff;
      --reasoning: #f5f0e6;
      --danger: #b42318;
      --line: #e2dfd5;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: "Avenir Next", "Segoe UI", sans-serif;
      color: var(--ink);
      background:
        radial-gradient(circle at top right, #f8eecf 0%, rgba(248,238,207,0) 35%),
        radial-gradient(circle at bottom left, #dceef6 0%, rgba(220,238,246,0) 45%),
        var(--bg);
      min-height: 100vh;
      padding: 24px 12px;
    }
    .container {
      max-width: 860px;
      margin: 0 auto;
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: 0 18px 40px rgba(29, 35, 44, 0.08);
      overflow: hidden;
    }
    .header {
      padding: 20px;
      border-bottom: 1px solid #e9e4d8;
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      gap: 12px;
      flex-wrap: wrap;
    }
    .title {
      margin: 0;
      font-size: 1.2rem;
      letter-spacing: 0.02em;
    }
    .meta {
      margin: 0;
      color: var(--muted);
      font-size: 0.9rem;
    }
    .messages {
      display: none;
      gap: 14px;
      padding: 20px;
    }
    .messages.has-messages {
      display: grid;
    }
    .assistant-entry {
      display: grid;
      gap: 8px;
      justify-items: start;
    }
    .reasoning {
      width: min(88%, 100%);
      border: 1px solid #e6dfcf;
      border-radius: 12px;
      background: var(--reasoning);
      overflow: hidden;
    }
    .reasoning summary {
      cursor: pointer;
      list-style: none;
      padding: 10px 14px;
      font-size: 0.82rem;
      color: var(--muted);
      border-bottom: 1px solid #ece4d5;
    }
    .reasoning summary::-webkit-details-marker {
      display: none;
    }
    .reasoning-body {
      padding: 12px 14px;
      line-height: 1.55;
      white-space: pre-wrap;
      word-wrap: break-word;
      font-size: 0.95rem;
    }
    .bubble {
      border: 1px solid #e6e3d9;
      border-radius: 12px;
      padding: 12px 14px;
      line-height: 1.6;
      white-space: pre-wrap;
      word-wrap: break-word;
      max-width: 88%;
    }
    .bubble.user {
      background: var(--user);
      justify-self: end;
    }
    .bubble.assistant {
      background: var(--assistant);
      justify-self: start;
    }
    .bubble.assistant.is-waiting {
      display: inline-flex;
      align-items: center;
      min-width: 74px;
      min-height: 52px;
    }
    .typing-indicator {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
    }
    .typing-indicator-logo {
      width: 28px;
      height: 28px;
      display: block;
      transform-origin: center;
      animation: typing-logo 1.4s infinite ease-in-out;
      filter: drop-shadow(0 4px 10px rgba(0, 109, 119, 0.14));
    }
    @keyframes typing-logo {
      0%, 100% {
        opacity: 0.72;
        transform: translateY(0) scale(0.97);
      }
      50% {
        opacity: 1;
        transform: translateY(-3px) scale(1.06);
      }
    }
    .error {
      margin: 0 20px 16px;
      padding: 12px;
      border-radius: 10px;
      border: 1px solid #f4c7c3;
      color: var(--danger);
      background: #fff1f0;
    }
    .error[hidden] {
      display: none;
    }
    .controls {
      border-top: 1px solid #e9e4d8;
      padding: 16px 20px 20px;
      display: grid;
      gap: 10px;
    }
    textarea {
      width: 100%;
      min-height: 120px;
      resize: vertical;
      border: 1px solid #d4d8dc;
      border-radius: 10px;
      padding: 10px 12px;
      font: inherit;
      color: var(--ink);
      background: #fff;
    }
    textarea:disabled {
      background: #f7f7f2;
      color: #55606d;
    }
    .actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    button {
      border: none;
      border-radius: 10px;
      padding: 10px 14px;
      font: inherit;
      cursor: pointer;
    }
    button:disabled {
      cursor: wait;
      opacity: 0.7;
    }
    .send {
      background: var(--accent);
      color: #fff;
      font-weight: 600;
    }
    .reset {
      background: #e6e8eb;
      color: #202833;
    }
    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }
  </style>
</head>
<body>
  <main class="container">
    <header class="header">
      <h1 class="title">OISHI AI</h1>
    </header>

    <section class="messages<?= count($_SESSION["chat_messages"]) > 0 ? " has-messages" : "" ?>" id="messages">
      <?php foreach ($_SESSION["chat_messages"] as $item): ?>
        <?php $role = ($item["role"] ?? "") === "user" ? "user" : "assistant"; ?>
        <?php $content = (string) ($item["content"] ?? ""); ?>
        <?php $reasoning = trim((string) ($item["reasoning"] ?? "")); ?>
        <?php if ($role === "user"): ?>
          <div class="bubble user"><?= escapeHtml($content) ?></div>
        <?php else: ?>
          <article class="assistant-entry">
            <?php if ($reasoning !== ""): ?>
              <details class="reasoning" open>
                <summary>推論</summary>
                <div class="reasoning-body"><?= escapeHtml($reasoning) ?></div>
              </details>
            <?php endif; ?>
            <div class="bubble assistant"><?= escapeHtml($content) ?></div>
          </article>
        <?php endif; ?>
      <?php endforeach; ?>
    </section>

    <p class="error" id="error-box"<?= $error === null ? " hidden" : "" ?>><?= $error === null ? "" : escapeHtml($error) ?></p>

    <section class="controls">
      <form id="chat-form" method="post">
        <label class="sr-only" for="chat-message">メッセージ</label>
        <textarea id="chat-message" name="message" required><?= escapeHtml($draftMessage) ?></textarea>
        <div class="actions">
          <button class="send" id="send-button" type="submit">送信</button>
        </div>
      </form>
      <form method="post" id="reset-form">
        <div class="actions">
          <button class="reset" type="submit" name="reset" value="1">新規会話</button>
        </div>
      </form>
    </section>
  </main>

  <script>
    (() => {
      const typingLogo = <?= json_encode([
          "src" => $logo56Url,
          "srcset" => $logo56Url . " 56w, " . $logo112Url . " 112w",
          "sizes" => "28px",
      ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      const form = document.getElementById("chat-form");
      const resetForm = document.getElementById("reset-form");
      const textarea = document.getElementById("chat-message");
      const sendButton = document.getElementById("send-button");
      const messages = document.getElementById("messages");
      const errorBox = document.getElementById("error-box");
      let inflightController = null;
      let stickToBottom = true;

      const isNearBottom = () => {
        const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
        const viewportBottom = scrollTop + window.innerHeight;
        const documentBottom = document.documentElement.scrollHeight;
        return viewportBottom >= documentBottom - 160;
      };

      const maybeScrollToBottom = () => {
        if (!stickToBottom) {
          return;
        }

        window.requestAnimationFrame(() => {
          window.scrollTo({ top: document.documentElement.scrollHeight, behavior: "auto" });
        });
      };

      const clearError = () => {
        errorBox.hidden = true;
        errorBox.textContent = "";
      };

      const showError = (message) => {
        errorBox.hidden = false;
        errorBox.textContent = message;
      };

      const setBusy = (busy) => {
        textarea.disabled = busy;
        sendButton.disabled = busy;
        sendButton.textContent = busy ? "生成中..." : "送信";
      };

      const appendUserBubble = (content) => {
        messages.classList.add("has-messages");
        const bubble = document.createElement("div");
        bubble.className = "bubble user";
        bubble.textContent = content;
        messages.appendChild(bubble);
      };

      const appendAssistantEntry = () => {
        messages.classList.add("has-messages");

        const entry = document.createElement("article");
        entry.className = "assistant-entry";

        const reasoning = document.createElement("details");
        reasoning.className = "reasoning";
        reasoning.open = true;
        reasoning.hidden = true;

        const summary = document.createElement("summary");
        summary.textContent = "推論";

        const reasoningBody = document.createElement("div");
        reasoningBody.className = "reasoning-body";

        reasoning.append(summary, reasoningBody);

        const answer = document.createElement("div");
        answer.className = "bubble assistant is-waiting";

        const typingIndicator = document.createElement("span");
        typingIndicator.className = "typing-indicator";

        const typingLogoImage = document.createElement("img");
        typingLogoImage.className = "typing-indicator-logo";
        typingLogoImage.src = typingLogo.src;
        typingLogoImage.srcset = typingLogo.srcset;
        typingLogoImage.sizes = typingLogo.sizes;
        typingLogoImage.width = 28;
        typingLogoImage.height = 28;
        typingLogoImage.alt = "";
        typingLogoImage.decoding = "async";
        typingLogoImage.loading = "eager";
        typingLogoImage.setAttribute("aria-hidden", "true");

        typingIndicator.appendChild(typingLogoImage);

        answer.appendChild(typingIndicator);

        entry.append(reasoning, answer);
        messages.appendChild(entry);

        return { entry, reasoning, reasoningBody, answer, typingIndicator };
      };

      const stopWaitingIndicator = (nodes) => {
        if (!nodes.typingIndicator) {
          return;
        }

        nodes.typingIndicator.remove();
        nodes.typingIndicator = null;
        nodes.answer.classList.remove("is-waiting");
      };

      const handleEventBlock = (block, nodes) => {
        const lines = block.split("\n");
        let eventName = "message";
        const dataLines = [];

        for (const rawLine of lines) {
          if (rawLine.startsWith("event:")) {
            eventName = rawLine.slice(6).trim();
            continue;
          }

          if (rawLine.startsWith("data:")) {
            dataLines.push(rawLine.slice(5).trimStart());
          }
        }

        if (dataLines.length === 0) {
          return;
        }

        let payload;
        try {
          payload = JSON.parse(dataLines.join("\n"));
        } catch (error) {
          return;
        }

        if (eventName === "reasoning") {
          if (payload.delta) {
            nodes.reasoning.hidden = false;
            nodes.reasoningBody.textContent += payload.delta;
            maybeScrollToBottom();
          }
          return;
        }

        if (eventName === "content") {
          if (payload.delta) {
            stopWaitingIndicator(nodes);
            nodes.answer.textContent += payload.delta;
            maybeScrollToBottom();
          }
          return;
        }

        if (eventName === "error") {
          stopWaitingIndicator(nodes);
          showError(payload.message || "AI応答に失敗しました。");
          return;
        }

        if (eventName === "done") {
          stopWaitingIndicator(nodes);
          if (!nodes.reasoningBody.textContent.trim()) {
            nodes.reasoning.hidden = true;
          }
        }
      };

      const processStreamBuffer = (buffer, nodes) => {
        let working = buffer.replace(/\r\n/g, "\n");
        let boundary = working.indexOf("\n\n");

        while (boundary !== -1) {
          const block = working.slice(0, boundary);
          working = working.slice(boundary + 2);
          handleEventBlock(block, nodes);
          boundary = working.indexOf("\n\n");
        }

        return working;
      };

      window.addEventListener("scroll", () => {
        stickToBottom = isNearBottom();
      }, { passive: true });

      resetForm.addEventListener("submit", () => {
        if (inflightController) {
          inflightController.abort();
        }
      });

      form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (inflightController) {
          return;
        }

        const message = textarea.value.trim();
        if (!message) {
          showError("メッセージを入力してください。");
          textarea.focus();
          return;
        }

        stickToBottom = isNearBottom();
        clearError();
        appendUserBubble(message);
        const assistantNodes = appendAssistantEntry();
        maybeScrollToBottom();

        textarea.value = "";
        setBusy(true);

        const controller = new AbortController();
        inflightController = controller;

        try {
          const body = new URLSearchParams();
          body.set("message", message);

          const response = await fetch(`${window.location.pathname}?stream=1`, {
            method: "POST",
            headers: {
              "Accept": "text/event-stream",
            },
            body,
            signal: controller.signal,
          });

          if (!response.ok || !response.body) {
            throw new Error("ストリーミング接続の開始に失敗しました。");
          }

          const reader = response.body.getReader();
          const decoder = new TextDecoder();
          let buffer = "";

          while (true) {
            const { done, value } = await reader.read();
            if (done) {
              break;
            }

            buffer += decoder.decode(value, { stream: true });
            buffer = processStreamBuffer(buffer, assistantNodes);
          }

          buffer += decoder.decode();
          processStreamBuffer(buffer + "\n\n", assistantNodes);

          if (!assistantNodes.answer.textContent.trim()) {
            assistantNodes.entry.remove();
            throw new Error("AIから空の応答が返されました。");
          }
        } catch (error) {
          if (error instanceof DOMException && error.name === "AbortError") {
            return;
          }

          if (!assistantNodes.answer.textContent.trim() && !assistantNodes.reasoningBody.textContent.trim()) {
            assistantNodes.entry.remove();
          }

          showError(error instanceof Error ? error.message : "AI応答に失敗しました。");
        } finally {
          inflightController = null;
          setBusy(false);
          textarea.focus();
        }
      });
    })();
  </script>
</body>
</html>
