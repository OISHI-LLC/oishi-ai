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
