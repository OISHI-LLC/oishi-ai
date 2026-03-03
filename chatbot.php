<?php
declare(strict_types=1);

session_start();

const CHAT_MODE_LABEL = "固定応答モード";
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
        $assistantReply = generateFixedReply($message);
        $_SESSION["chat_messages"][] = ["role" => "assistant", "content" => $assistantReply];
        header("Location: " . strtok($_SERVER["REQUEST_URI"], "?"));
        exit;
    }
}

function generateFixedReply(string $message): string
{
    $intents = [
        [
            "keywords" => ["料金", "費用", "価格", "見積", "予算"],
            "response" => "費用は要件とデータ量で変わります。目安は、相談・業務整理が5万円から、PoCは30万円から、本番導入は80万円からです。用途を教えてもらえれば、もう少し具体化できます。",
        ],
        [
            "keywords" => ["自動化", "業務改善", "工数削減"],
            "response" => "業務自動化なら、まずは「毎日同じ手順の作業」を1つ特定するのが最短です。対象業務の流れを3ステップで教えてもらえれば、自動化シナリオを提案します。",
        ],
        [
            "keywords" => ["チャットボット", "顧客対応", "問い合わせ対応"],
            "response" => "顧客対応チャットボットは、FAQ整備と回答ログ分析を同時に回すと精度が伸びます。公開前に50問程度のテスト会話を作るのがおすすめです。",
        ],
        [
            "keywords" => ["PoC", "poc", "検証", "試験導入"],
            "response" => "PoCは通常2〜4週間で実施できます。1週目で要件確定、2〜3週目で検証、4週目で結果評価という流れが一般的です。",
        ],
        [
            "keywords" => ["導入", "進め方", "手順", "ロードマップ"],
            "response" => "導入ステップは、1) 課題整理 2) 小規模検証 3) 本番適用 4) 運用改善 の順が失敗しにくいです。現場の担当者を早めに巻き込むのがポイントです。",
        ],
    ];

    foreach ($intents as $intent) {
        if (containsKeyword($message, $intent["keywords"])) {
            return (string) $intent["response"];
        }
    }

    if (containsKeyword($message, ["ありがとう", "助かる", "助かった"])) {
        return "どういたしまして。必要なら、そのまま次のアクション案まで一緒に整理します。";
    }

    $fallbackResponses = [
        "その観点は重要です。対象の業務と、今いちばん困っている点を1つだけ教えてください。",
        "ありがとうございます。現状の作業時間と目標の削減率がわかると、より現実的な提案ができます。",
        "良いテーマです。利用する部署と、扱うデータの種類を教えてもらえれば具体案を返せます。",
    ];
    $index = abs(crc32($message)) % count($fallbackResponses);
    return $fallbackResponses[$index];
}

function containsKeyword(string $text, array $keywords): bool
{
    foreach ($keywords as $keyword) {
        if (!is_string($keyword) || $keyword === "") {
            continue;
        }
        if (stripos($text, $keyword) !== false) {
            return true;
        }
    }
    return false;
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
      <p class="meta">応答モード: <?= escapeHtml(CHAT_MODE_LABEL) ?></p>
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
      <p class="hint">現在は固定応答モードで動作中です。APIキー不要・課金なしで利用できます。</p>
    </section>
  </main>
</body>
</html>
