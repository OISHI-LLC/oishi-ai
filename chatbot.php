<?php
declare(strict_types=1);

session_start();

const DEFAULT_MODEL = "gpt-oss:120b";
const DEFAULT_API_BASE = "https://api.openai.com/v1";
const DEFAULT_SYSTEM_PROMPT = <<<PROMPT
あなたはAI Lab OISHIの業務相談チャットアシスタントです。
原則として日本語で、わかりやすく丁寧に回答してください。
相手が明示的に他言語を希望した場合のみ、その言語で回答してください。
PROMPT;

$apiKey = (string) getenv("CHATBOT_API_KEY");
$apiBase = rtrim((string) (getenv("CHATBOT_API_BASE_URL") ?: DEFAULT_API_BASE), "/");
$model = (string) (getenv("CHATBOT_MODEL") ?: DEFAULT_MODEL);
$baseSystemPrompt = (string) (getenv("CHATBOT_SYSTEM_PROMPT") ?: DEFAULT_SYSTEM_PROMPT);
$systemPrompt = trim($baseSystemPrompt . "\n\n必ず原則日本語で回答してください。");
$error = null;

if (!isset($_SESSION["chat_messages"]) || !is_array($_SESSION["chat_messages"])) {
    $_SESSION["chat_messages"] = [];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["reset"])) {
        $_SESSION["chat_messages"] = [];
        header("Location: " . strtok($_SERVER["REQUEST_URI"], "?"));
        exit;
    }

    $message = trim((string) ($_POST["message"] ?? ""));
    if ($message === "") {
        $error = "メッセージを入力してください。";
    } else {
        $_SESSION["chat_messages"][] = ["role" => "user", "content" => $message];
        $requestMessages = array_merge(
            [["role" => "system", "content" => $systemPrompt]],
            $_SESSION["chat_messages"]
        );

        try {
            $assistantReply = fetchAssistantReply($apiBase, $apiKey, $model, $requestMessages);
            $_SESSION["chat_messages"][] = ["role" => "assistant", "content" => $assistantReply];
            header("Location: " . strtok($_SERVER["REQUEST_URI"], "?"));
            exit;
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}

function fetchAssistantReply(string $apiBase, string $apiKey, string $model, array $messages): string
{
    $url = $apiBase . "/chat/completions";
    $payload = json_encode(
        [
            "model" => $model,
            "messages" => $messages,
            "temperature" => 0.7,
        ],
        JSON_THROW_ON_ERROR
    );

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException("通信の初期化に失敗しました。");
    }

    $headers = ["Content-Type: application/json"];
    if ($apiKey !== "") {
        $headers[] = "Authorization: Bearer " . $apiKey;
    }

    curl_setopt_array(
        $curl,
        [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 60,
        ]
    );

    $raw = curl_exec($curl);
    if ($raw === false) {
        $curlError = curl_error($curl);
        throw new RuntimeException("通信に失敗しました: " . $curlError);
    }

    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException("APIレスポンスの解析に失敗しました。");
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        $apiErrorMessage = (string) ($decoded["error"]["message"] ?? "不明なAPIエラー");
        throw new RuntimeException("APIリクエストに失敗しました（{$statusCode}）: {$apiErrorMessage}");
    }

    $content = $decoded["choices"][0]["message"]["content"] ?? null;
    $text = normalizeMessageContent($content);
    if ($text === "") {
        throw new RuntimeException("APIから空の応答が返されました。");
    }

    return $text;
}

function normalizeMessageContent(mixed $content): string
{
    if (is_string($content)) {
        return trim($content);
    }

    if (is_array($content)) {
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
        return trim(implode("\n", $parts));
    }

    return "";
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
  <title>OISHI チャットボット</title>
  <style>
    :root {
      --bg: #f4f4ef;
      --card: #fffdf7;
      --ink: #1d232c;
      --accent: #006d77;
      --muted: #5f6b76;
      --user: #d8f3dc;
      --assistant: #ffffff;
      --danger: #b42318;
    }
    * { box-sizing: border-box; }
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
      max-width: 840px;
      margin: 0 auto;
      background: var(--card);
      border: 1px solid #e2dfd5;
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
      padding: 20px;
      display: grid;
      gap: 12px;
      min-height: 320px;
      max-height: 58vh;
      overflow-y: auto;
    }
    .bubble {
      border: 1px solid #e6e3d9;
      border-radius: 12px;
      padding: 12px 14px;
      line-height: 1.45;
      white-space: pre-wrap;
      word-wrap: break-word;
    }
    .bubble.user {
      background: var(--user);
      justify-self: end;
      max-width: 88%;
    }
    .bubble.assistant {
      background: var(--assistant);
      justify-self: start;
      max-width: 88%;
    }
    .error {
      margin: 0 20px 12px;
      padding: 12px;
      border-radius: 10px;
      border: 1px solid #f4c7c3;
      color: var(--danger);
      background: #fff1f0;
    }
    .controls {
      border-top: 1px solid #e9e4d8;
      padding: 16px 20px 20px;
      display: grid;
      gap: 10px;
    }
    textarea {
      width: 100%;
      min-height: 88px;
      resize: vertical;
      border: 1px solid #d4d8dc;
      border-radius: 10px;
      padding: 10px 12px;
      font: inherit;
      color: var(--ink);
      background: #fff;
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
    .send {
      background: var(--accent);
      color: #fff;
      font-weight: 600;
    }
    .reset {
      background: #e6e8eb;
      color: #202833;
    }
    .hint {
      margin: 0;
      color: var(--muted);
      font-size: 0.88rem;
    }
    code {
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      background: #eff3f7;
      border-radius: 6px;
      padding: 1px 5px;
    }
  </style>
</head>
<body>
  <main class="container">
    <header class="header">
      <h1 class="title">OISHI チャットボット</h1>
      <p class="meta">使用モデル: <?= escapeHtml($model) ?></p>
    </header>

    <section class="messages">
      <?php if (count($_SESSION["chat_messages"]) === 0): ?>
        <div class="bubble assistant">こんにちは。相談内容を入力すると、日本語で回答します。</div>
      <?php else: ?>
        <?php foreach ($_SESSION["chat_messages"] as $item): ?>
          <?php $role = ($item["role"] ?? "") === "user" ? "user" : "assistant"; ?>
          <?php $content = (string) ($item["content"] ?? ""); ?>
          <div class="bubble <?= escapeHtml($role) ?>"><?= escapeHtml($content) ?></div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <?php if ($error !== null): ?>
      <p class="error"><?= escapeHtml($error) ?></p>
    <?php endif; ?>

    <section class="controls">
      <form method="post">
        <textarea name="message" placeholder="相談内容を入力してください..." required></textarea>
        <div class="actions">
          <button class="send" type="submit">送信</button>
        </div>
      </form>
      <form method="post">
        <div class="actions">
          <button class="reset" type="submit" name="reset" value="1">会話をリセット</button>
        </div>
      </form>
      <p class="hint">Ollamaを使う場合: <code>CHATBOT_API_BASE_URL=http://127.0.0.1:11434/v1</code> を設定。必要に応じて <code>CHATBOT_API_KEY</code>, <code>CHATBOT_MODEL</code>, <code>CHATBOT_SYSTEM_PROMPT</code> も設定できます。</p>
    </section>
  </main>
</body>
</html>
