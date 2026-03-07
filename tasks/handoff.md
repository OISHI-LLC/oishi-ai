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

### 2026-03-08 03:25 JST | Agent: Codex
- Task: docs-only push で `Deploy to Xserver` が走ったため、workflow に changed-files 二重ガードを追加
- Changed Files:
  - `.github/workflows/deploy.yml`
  - `tasks/lessons.md`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo-archive.md`
- Deploy:
  - GitHub push: `4c341fe` -> `master`
  - GitHub Actions: 直前の docs-only push `db249c3` で `Deploy to Xserver` run `22804559952` が予期せず `success`
  - `4c341fe` push 後の GitHub Actions 一覧では、新しい `Deploy to Xserver` run はまだ発生していないことを確認
- Verification:
  - local:
    - `ruby -e "require 'yaml'; YAML.load_file('.github/workflows/deploy.yml')"` OK
    - runtime commit `3ecf613..834fb4b` の変更対象が `chatbot.php` / `inc/chatbot/core.php` であることを確認
    - docs commit `db249c3` の変更対象が `tasks/handoff-archive.md` / `tasks/handoff.md` / `tasks/lessons.md` / `tasks/todo-archive.md` のみであることを確認
  - GitHub:
    - `Deploy to Xserver` 最新 run 一覧で `db249c3` の docs-only push が実行対象になっていたことを確認
    - `4c341fe` push 後も最新 run は `22804559952` のままで、新規 deploy run 未発生を確認
- Open Items:
  - `GPT-5.4` 予約投稿（ID 32）の自動公開確認は未実施。現在時刻は `2026-03-08 03:25 JST` で、予定公開時刻 `2026-03-08 04:00 JST` 前
- Next Action:
  - 次回の docs-only push でも FTP deploy が走らないことを確認しつつ、`2026-03-08 04:00 JST` 以降に `GPT-5.4` 記事の公開を確認する

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
