# Handoff Log

このファイルは Codex / Claude の引き継ぎ専用です。
新しいエントリを先頭に追記してください。

## Template

### YYYY-MM-DD HH:MM JST | Agent: Codex or Claude
- Task:
- Changed Files:
- Deploy:
- Verification:
- Open Items:
- Next Action:

---

### 2026-03-06 16:24 JST | Agent: Codex
- Task: チャットヘッダーの `モデル: gpt-oss:20b-cloud` 表示を削除
- Changed Files:
  - `chatbot.php`
  - `tasks/todo.md`
  - `tasks/handoff.md`
- Deploy:
  - 本番事前確認: `ssh xserver "head -30 ~/oishillc.jp/public_html/wp-content/themes/oishi-ai/chatbot.php"`
  - 本番バックアップ作成: `~/oishillc.jp/backups/20260306-chatbot-streaming/chatbot-hide-model-label.php`
  - `chatbot.php` を `~/oishillc.jp/public_html/wp-content/themes/oishi-ai/` へ反映
- Verification:
  - `php -l chatbot.php` OK
  - live chatbot HTML に `OISHI AI` は残り、`モデル:` と `active-model` は存在しないことを確認
  - `/`, `/blog/`, `/wp-login.php`, `/favicon.ico` はすべて `HTTP 200`
- Open Items:
  - なし
- Next Action:
  - 必要ならヘッダー余白やタイトルサイズをUI全体に合わせて微調整

### 2026-03-06 16:21 JST | Agent: Codex
- Task: チャット待機表示を3点ドットからトップページ左上と同じロゴへ変更
- Changed Files:
  - `chatbot.php`
  - `tasks/todo.md`
  - `tasks/handoff.md`
- Deploy:
  - 本番事前確認: `ssh xserver "head -30 ~/oishillc.jp/public_html/wp-content/themes/oishi-ai/chatbot.php"`
  - 本番バックアップ作成: `~/oishillc.jp/backups/20260306-chatbot-streaming/chatbot-logo-loading-indicator.php`
  - `chatbot.php` を `~/oishillc.jp/public_html/wp-content/themes/oishi-ai/` へ反映
- Verification:
  - `php -l chatbot.php` OK
  - live chatbot HTML に `typing-indicator-logo` と `logo-56.png` / `logo-112.png` を確認
  - homepage HTML のヘッダーロゴも同じ `logo-56.png` / `logo-112.png` 系を参照していることを確認
  - `/`, `/blog/`, `/wp-login.php`, `/favicon.ico` はすべて `HTTP 200`
- Open Items:
  - アニメーションの見え方そのものはブラウザ実画面での最終微調整余地あり
- Next Action:
  - 必要ならロゴの揺れ幅、速度、透明度をブランドトーンに合わせて詰める

### 2026-03-06 16:17 JST | Agent: Codex
- Task: チャット送信後の待機中に3点ドットアニメーションを追加
- Changed Files:
  - `chatbot.php`
  - `tasks/todo.md`
  - `tasks/handoff.md`
- Deploy:
  - 本番事前確認: `ssh xserver "head -30 ~/oishillc.jp/public_html/wp-content/themes/oishi-ai/chatbot.php"`
  - 本番バックアップ作成: `~/oishillc.jp/backups/20260306-chatbot-streaming/chatbot-typing-indicator.php`
  - `chatbot.php` を `~/oishillc.jp/public_html/wp-content/themes/oishi-ai/` へ反映
- Verification:
  - `php -l chatbot.php` OK
  - live HTML に `typing-indicator` / `is-waiting` / `typing-dot` を確認
  - `/`, `/blog/`, `/wp-login.php`, `/favicon.ico` はすべて `HTTP 200`
- Open Items:
  - アニメーション自体の視覚確認はブラウザ実画面前提
- Next Action:
  - 必要なら待機インジケータの速度、色、位置をUIトーンに合わせて微調整

### 2026-03-06 16:10 JST | Agent: Codex
- Task: 公開チャットを自由スクロール化し、モック文言を削除し、推論/本文ストリーミング対応へ更新
- Changed Files:
  - `chatbot.php`
  - `tasks/todo.md`
  - `tasks/handoff.md`
- Deploy:
  - 本番事前確認: `ssh xserver "head -40 ~/oishillc.jp/public_html/wp-content/themes/oishi-ai/chatbot.php"`
  - 本番バックアップ作成: `~/oishillc.jp/backups/20260306-chatbot-streaming/chatbot.php`
  - `chatbot.php` を `~/oishillc.jp/public_html/wp-content/themes/oishi-ai/` へ反映
- Verification:
  - `php -l chatbot.php` OK
  - `php -S 127.0.0.1:8090 -t .` + local `curl` GET で旧モック文言なしを確認
  - local `POST /chatbot.php?stream=1` で `reasoning -> content -> done` を確認
  - live `GET https://www.oishillc.jp/wp-content/themes/oishi-ai/chatbot.php` で `OISHI AI` / `モデル: gpt-oss:20b-cloud` / 旧文言なしを確認
  - live `POST https://www.oishillc.jp/wp-content/themes/oishi-ai/chatbot.php?stream=1` で推論と本文のストリーミングを確認
  - `https://www.oishillc.jp/wp-content/themes/oishi-ai/.env` は `HTTP 403`
  - `/`, `/blog/`, `/wp-login.php`, `/favicon.ico` はすべて `HTTP 200`
- Open Items:
  - モデルの `reasoning` は現状そのまま可視化しているため、英語混在や詳細度はモデル依存
- Next Action:
  - 必要なら推論表示のON/OFF切替、会話永続化、または送客CTA連携を追加

### 2026-03-06 15:55 JST | Agent: Codex
- Task: 本番チャットボットを Ollama Cloud 直API で稼働化し、公開URLで実応答確認
- Changed Files:
  - `chatbot.php`
  - `.env.example`
  - `.htaccess`
  - `tasks/todo.md`
  - `tasks/handoff.md`
  - `tasks/lessons.md`
- Deploy:
  - 本番バックアップ作成: `~/oishillc.jp/backups/20260306-chatbot-ollama/`
  - `chatbot.php` を `~/oishillc.jp/public_html/wp-content/themes/oishi-ai/` へ反映
  - `.env` を
    - `~/oishillc.jp/.oishi-ai-chatbot.env`
    - `~/oishillc.jp/public_html/wp-content/themes/oishi-ai/.env`
    に配置
  - `.htaccess` を `~/oishillc.jp/public_html/wp-content/themes/oishi-ai/.htaccess` へ配置
- Verification:
  - `https://www.oishillc.jp/wp-content/themes/oishi-ai/chatbot.php` の初期表示で `モデル: gpt-oss:20b-cloud`
  - live POST でAI本文を確認
  - `https://www.oishillc.jp/wp-content/themes/oishi-ai/.env` は `HTTP 403`
  - `/`, `/blog/`, `/wp-login.php`, `/favicon.ico` はすべて `HTTP 200`
- Open Items:
  - サーバーにはユーザー権限の Ollama 0.17.6 も展開済みだが、公開チャットは現在 `https://ollama.com/v1` を使うため必須ではない
- Next Action:
  - 必要ならチャットUI改善、会話ログ保存、レート制御、または問い合わせ導線への接続を追加

### 2026-03-06 15:27 JST | Agent: Codex
- Task: 固定応答の `chatbot.php` を Ollama 実接続へ戻し、`gpt-oss:20b-cloud` で動作確認
- Changed Files:
  - `chatbot.php`
  - `.gitignore`
  - `.env.example`
  - `.htaccess`
  - `tasks/todo.md`
  - `tasks/lessons.md`
- Deploy:
  - なし（ローカル実装とローカル検証のみ）
- Verification:
  - `php -l chatbot.php` OK
  - `curl http://127.0.0.1:11434/v1/chat/completions` で `gpt-oss:20b-cloud` の応答を確認
  - `php -S 127.0.0.1:8090 -t .` + `curl -L --data-urlencode 'message=...' http://127.0.0.1:8090/chatbot.php` で HTML上のAI応答を確認
  - `curl http://127.0.0.1:11434/api/tags` で `gpt-oss:20b-cloud` 登録を確認
- Open Items:
  - 本番サーバーで使う場合は、そのサーバー自身で Ollama daemon と outbound network が必要
  - `.env` をテーマ直下に置く運用は `.htaccess` 前提なので、より安全にするなら本番では `CHATBOT_ENV_FILE` で公開外パスを使う
- Next Action:
  - 必要なら本番サーバー側の Ollama 接続可否を確認してデプロイ/検証へ進む

### 2026-03-06 15:05 JST | Agent: Codex
- Task: 画像SEOの全体改善（属性補完・レスポンシブ配信・SNSメタ・構造化データ・画像サイトマップ）
- Changed Files:
  - `functions.php`
  - `index.php`
  - `home.php`
  - `single.php`
  - `page-contact.php`
  - `page-portfolio.php`
  - `logo-56.png`
  - `logo-112.png`
  - `assets/services/service-01-strategy-800.webp`
  - `assets/services/service-02-custom-dev-800.webp`
  - `assets/services/service-03-agent-800.webp`
  - `assets/services/service-04-automation-800.webp`
  - `assets/services/service-05-training-800.webp`
  - `assets/services/service-06-diagnosis-800.webp`
  - `assets/services/service-07-integration-800.webp`
  - `assets/blog/20260306-poc-roadmap/img-01-poc-trap-to-roadmap-768.webp`
  - `assets/blog/20260306-poc-roadmap/img-02-90day-steps-768.webp`
  - `assets/blog/20260306-poc-roadmap/img-03-governance-kpi-768.webp`
- Deploy:
  - 本番バックアップ作成: `~/oishillc.jp/backups/20260306-image-seo/`
  - 上記ファイルを `~/oishillc.jp/public_html/wp-content/themes/oishi-ai/` へ反映済み
- Verification:
  - `/`, `/blog/`, `/contact/`, `/portfolio/`, 各記事ページで `img` の `alt/width/height/loading/srcset` 欠落が 0
  - `og:image`, `twitter:image`, `application/ld+json`（記事）出力を確認
  - `https://www.oishillc.jp/image-sitemap.xml` 生成確認
  - `robots.txt` に `Sitemap: https://www.oishillc.jp/image-sitemap.xml` を追加確認
  - `/wp-login.php` の favicon 出力と `/favicon.ico` HTTP 200 を再確認
- Open Items:
  - なし（今回の監査対象では未検出）
- Next Action:
  - 新規記事画像を追加する際は、同名 `-768.webp` などの縮小版を同時作成する運用を継続

### 2026-03-06 09:40 JST | Agent: Codex
- Task: CLAUDE運用の1,2,3適用（全体正本化・プロジェクト側の役割固定）
- Changed Files:
  - `/Users/eisukeoishi/CLAUDE.md`
  - `CLAUDE.md`
- Deploy: なし（運用ドキュメント変更のみ）
- Verification:
  - 全体 `CLAUDE.md` に「Source of Truth」明記を追加
  - プロジェクト `CLAUDE.md` に「接続情報専用 + 全体参照」明記を追加
- Open Items:
  - なし
- Next Action:
  - 今後の方針変更は `/Users/eisukeoishi/CLAUDE.md` だけを更新する

### 2026-03-06 07:30 JST | Agent: Codex
- Task: 運用シンプル化の基盤作成（共通ルール一本化）
- Changed Files:
  - `PROJECT_RULES.md`（新規）
  - `CLAUDE.md`（簡素化）
  - `CODEX_ONLY.md`（簡素化・再作成）
  - `AGENTS.md`（起動順の更新）
  - `tasks/handoff.md`（新規）
- Deploy: なし（運用ドキュメント変更のみ）
- Verification:
  - 本番主要テーマファイルはローカルとハッシュ一致
  - `/` `/blog/` `/wp-login.php` `/favicon.ico` は表示・応答正常
- Open Items:
  - 未コミットファイルが多く、履歴固定は未完了
  - favicon以外の資産（services/blog/hero）の運用台帳は未整備
- Next Action:
  - 未コミット資産を `commit対象 / 保管のみ / 削除候補` に3分類して確定
