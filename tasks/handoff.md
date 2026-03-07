# Handoff Log

このファイルは Codex / Claude の引き継ぎ専用です。
新しいエントリを先頭に追記してください。
過去のエントリは `handoff-archive.md` を参照。

## Template

### YYYY-MM-DD HH:MM JST | Agent: Codex or Claude
- Task:
- Changed Files:
- Deploy:
- Verification:
- Open Items:
- Next Action:

---

### 2026-03-08 03:14 JST | Agent: Codex
- Task: チャットボットの会社/サイト案内を構造化データ + 固定応答レイヤーへ切り替え、短い追撃でも文脈を維持するよう修正
- Changed Files:
  - `chatbot.php`
  - `inc/chatbot/core.php`
  - `tasks/lessons.md`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo-archive.md`
- Deploy:
  - GitHub push: `834fb4b` -> `master`
  - GitHub Actions: `Deploy to Xserver` run `22804413312` が `success`
- Verification:
  - local:
    - `php -l chatbot.php` OK
    - `php -l inc/chatbot/core.php` OK
    - `buildAssistantOverride(...)` で `御社の概要を教えて` / `会社名を教えて` / `御社について教えて` / `主なサービスを教えて` / `強みは？` / `問い合わせ方法を教えて` が固定応答に解決することを確認
    - `PoCの進め方を教えて` / `売上予測のPoCは何から始める？` は override されず `null`、`モデル名を教えて` は `OISHI-OSSです。` を返すことを確認
    - セッション保存後の `売上は？` -> `ホームページには明記されていません`、`概要ですよ` -> 会社概要再提示を確認
  - live:
    - `/` `HTTP 200`
    - `/blog/` `HTTP 200`
    - `/wp-login.php` `HTTP 200`
    - `/favicon.ico` `HTTP 200`
    - `https://www.oishillc.jp/wp-content/themes/oishi-ai/chatbot.php` `HTTP 200`
    - live HTML に `assets/css/chatbot.css?v=1772904321` / `assets/js/chatbot.js?v=1772904321` / `window.OISHI_CHATBOT_CONFIG` を確認
    - live stream `POST https://www.oishillc.jp/wp-content/themes/oishi-ai/chatbot.php?stream=1`:
      - `御社の概要を教えて` -> 会社概要の固定応答
      - `会社名を教えて` -> `ホームページに記載の会社名は AI Lab OISHI です。`
      - 同一セッションで `売上は？` -> `ホームページには明記されていません`
      - 同一セッションで `概要ですよ` -> 会社概要を再提示
- Open Items:
  - `GPT-5.4` 予約投稿（ID 32）の自動公開確認は未実施。現在時刻は `2026-03-08 03:14 JST` で、予定公開時刻 `2026-03-08 04:00 JST` 前
- Next Action:
  - `2026-03-08 04:00 JST` 以降に `/blog/` と該当記事URLで `GPT-5.4` 記事の公開を確認し、必要なら `tasks/handoff.md` を更新

### 2026-03-08 02:33 JST | Agent: Codex
- Task: チャットボット返答の可読性を改善し、Markdown生表示と壊れたJS配信を修正
- Changed Files:
  - `chatbot.php`
  - `inc/chatbot/core.php`
  - `assets/js/chatbot.js`
  - `assets/css/chatbot.css`
  - `tasks/lessons.md`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo.md`
  - `tasks/todo-archive.md`
- Deploy:
  - GitHub push: `bb82378` -> `master`
  - GitHub Actions: `Deploy to Xserver` run `22803654054` が `success`
- Verification:
  - local:
    - `node --check assets/js/chatbot.js` OK
    - `php -l chatbot.php` OK
    - `php -l inc/chatbot/core.php` OK
    - Node モックで assistant bubble の Markdown table が HTML table 化され、`<strong>` も適用されることを確認
    - `buildAssistantOverride("主なサービスを教えてください")` が番号付きの読みやすい文面を返すことを確認
  - live:
    - `/` `HTTP 200`
    - `/blog/` `HTTP 200`
    - `/wp-login.php` `HTTP 200`
    - `/favicon.ico` `HTTP 200`
    - `chatbot.php` HTML に `assets/css/chatbot.css?v=1772904321` / `assets/js/chatbot.js?v=1772904321` / `window.OISHI_CHATBOT_CONFIG` を確認
    - live `assets/js/chatbot.js` を取得して構文エラーなし、`formatAssistantBubble` と `assistant-table-wrap` を含むことを確認
    - live stream `POST ...chatbot.php?stream=1`:
      - `主なサービスを教えてください` -> Markdown表ではなく、1〜7の番号付き回答で `event: content` / `event: done`
- Open Items:
  - `GPT-5.4` 予約投稿（ID 32）の自動公開確認は未実施。現在時刻は `2026-03-08 02:33 JST` で、予定公開時刻 `2026-03-08 04:00 JST` 前
- Next Action:
  - `2026-03-08 04:00 JST` 以降に `/blog/` と該当記事URLで `GPT-5.4` 記事の公開を確認し、必要なら `tasks/handoff.md` を更新

### 2026-03-08 01:53 JST | Agent: Codex
- Task: 3/7 ブログアセットの Git 整合性を回復し、CI-only で本番再同期
- Changed Files:
  - `assets/blog/20260307-gpt54/*.webp`
  - `assets/blog/20260307-mac-comparison/*.webp`
  - `assets/blog/20260307-mac-comparison/*.svg`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo.md`
  - `tasks/todo-archive.md`
- Deploy:
  - GitHub push: `78d3a6f` -> `master`
  - GitHub Actions: `Deploy to Xserver` run `22803010650` が `success`
- Verification:
  - ヘルスチェック:
    - `/` `HTTP 200`
    - `/blog/` `HTTP 200`
    - `/wp-login.php` `HTTP 200`
    - `/favicon.ico` `HTTP 200`
  - live article `/2026/03/07/mac-comparison-2026-spring/` が `HTTP 200`
  - live article HTML に以下を確認:
    - `/wp-content/themes/oishi-ai/assets/blog/20260307-mac-comparison/hero.webp`
    - `/wp-content/themes/oishi-ai/assets/blog/20260307-mac-comparison/img-03-flowchart.webp`
    - `/wp-content/themes/oishi-ai/assets/blog/20260307-mac-comparison/img-04-price-range.webp`
  - asset checks:
    - `/wp-content/themes/oishi-ai/assets/blog/20260307-gpt54/hero-gpt54.webp` `HTTP 200`
    - `/wp-content/themes/oishi-ai/assets/blog/20260307-mac-comparison/img-01-positioning-map.svg` `HTTP 200`
  - ローカルと live のサイズ照合:
    - 10件の `webp` が local size = remote `content-length` で一致
  - `/blog/` で `mac-comparison` 記事掲載を確認、`GPT-5.4` 記事は未掲載（公開時刻前）
- Open Items:
  - `GPT-5.4` 予約投稿（ID 32）の自動公開確認は未実施。現在時刻は `2026-03-08 01:53 JST` で、予定公開時刻 `2026-03-08 04:00 JST` 前
- Next Action:
  - `2026-03-08 04:00 JST` 以降に `/blog/` と該当記事URLで公開確認し、必要なら `tasks/handoff.md` を更新
