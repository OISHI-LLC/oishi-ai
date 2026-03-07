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

### 2026-03-07 16:50 JST | Agent: Codex
- Task: `chatbot.php` 分割版を CI-only で本番反映し、公開検証まで完了
- Changed Files:
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo.md`
- Deploy:
  - GitHub push: `ff035b7` -> `master`
  - GitHub Actions: `Deploy to Xserver` run `22794996586` が `success`
- Verification:
  - live `chatbot.php` HTML に以下を確認:
    - `/wp-content/themes/oishi-ai/assets/css/chatbot.css?...`
    - `/wp-content/themes/oishi-ai/assets/js/chatbot.js?...`
    - `window.OISHI_CHATBOT_CONFIG`
  - live stream `POST ...chatbot.php?stream=1`:
    - `モデル名は何ですか？` -> `OISHI-OSSです。`
    - `1+1だけ答えて。` で `event: content` / `event: done`
  - ヘルスチェック:
    - `/` `HTTP 200`
    - `/blog/` `HTTP 200`
    - `/wp-login.php` `HTTP 200`
    - `/favicon.ico` `HTTP 200`
- Open Items:
  - なし
- Next Action:
  - 以後は新機能追加時も `chatbot.php` へ直書きせず、`inc/chatbot` と `assets/js|css` 側へ追記する

### 2026-03-07 16:31 JST | Agent: Codex
- Task: `chatbot.php` の段階分割（関数群をモジュール化し、CSS/JSを外出し）
- Changed Files:
  - `chatbot.php`
  - `inc/chatbot/core.php`（新規）
  - `assets/css/chatbot.css`（新規）
  - `assets/js/chatbot.js`（新規）
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo.md`
- Deploy:
  - なし（ローカル構造改善のみ）
- Verification:
  - `php -l chatbot.php` / `php -l inc/chatbot/core.php` OK
  - `php chatbot.php` 出力で `assets/css/chatbot.css` / `assets/js/chatbot.js` 参照を確認
  - `php -r ... include chatbot.php`（`POST+stream=1`）で `モデル名は何ですか？` -> `OISHI-OSSです。` の SSE 応答を確認
  - `buildThemeAssetUrl(\"assets/css/chatbot.css\")` のURLが `%2F` ではなく通常パスで出ることを確認
  - 関数移動で壊れた相対パスを修正（`.env` 読み込み基準とアセット解決基準をテーマルート基準に統一）
- Open Items:
  - 本番反映は未実施（CIデプロイ実行が次ステップ）
- Next Action:
  - 必要なら CI を実行して本番反映し、`/`, `/blog/`, `/wp-login.php`, `/favicon.ico` を再検証

### 2026-03-07 17:30 JST | Agent: Claude
- Task: GPT-5.4 ブログ記事作成・画像生成・WordPress予約投稿・画像品質修正
- Changed Files:
  - `tasks/article-20260307-gpt54.html`（記事HTML）
  - `assets/blog/20260307-gpt54/hero-gpt54.webp`（ヒーロー画像、Geminiマーク除去済み）
  - `assets/blog/20260307-gpt54/img-01-evolution-map.webp`（進化マップ）
  - `assets/blog/20260307-gpt54/img-02-use-cases.webp`（活用シーン）
  - `assets/blog/20260307-gpt54/img-03-steps.webp`（導入3ステップ）
  - `assets/blog/20260307-gpt54/img-04-summary.webp`（要点3行）
  - `tasks/lessons.md`（WPスケジュール / 品質ゲート / rsvg絵文字の教訓追加）
  - `tasks/handoff.md`
- Deploy:
  - WordPress投稿 ID 32 を作成、`future` ステータスで 2026-03-08 04:00 JST に予約
  - ヒーロー画像をメディアライブラリに登録しアイキャッチ設定済み
  - 4枚のインフォグラフィック画像を本番サーバーへ scp（緊急対応: 絵文字黒四角修正）
- Verification:
  - 4画像すべて HTTP 200 確認
  - 投稿 ID 32: `post_status=future`, `post_date=2026-03-08 04:00:00`, `post_date_gmt=2026-03-07 19:00:00`
  - `/`, `/blog/`, `/wp-login.php`, `/favicon.ico` すべて HTTP 200
- Open Items:
  - なし
- Next Action:
  - 2026-03-08 04:00 JST に自動公開されることを確認

