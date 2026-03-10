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

### 2026-03-10 11:20 JST | Agent: Codex
- Task: テーマの render-blocking を削減するため、Google Fonts 依存と WordPress の不要な `global-styles` / head 出力を整理
- Changed Files:
  - `functions.php`
  - `style.css`
  - `assets/css/chatbot.css`
  - `home.php`
  - `index.php`
  - `page-portfolio.php`
  - `page-contact.php`
  - `single.php`
  - `chatbot.php`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo.md`
  - `tasks/todo-archive.md`
- Deploy:
  - GitHub push: `e6b08a8` -> `master`
  - GitHub Actions: `Deploy to Xserver` run `22883981292` が `success`
- Verification:
  - local:
    - `php -l functions.php home.php index.php page-portfolio.php page-contact.php single.php chatbot.php` OK
    - `rg 'fonts.googleapis.com|fonts.gstatic.com|Inter:wght@|"Inter"'` でリポジトリ内の外部 Inter 読み込みが消えたことを確認
  - live:
    - `/` `HTTP 200`
    - `/blog/` `HTTP 200`
    - `/wp-login.php` `HTTP 200`
    - `/favicon.ico` `HTTP 200`
    - homepage HTML から `fonts.googleapis.com` / `fonts.gstatic.com` / `global-styles-inline-css` / `rel="https://api.w.org/"` が消えていることを確認
    - `/blog/` HTML からも `fonts.googleapis.com` / `global-styles-inline-css` / `rel="https://api.w.org/"` が消えていることを確認
    - `chatbot.php` HTML から `fonts.googleapis.com` / `"Inter"` / `global-styles` が消えていることを確認
    - `curl -w` の単発計測で homepage `ttfb=0.658083 total=0.658370 size=16682` を確認
  - note:
    - 変更前に同じ方法で見た homepage は `ttfb=1.339785 total=1.340211 size=25864` だったため、今回の削減で head 出力と初回応答は改善
    - Google PageSpeed API は再取得時に `429` を返したため、Lighthouse スコアの再採点値までは未取得
- Open Items:
  - なし
- Next Action:
  - なし

### 2026-03-09 19:27 JST | Agent: Codex
- Task: 検索結果 favicon 改善のため explicit `16x16` / `32x32` PNG favicon を追加し、live 配信と head tag を検証
- Changed Files:
  - `functions.php`
  - `tasks/scripts/build_favicon_set.py`
  - `apple-touch-icon.png`
  - `site-icon-192.png`
  - `favicon-16x16.png`
  - `favicon-32x32.png`
  - `tasks/favicon-inventory.md`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo.md`
  - `tasks/todo-archive.md`
- Deploy:
  - GitHub push: `855efad` -> `master`
  - GitHub Actions: `Deploy to Xserver` run `22848915458` が `success`
- Verification:
  - local:
    - `php -l functions.php` OK
    - `file favicon-16x16.png favicon-32x32.png favicon.ico` で `16x16` / `32x32` PNG と multi-size ICO を確認
    - `md5 -q favicon-32x32.png` = `7d3efa92561b8bac60ceae9d50556bef`
    - `md5 -q favicon-16x16.png` = `c46159f1dca280aec8594a8550f4627a`
  - live:
    - `/` `HTTP 200`
    - `/blog/` `HTTP 200`
    - `/wp-login.php` `HTTP 200`
    - `/favicon.ico` `HTTP 200`
    - `/wp-content/themes/oishi-ai/favicon-32x32.png` `HTTP 200`
    - `/wp-content/themes/oishi-ai/favicon-16x16.png` `HTTP 200`
    - homepage HTML に `rel="icon" sizes="32x32"` / `rel="icon" sizes="16x16"` / existing `512x512` / `192x192` / `apple-touch-icon` を確認
    - live `favicon-32x32.png` / `favicon-16x16.png` の md5 が local と一致
  - note:
    - live site は既に WordPress `W` ではなくテーマ側 favicon を返していたため、検索結果の `W` は Google favicon cache の残存が主因と判断
    - 今回の修正で crawler 向けに explicit `16x16` / `32x32` favicon を追加し、site 側で渡せる signal は強化済み
- Open Items:
  - なし
- Next Action:
  - なし

### 2026-03-08 20:13 JST | Agent: Codex
- Task: 相談整理 agent が番号付きの一括回答を1項目目しか受け取らず聞き返していたため、複数項目をまとめて吸い上げるよう修正
- Changed Files:
  - `inc/chatbot/consultation-agent.php`
  - `tasks/lessons.md`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/todo.md`
  - `tasks/todo-archive.md`
- Deploy:
  - GitHub push: `115dfc8` -> `master`
  - GitHub Actions: `Deploy to Xserver` run `22819588845` が `success`
- Verification:
  - local:
    - `php -l chatbot.php` / `inc/chatbot/core.php` / `inc/chatbot/consultation-agent.php` OK
    - セッション付きローカルモックで `AI導入の相談です。3分診断を始めてください。` の後に `1.小売りで年商5000万ほど 2.在庫管理と売上管理 3.Excelのみ 4.完全自動化したい` を送ると、追加質問なしで要約へ進むことを確認
    - `1.小売業 / 5名 3.Excelのみ 4.自動化したい` のように一部欠けた回答では、未回答の `今いちばん重い業務や課題` だけを聞くことを確認
    - `業種: 小売業 / 5名 課題: 在庫管理と売上管理 ツール: Excelのみ 目標: 完全自動化したい` でも一括吸い上げできることを確認
    - `PoCの最初の2ステップを短く教えて` は override されず `null`
  - live:
    - `/` `HTTP 200`
    - `/blog/` `HTTP 200`
    - `/wp-login.php` `HTTP 200`
    - `/favicon.ico` `HTTP 200`
    - live stream `POST ...chatbot.php?stream=1`:
      - 開始後に `1.小売りで年商5000万ほど 2.在庫管理と売上管理 3.Excelのみ 4.完全自動化したい` -> `定型業務の自動化` を提案する要約をその場で返す
      - 開始後に `1.小売業 / 5名 3.Excelのみ 4.自動化したい` -> 未回答の `今いちばん重い業務や課題` だけを聞く
- Open Items:
  - なし
- Next Action:
  - なし
