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

### 2026-03-12 04:05 JST | Agent: Codex
- Task: Claude Code 記事向け X 投稿 3 本の結果確認と、今夜用の臨時 cron を通常運用へ戻す
- Changed Files:
  - `.github/workflows/x-post.yml`
  - `tasks/todo.md`
  - `tasks/handoff.md`
- Deploy:
  - GitHub push: pending
  - GitHub Actions:
    - manual rerun `22952737185`: `id=26` は `403 Forbidden / You are not permitted to perform this action.`
    - scheduled run `22962122968`: `id=27` / `id=28` を自動投稿成功
    - scheduled run `22966084229`: `Done. 0 tweet(s) processed.`
- Verification:
  - `gh run view 22962122968 --log` で `Posted id=27 -> tweet 2031763562948174126` と `Posted id=28 -> tweet 2031763564105793607` を確認
  - local `tasks/x-content-queue.json` を `git pull` 後に確認し、`id=27,28` が `status=posted`・`tweet_id`・`posted_at` 付きで反映されていることを確認
  - `id=26` はユーザー手動投稿済みのため queue 上の `error` をそのまま残す
- Open Items:
  - `id=26` の queue 履歴は `error` のまま残っているが、実運用上は手動投稿済み
- Next Action:
  - なし

### 2026-03-11 21:10 JST | Agent: Codex
- Task: 今夜の Claude Code 記事向け X 投稿 3 本の自動投稿失敗を調査し、再実行まで実施
- Changed Files:
  - `tasks/x-content-queue.json`
- Deploy:
  - GitHub push: `1e7fe6a` -> `master`
  - GitHub Actions: `X Auto Post` manual run `22951851894`
- Verification:
  - `gh run view 22951115151 --log` で `id=26` の `create_tweet` が `403 Forbidden / You are not permitted to perform this action.` で失敗することを確認
  - ユーザー提供の X API credentials を GitHub Secrets に更新後、`id=26` を `pending` に戻して manual rerun
  - `gh run view 22951851894 --log` でも同じ `403 Forbidden / You are not permitted to perform this action.` を再確認
  - local `tasks/x-content-queue.json` は `id=26=error`, `id=27,28=pending`
- Open Items:
  - X App 側の permission が `Read only` のままの可能性が高く、`Read and write` への変更と Access Token / Access Token Secret の再発行が必要
  - 権限更新前は `22:00 / 23:30 JST` 分も同様に失敗する見込み
- Next Action:
  - ユーザーが X Developer Portal で `Read and write` に変更し、新しい token を GitHub Secrets へ反映後、`id=26` を再度 `pending` に戻して workflow を手動再実行する

### 2026-03-11 19:34 JST | Agent: Codex
- Task: Claude Code 記事に合わせた X 投稿 3 本を作成し、今夜 `20:30 / 22:00 / 23:30 JST` の自動投稿キューへ登録
- Changed Files:
  - `.github/workflows/x-post.yml`
  - `tasks/x-content-queue.json`
  - `tasks/todo.md`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
- Deploy:
  - GitHub push: `af81ad9` -> `master`
  - GitHub Actions: 予約時刻前のため実投稿 run は未確認
- Verification:
  - local:
    - `python3 -m json.tool tasks/x-content-queue.json` OK
    - queue に `id=26,27,28` の `pending` 3件、時刻 `2026-03-11T20:30:00+09:00` / `22:00:00+09:00` / `23:30:00+09:00` を確認
    - `.github/workflows/x-post.yml` に UTC cron `11:30 / 13:00 / 14:30` を追加し、JST `20:30 / 22:00 / 23:30` に対応することを確認
- Open Items:
  - 実際の X 投稿成功確認は各時刻後に必要
  - `x-post.yml` は今夜対応のため 1日5回実行になっている。恒久運用にするか後で戻すかを決める必要あり
- Next Action:
  - `2026-03-11 20:30 JST` 以降、GitHub Actions run と `tasks/x-content-queue.json` の `status` が `posted` へ変わることを確認し、必要なら cron を整理する

### 2026-03-11 17:23 JST | Agent: Codex
- Task: Claude Code 完全ガイド記事を作成し、ユーザー支給のアイキャッチ画像を設定して WordPress 本番へ公開
- Changed Files:
  - `tasks/todo.md`
  - `tasks/lessons.md`
  - `tasks/handoff.md`
  - `tasks/handoff-archive.md`
  - `tasks/article-20260311-claude-code-guide.html`（作業用草稿、`.gitignore` 対象）
- Deploy:
  - GitHub push なし（テーマコード変更なし）
  - WordPress 投稿 ID `50` を `publish` で作成
  - メディア ID `49` としてアイキャッチ画像を登録し、投稿 ID `50` の `_thumbnail_id` に設定
- Verification:
  - local:
    - `wc -m tasks/article-20260311-claude-code-guide.html` = `16716`
    - 記事草稿に `目次`、`参照元`、`about-table-wrap` の表ラッパー 3 か所を確認
  - live:
    - `https://www.oishillc.jp/2026/03/11/claude-code-complete-guide-for-beginners/` `HTTP 200`
    - `/blog/` に当該記事の掲載を確認
    - live article HTML に `og:image` / `twitter:image` として `wp-content/uploads/2026/03/名称未設定のデザイン-4-scaled.png` が入っていることを確認
    - アイキャッチ画像 URL `https://www.oishillc.jp/wp-content/uploads/2026/03/%E5%90%8D%E7%A7%B0%E6%9C%AA%E8%A8%AD%E5%AE%9A%E3%81%AE%E3%83%87%E3%82%B6%E3%82%A4%E3%83%B3-4-scaled.png` `HTTP 200`
- Open Items:
  - `tasks/article-20260311-claude-code-guide.html` は `.gitignore` 対象のため、Git には載っていない
  - 画像ファイル名が WordPress 既定の日本語名のままなので、必要なら後日メディア整理を検討
- Next Action:
  - 必要ならこの記事から X 投稿 3 本（ブログ誘導 / トレンド要点整理 / 実務転用）を作成

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
