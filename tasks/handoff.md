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

### 2026-03-07 16:14 JST | Agent: Codex
- Task: 本番デプロイ経路を CI-only（GitHub Actions）へ一本化し、運用ルールを統一
- Changed Files:
  - `PROJECT_RULES.md`
  - `CODEX_ONLY.md`
  - `tasks/lessons.md`
  - `tasks/handoff.md`
- Deploy:
  - なし（ルール/運用ドキュメントのみ）
- Verification:
  - `PROJECT_RULES.md` に CI-only / 手動反映は緊急時のみ を明記済み
  - `CODEX_ONLY.md` に同一ポリシーを明記済み
  - `tasks/lessons.md` の Deploy Safety を CI-only 方針へ更新済み
- Open Items:
  - なし
- Next Action:
  - 今後のデプロイ実施時は GitHub Actions の実行結果（成功/失敗）を `tasks/handoff.md` に記録する

