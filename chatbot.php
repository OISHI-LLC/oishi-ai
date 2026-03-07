<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const DEFAULT_MODEL = "gpt-oss:20b-cloud";
const PUBLIC_MODEL_NAME = "OISHI-OSS";
const DEFAULT_API_BASE = "https://ollama.com/v1";
const DEFAULT_MAX_HISTORY = 12;
const DEFAULT_REQUEST_TIMEOUT = 120;
const DEFAULT_SYSTEM_PROMPT = <<<PROMPT
あなたはOISHI LLCのAI導入アドバイザーです。
原則として日本語で、具体性のある回答をしてください。
回答はスマホで読みやすいように、最初に結論を短く述べ、必要に応じて短い箇条書きで整理してください。
Markdownの表（| 区切り）、コードブロック、過度な記号装飾は使わないでください。
要件が曖昧なときは、前提を決めつけずに不足情報を1つずつ確認してください。
業務改善、AI導入、PoC、チャットボット、自動化の相談に強いアシスタントとして振る舞ってください。
モデル名や利用しているAIについて聞かれた場合は、「OISHI-OSSです。」と答えてください。
会社情報、サービス、強み、問い合わせ案内などサイトに関する回答は、別途渡されるホームページ記載情報だけを根拠にしてください。
その情報にない事項は推測せず、「ホームページには明記されていません」と答えてください。
PROMPT;
const HOMEPAGE_FACTS = [
    "site_summary" => [
        "小規模事業者から大企業まで、AI導入を戦略から実装までワンストップで支援",
        "AIの力で、ビジネスを次のステージへ",
        "規模を問わず、最適なAIソリューションを戦略から実装までワンストップで提供",
    ],
    "company" => [
        "name" => "AI Lab OISHI",
        "location" => "神奈川県川崎市",
        "business" => "AIコンサルティング / AI開発 / DX推進支援",
        "url" => "https://oishillc.jp",
        "summary" => "小規模事業者から大企業までを対象に、AI導入を戦略から実装までワンストップで支援しています。",
        "messages" => [
            "AIは大企業だけのものではない",
            "「何ができるか分からない」という段階から一緒に考え、ビジネスに本当に役立つAI活用を見つけ出す",
        ],
    ],
    "services" => [
        [
            "name" => "AI戦略コンサルティング",
            "summary" => "ビジネス課題のヒアリングからAI導入ロードマップの策定まで支援します。",
        ],
        [
            "name" => "カスタムAI開発",
            "summary" => "チャットボット・文書解析・画像認識・予測モデルなどの業務特化型AIシステムを開発します。",
        ],
        [
            "name" => "AIエージェント開発",
            "summary" => "情報収集・判断補助・タスク実行まで含む業務フロー自動化を支援します。",
        ],
        [
            "name" => "業務プロセス自動化",
            "summary" => "レポート作成・データ入力・メール対応などの定型業務を自動化します。",
        ],
        [
            "name" => "AI研修・内製化支援",
            "summary" => "生成AI活用研修と社内AIチーム立ち上げを支援します。",
        ],
        [
            "name" => "AI導入診断",
            "summary" => "AI導入余地の分析、投資対効果試算、レポート提供を行います。",
        ],
        [
            "name" => "既存システムへのAI統合",
            "summary" => "API連携やプラグイン開発で既存環境を高度化します。",
        ],
    ],
    "strengths" => [
        "規模を問わない柔軟な対応",
        "戦略から実装まで一気通貫",
        "最新技術への迅速なキャッチアップ",
    ],
    "contact" => [
        "is_free" => "初回の相談は無料",
        "proposal_timeline" => "お問い合わせから最短3営業日で提案可能",
        "flow" => [
            "お問い合わせ",
            "無料ヒアリング（オンライン30〜60分程度）",
            "ご提案",
        ],
        "page" => "/contact/",
    ],
    "unsupported_company_fields" => [
        "創業年 / 設立年",
        "代表者名",
        "従業員数",
        "資本金",
        "売上",
        "主要顧客 / 取引先",
        "取引実績件数",
        "株式会社などの法人格表記",
    ],
];

require_once __DIR__ . "/inc/chatbot/core.php";

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
$logo512Url = buildThemeAssetUrl("logo.png");
$error = null;
$draftMessage = "";

if (!isset($_SESSION["chat_messages"]) || !is_array($_SESSION["chat_messages"])) {
    $_SESSION["chat_messages"] = [];
}
$hasMessages = count($_SESSION["chat_messages"]) > 0;
$_SESSION["chatbot_active_model"] = (string) ($_SESSION["chatbot_active_model"] ?? $model);
[$introGreetingText, $introCopyText] = getIntroGreetingCopyForJst();

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (isset($_POST["reset"])) {
        $_SESSION["chat_messages"] = [];
        $_SESSION["chatbot_active_model"] = $model;
        unset($_SESSION["chatbot_last_site_subject"]);
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
        $assistantOverride = buildAssistantOverride($draftMessage);
        if ($assistantOverride !== null) {
            storeCompletedExchange($pendingMessages, $assistantOverride, PUBLIC_MODEL_NAME);
            header("Location: " . strtok((string) ($_SERVER["REQUEST_URI"] ?? ""), "?"), true, 303);
            exit;
        }

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

?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <title>OISHI AI</title>
  <link rel="icon" href="/wp-content/themes/oishi-ai/site-icon.png" type="image/png">
  <link rel="stylesheet" href="<?= escapeHtml(buildThemeAssetUrl("assets/css/chatbot.css")) ?>">
</head>
<body>
  <main class="container">
    <header class="header">
      <div class="brand">
        <img
          src="<?= escapeHtml($logo56Url) ?>"
          srcset="<?= escapeHtml($logo56Url) ?> 56w, <?= escapeHtml($logo112Url) ?> 112w"
          sizes="28px"
          width="28"
          height="28"
          alt="AI Lab OISHIロゴ"
          class="header-logo"
          loading="eager"
          decoding="async"
        >
        <div class="brand-copy">
          <p class="brand-kicker">AI Lab OISHI</p>
          <h1 class="title">Chat Assistant</h1>
        </div>
      </div>
    </header>

    <section class="intro" id="intro"<?= $hasMessages ? " hidden" : "" ?>>
      <div class="intro-mark">
        <img
          src="<?= escapeHtml($logo112Url) ?>"
          srcset="<?= escapeHtml($logo112Url) ?> 112w, <?= escapeHtml($logo512Url) ?> 512w"
          sizes="72px"
          width="72"
          height="72"
          alt="OISHI AIロゴ"
          class="intro-logo"
          loading="eager"
          decoding="async"
        >
      </div>
      <p class="intro-kicker">OISHI AI Concierge</p>
      <h2 class="intro-title" id="intro-greeting"><?= escapeHtml($introGreetingText) ?></h2>
      <p class="intro-copy" id="intro-copy"><?= escapeHtml($introCopyText) ?></p>
      <div class="suggestions" id="suggestions">
        <button class="suggestion" type="button" data-prompt="AI導入の進め方を相談したいです。最初の3ステップを教えてください。">AI導入の進め方</button>
        <button class="suggestion" type="button" data-prompt="最近のブログで読むべき記事を3つ教えてください。">おすすめ記事を見る</button>
        <button class="suggestion" type="button" data-prompt="業務自動化を進めたいです。まず何から整理すればいいですか？">業務自動化を相談</button>
        <button class="suggestion" type="button" data-prompt="お問い合わせの進め方を教えてください。">お問い合わせしたい</button>
      </div>
    </section>

    <section class="messages<?= $hasMessages ? " has-messages" : "" ?>" id="messages">
      <?php foreach ($_SESSION["chat_messages"] as $item): ?>
        <?php $role = ($item["role"] ?? "") === "user" ? "user" : "assistant"; ?>
        <?php $content = (string) ($item["content"] ?? ""); ?>
        <?php $reasoning = trim((string) ($item["reasoning"] ?? "")); ?>
        <?php if ($role === "user"): ?>
          <div class="bubble user"><?= escapeHtml($content) ?></div>
        <?php else: ?>
          <article class="assistant-entry">
            <?php if ($reasoning !== ""): ?>
              <details class="reasoning">
                <summary>思考</summary>
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
      <div class="composer">
        <form id="chat-form" method="post" class="chat-form">
          <label class="sr-only" for="chat-message">メッセージ</label>
          <textarea id="chat-message" name="message" required><?= escapeHtml($draftMessage) ?></textarea>
          <div class="actions">
            <div class="composer-note">AI導入、ブログ、業務自動化、お問い合わせまでそのまま相談できます。</div>
            <button class="send" id="send-button" type="submit">送信</button>
          </div>
        </form>
        <form method="post" id="reset-form">
          <button class="reset" type="submit" name="reset" value="1">新規会話</button>
        </form>
      </div>
    </section>
  </main>

  <script>
    window.OISHI_CHATBOT_CONFIG = <?= json_encode([
        "typingLogo" => [
            "src" => $logo56Url,
            "srcset" => $logo56Url . " 56w, " . $logo112Url . " 112w",
            "sizes" => "28px",
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  </script>
  <script src="<?= escapeHtml(buildThemeAssetUrl("assets/js/chatbot.js")) ?>" defer></script>
</body>
</html>
