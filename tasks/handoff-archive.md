# Handoff Archive

完了済みエントリのアーカイブ。最新は `handoff.md` を参照。

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

### 2026-03-07 16:14 JST | Agent: Codex
- Task: 本番デプロイ経路を CI-only（GitHub Actions）へ一本化し、運用ルールを統一
- Changed Files: `PROJECT_RULES.md`, `CODEX_ONLY.md`, `tasks/lessons.md`, `tasks/handoff.md`
- Deploy: なし（ルール/運用ドキュメントのみ）
- Verification: OK（CI-only 方針明記 / 例外条件明記）

### 2026-03-07 16:00 JST | Agent: Claude
- Task: モデル名質問の検知範囲を拡大（アイデンティティ質問・具体的モデル名推測に対応）
- Changed Files: `chatbot.php`, `tasks/todo.md`, `tasks/handoff.md`
- Deploy: `chatbot.php` を本番反映（バックアップ作成済み）
- Verification: OK（php -l / ローカル29ケース / live stream / health checks）

### 2026-03-07 15:06 JST | Agent: Codex
- Task: モデル名質問の取りこぼしを解消しつつ、会社名/未掲載情報への誤判定を修正
- Changed Files: `chatbot.php`, `tasks/todo.md`, `tasks/lessons.md`, `tasks/handoff.md`
- Deploy: `chatbot.php` を本番反映（バックアップ作成済み）
- Verification: OK（php -l / local+live テスト / health checks）

### 2026-03-07 14:50 JST | Agent: Codex
- Task: 優先度高の運用リスク対策として CI デプロイ範囲を強化（不要配信防止）
- Changed Files: `.github/workflows/deploy.yml`
- Deploy: なし（CI設定のみ）
- Verification: YAML構文OK、差分確認済み

### 2026-03-07 04:34 JST | Agent: Codex
- Task: 時間帯あいさつをクライアント時刻依存から外し、サーバー時刻（JST）で安定化
- Changed Files: `chatbot.php`
- Deploy: `chatbot.php` を本番反映
- Verification: OK（php -l / live stream / health checks）

### 2026-03-07 04:31 JST | Agent: Codex
- Task: チャットUIをホームページ寄りの白基調へ再調整し、文字の出方を落ち着かせる
- Changed Files: `chatbot.php`
- Deploy: `chatbot.php` を本番反映
- Verification: OK

### 2026-03-07 04:20 JST | Agent: Codex
- Task: チャットUIを空状態ヒーロー付きに刷新し、モデル名質問を `OISHI-OSS` へ統一
- Changed Files: `chatbot.php`
- Deploy: `chatbot.php` を本番反映
- Verification: OK

### 2026-03-06 16:24 JST | Agent: Codex
- Task: チャットヘッダーの `モデル: gpt-oss:20b-cloud` 表示を削除
- Deploy: `chatbot.php` を本番反映
- Verification: OK

### 2026-03-06 16:21 JST | Agent: Codex
- Task: チャット待機表示を3点ドットからトップページ左上と同じロゴへ変更
- Deploy: `chatbot.php` を本番反映
- Verification: OK

### 2026-03-06 16:17 JST | Agent: Codex
- Task: チャット送信後の待機中に3点ドットアニメーションを追加
- Deploy: `chatbot.php` を本番反映
- Verification: OK

### 2026-03-06 16:10 JST | Agent: Codex
- Task: 公開チャットを自由スクロール化し、モック文言を削除し、推論/本文ストリーミング対応へ更新
- Deploy: `chatbot.php` を本番反映
- Verification: OK

### 2026-03-06 15:55 JST | Agent: Codex
- Task: 本番チャットボットを Ollama Cloud 直API で稼働化し、公開URLで実応答確認
- Deploy: `chatbot.php` + `.env` + `.htaccess` を本番反映
- Verification: OK

### 2026-03-06 15:27 JST | Agent: Codex
- Task: 固定応答の `chatbot.php` を Ollama 実接続へ戻し、`gpt-oss:20b-cloud` で動作確認
- Deploy: なし（ローカルのみ）
- Verification: OK

### 2026-03-06 15:05 JST | Agent: Codex
- Task: 画像SEOの全体改善（属性補完・レスポンシブ配信・SNSメタ・構造化データ・画像サイトマップ）
- Deploy: 複数ファイルを本番反映
- Verification: OK

### 2026-03-06 09:40 JST | Agent: Codex
- Task: CLAUDE運用の1,2,3適用（全体正本化・プロジェクト側の役割固定）
- Deploy: なし（ドキュメントのみ）
- Verification: OK

### 2026-03-06 07:30 JST | Agent: Codex
- Task: 運用シンプル化の基盤作成（共通ルール一本化）
- Deploy: なし（ドキュメントのみ）
- Verification: OK
