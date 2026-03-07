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

### 2026-03-08 07:06 JST | Agent: Codex
- Task: チャットボットに相談整理エージェントの最小MVPを追加し、AI関連ニュースの誤ルーティングを修正
- Changed Files:
  - `chatbot.php`
  - `inc/chatbot/core.php`
  - `inc/chatbot/consultation-agent.php`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo.md`
  - `tasks/todo-archive.md`
- Deploy:
  - GitHub push: `f13bc9b` -> `master`
  - GitHub Actions: `Deploy to Xserver` run `22808159619` が `success`
- Verification:
  - local:
    - `php -l chatbot.php` / `inc/chatbot/core.php` / `inc/chatbot/consultation-agent.php` OK
    - セッションを持ったローカルモックで `AI導入の相談です。3分診断を始めてください。` -> 4問ヒアリング開始 -> 要約 -> `お願いします` で問い合わせ文下書き生成を確認
    - `中断` で `chatbot_agent_state` が cleared されることを確認
    - `PoCの進め方を短く教えて` は override されず `null`
    - `resolveNewsIntent("AI関連の最新のニュースを教えて")` が `label=最新のAI関連ニュース` を返すことを確認
    - `php chatbot.php | rg` で `3分診断を始める` / `業務自動化の相談です。ヒアリングを始めてください。` / `AI導入、導入診断、ブログ、最新情報、お問い合わせまでそのまま相談できます。` を確認
  - live:
    - `/` `HTTP 200`
    - `/blog/` `HTTP 200`
    - `/wp-login.php` `HTTP 200`
    - `/favicon.ico` `HTTP 200`
    - `https://www.oishillc.jp/wp-content/themes/oishi-ai/chatbot.php` HTML に `3分診断を始める` / `業務自動化を相談` / `AI導入、導入診断、ブログ、最新情報、お問い合わせまでそのまま相談できます。` を確認
    - live stream `POST ...chatbot.php?stream=1`:
      - `AI導入の相談です。3分診断を始めてください。` -> 4問ヒアリング開始
      - 同一セッションで `製造業 / 20名` -> 課題ヒアリングへ進行
      - 同一セッションで `問い合わせ対応に時間がかかる` / `Gmail とスプレッドシート` / `3か月以内に一次回答を半自動化したい` -> `問い合わせ対応の自動化` を提案する要約を返す
      - 同一セッションで `お願いします` -> 問い合わせ文下書きを返す
      - 新規セッションで `AI関連の最新のニュースを教えて` -> `最新のAI関連ニュースです` と Google News 3件を返す
      - 新規セッションで `PoCの最初の2ステップを短く教えて` -> agent ではなく通常のPoC回答を返す
- Open Items:
  - なし
- Next Action:
  - なし

### 2026-03-08 04:02 JST | Agent: Codex
- Task: `GPT-5.4` 予約投稿（ID 32）の自動公開と公開URL / 画像アセットを確認し、todo をクローズ
- Changed Files:
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo.md`
  - `tasks/todo-archive.md`
- Deploy:
  - なし（WordPress の予約公開確認と運用ログ更新のみ）
- Verification:
  - WordPress REST API `posts/32` で `status=publish`、`slug=gpt-5-4-guide-for-business`、`link=https://www.oishillc.jp/2026/03/08/gpt-5-4-guide-for-business/` を確認
  - `/blog/` に `GPT-5.4は”使えるAI”になったのか？経営者が知っておくべき全体像` の掲載を確認
  - live article `https://www.oishillc.jp/2026/03/08/gpt-5-4-guide-for-business/` `HTTP 200`
  - live article HTML に `hero-gpt54.webp` / `img-01-evolution-map.webp` / `img-02-use-cases.webp` / `img-03-steps.webp` / `img-04-summary.webp` 参照を確認
  - asset checks:
    - `/wp-content/themes/oishi-ai/assets/blog/20260307-gpt54/hero-gpt54.webp` `HTTP 200`
    - `/wp-content/themes/oishi-ai/assets/blog/20260307-gpt54/img-03-steps.webp` `HTTP 200`
- Open Items:
  - なし
- Next Action:
  - なし

### 2026-03-08 03:59 JST | Agent: Codex
- Task: チャットボットに live-info レイヤー（天気 / 最新ニュース）と下部ガード文言を追加し、一般質問を会社情報制約から分離
- Changed Files:
  - `chatbot.php`
  - `inc/chatbot/core.php`
  - `assets/css/chatbot.css`
  - `tasks/lessons.md`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo-archive.md`
- Deploy:
  - GitHub push: `7832468` -> `master`
  - GitHub Actions: `Deploy to Xserver` run `22805095650` が `success`
  - GitHub push: `dcb9cf1` -> `master`
  - GitHub Actions: `Deploy to Xserver` run `22805183984` が `success`
- Verification:
  - local:
    - `php -l chatbot.php` OK
    - `php -l inc/chatbot/core.php` OK
    - `buildAssistantOverride("天気を教えて")` が地域確認を返すことを確認
    - `buildAssistantOverride("最新のAI事情を教えて")` がモデル名応答ではなく live-info 判定に入ることを確認
    - `buildChatRequestMessages(...)` の system prompt 件数が `1` で、ホームページ facts の常時付与を外したことを確認
    - `php chatbot.php | rg` で composer 文言と `AIは間違えることがあります` のガード文言を確認
    - sandbox 内の PHP cURL は外部 DNS 解決に失敗したため、最新情報の取得そのものは live 側で確認
  - live:
    - `/` `HTTP 200`
    - `/blog/` `HTTP 200`
    - `/wp-login.php` `HTTP 200`
    - `/favicon.ico` `HTTP 200`
    - live HTML に `assets/css/chatbot.css?v=1772909578`、`AI導入、ブログ、最新情報、お問い合わせまでそのまま相談できます。`、`AIは間違えることがあります。...` を確認
    - live stream `POST https://www.oishillc.jp/wp-content/themes/oishi-ai/chatbot.php?stream=1`:
      - `御社の概要を教えて` -> 会社概要の固定応答
      - `東京の天気を教えて` -> `東京都, 日本` の天気を Open-Meteo 取得結果で返す（warning 混入なし）
      - `今日のニュースを教えて` -> Google News の主要ニュース3件
      - `最新のAI事情を教えて` -> Google News のAI関連ニュース3件
- Open Items:
  - `GPT-5.4` 予約投稿（ID 32）の自動公開確認は未実施。現在時刻は `2026-03-08 03:59 JST` で、予定公開時刻 `2026-03-08 04:00 JST` 前
- Next Action:
  - `2026-03-08 04:00 JST` 以降に `/blog/` と該当記事URLで `GPT-5.4` 記事の公開を確認し、必要なら `tasks/handoff.md` を更新
