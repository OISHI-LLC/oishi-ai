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

### 2026-03-14 06:02 JST | Agent: Claude
- Task: 「ひとりAIチーム」の作り方 記事・画像アセット作成 → WP投稿 → デプロイ
- Changed Files:
  - `tasks/article-20260314-one-person-ai-team.html`（記事HTML新規作成、12,497文字）
  - `assets/blog/20260314-one-person-ai-team/hero-one-person-ai-team.webp`（アイキャッチ画像）
  - `assets/blog/20260314-one-person-ai-team/img-01-labor-shortage.{svg,png,webp}`（人手不足グラフ）
  - `assets/blog/20260314-one-person-ai-team/img-02-cost-comparison.{svg,png,webp}`（雇用vsAIコスト比較）
  - `assets/blog/20260314-one-person-ai-team/img-03-tool-guide.{svg,png,webp}`（業務別ツールガイド）
  - `assets/blog/20260314-one-person-ai-team/img-04-roadmap.{svg,png,webp}`（3ステップロードマップ）
  - `tasks/handoff.md`（本エントリ追加）
- Deploy:
  - WP: `wp post create` → ID=67（下書き）、`wp post update 67` で本文投入、Yoast SEOメタ設定
  - GitHub push: `79f43ae` → `master`
  - GitHub Actions: `Deploy to Xserver` run `23070387294`: `success`
- Verification:
  - 画像: SVG 4枚を rsvg-convert → cwebp で変換後、全数Readツールで目視確認。img-02/03/04にテキスト重なり・切れを発見→修正→再変換→再確認OK
  - 記事: HTML構文エラーなし、文字数12,497、SEO全項目（タイトル・メタ・見出し階層・内部リンク3件・構造化データ・OGP）設定済み
  - live:
    - `/` `/blog/` `/wp-login.php` `/favicon.ico` すべて HTTP 200
    - 画像5枚（hero + img-01〜04）すべて HTTP 200
  - ファクトチェック: 人手不足倒産427件（帝国データバンク）、AI導入率23.4%（総務省）、採用コスト103.3万円（リクルート）、パナソニック18.6万時間削減 — 全数確認済み
- Open Items:
  - なし（Codexが06:09 JSTに公開済み）
- Next Action:
  - X投稿3本の作成・キュー登録（型1:ブログ宣伝 / 型2or3を2本）

---

### 2026-03-14 06:09 JST | Agent: Codex
- Task: WordPress の最新下書き記事を本番公開
- Changed Files:
  - `tasks/todo.md`（公開タスク記録・Review 追記）
  - `tasks/handoff.md`（本エントリ追加）
- Deploy:
  - なし（WordPress 本番記事の公開操作のみ）
- Verification:
  - server:
    - `wp post list --post_status=draft` で下書きは `ID=67` の 1 件のみ確認
    - `wp post update 67 --post_status=publish` 実行成功
    - `wp post get 67` で `post_status=publish`、`post_name=one-person-ai-team` を確認
    - `wp post url 67` で `https://www.oishillc.jp/2026/03/14/one-person-ai-team/` を確認
  - live:
    - 記事 URL `https://www.oishillc.jp/2026/03/14/one-person-ai-team/` `HTTP 200`
- Open Items:
  - なし
- Next Action:
  - なし

---

### 2026-03-13 21:00 JST | Agent: Claude
- Task: 3/13 AI コーディングツール比較記事向け X 投稿 3 本の予約投稿セットアップ、未コミットローカル変更の整理・デプロイ・検証
- Changed Files:
  - `.github/workflows/x-post.yml`（cron を 20:50/21:55/23:23 JST に設定）
  - `tasks/x-content-queue.json`（id=32,33,34 追加、スケジュール更新、id=30,31 をリモート投稿済みに同期、JSON エスケープ修正）
  - `single.php`（アイキャッチ画像表示追加 — サーバーには既に反映済みだったが Git 未コミット）
  - `style.css`（`.blog-header h1` に `padding-bottom: 20px` + `border-bottom: 2px solid var(--border)` 追加）
  - `PROJECT_RULES.md`（コンテンツ品質基準セクション追加）
  - `tasks/lessons.md`（アイキャッチ CSS、コンテンツ方針、X 運用、API 権限の教訓追加）
  - `tasks/todo.md`（完了タスク・Review 削除、ルール準拠に整理）
  - `tasks/handoff.md`（本エントリ追加、3 件ルールに従いアーカイブ移動）
  - `tasks/handoff-archive.md`（旧エントリ移動）
  - `assets/blog/20260309-ai-divide/` 〜 `assets/blog/20260313-ai-coding-tools-comparison/`（過去記事の画像アセット一括コミット）
  - `assets/twitter/20260313/*.svg`（ツイート画像ソース）
  - `assets/blog/20260307-gpt54/hero-gpt54.webp`（画像更新）
- Deploy:
  - GitHub push: `e20a4a5` → `d4f8ebb` → `56da7e3` → `master`
  - GitHub Actions:
    - `Deploy to Xserver` run `23049473596`: `success`
    - `X Auto Post` cron: 20:50 JST (`50 11 13 3 *`) / 21:55 JST (`55 12 13 3 *`) / 23:23 JST (`23 14 13 3 *`)
- Verification:
  - live:
    - `/` `HTTP 200`
    - `/blog/` `HTTP 200`
    - `/wp-login.php` `HTTP 200`
    - `/favicon.ico` `HTTP 200`
    - 記事 `https://www.oishillc.jp/2026/03/13/claude-code-vs-cursor-vs-github-copilot-comparison-2026/` `HTTP 200`
    - `og:image` / `twitter:image` に `tmp-hero-upload-scaled.webp` 設定確認
    - SEO title: `Claude Code vs Cursor vs GitHub Copilot 徹底比較【2026年最新】あなたに合うAIコーディングツールは？`
    - meta description 設定確認
    - `blog-featured-img` アイキャッチ表示確認
    - サーバー `style.css` に `padding-bottom: 20px` / `border-bottom: 2px solid var(--border)` 反映確認
  - ファクトチェック（X 投稿 3 本）:
    - id=32（ブログ宣伝）: 記事構成・URL・キーワード整合 OK
    - id=33（コスト vs 投資）: 数値計算（15h/40h/25h/¥25,000）すべて正確、記事セクション一致 OK
    - id=34（満足度データ）: Claude Code 46% / Cursor 19% / Copilot 9% — 記事テーブルと完全一致 OK
  - JSON: `python3 -c "import json; json.load(...)"` パース OK
  - `php -l single.php` 構文 OK
- Open Items:
  - X 投稿 3 本は予約時刻（20:50/21:55/23:23 JST）に自動実行待ち。投稿後に `x-content-queue.json` が `posted` に更新されることを確認する
  - 昨日分の id=30,31 は投稿済みだがローカル queue がリモートより古かった問題を rebase で解消済み
- Next Action:
  - 20:50 JST 以降に `gh run list --workflow=x-post.yml` で各 cron run の成功を確認する
  - 投稿失敗時は `workflow_dispatch` で手動再実行する

---

### 2026-03-13 11:45 JST | Agent: Codex
- Task: ブログ記事詳細ページのアイキャッチ画像が大きすぎる表示崩れを修正
- Changed Files:
  - `style.css`（`.blog-featured-img` / `.blog-featured-img img` 追加: `max-width: 760px`・中央配置・画像幅100%）
  - `tasks/handoff.md`
- Deploy:
  - GitHub push: `d10de39` → `master`
  - GitHub Actions: `Deploy to Xserver` run `23042155609`: `success`
- Verification:
  - local:
    - `single.php` にある `.blog-featured-img` ラッパーに対応する CSS が未定義だったことを確認
    - `style.css` に `.blog-featured-img` と `.blog-featured-img img` を追加し、`max-width: 760px`・中央配置・画像幅100%を設定
  - live:
    - 記事ページで `blog-featured-img` クラスの存在を確認
- Open Items:
  - なし
- Next Action:
  - なし

---

### 2026-03-12 11:48 JST | Agent: GitHub Actions (自動)
- Task: Claude Cowork 記事向け X 投稿 id=29 の自動投稿
- Deploy:
  - GitHub Actions: `X Auto Post` scheduled run — `id=29` 投稿成功、`id=30,31` 投稿成功
- Verification:
  - `id=29` tweet_id `2031925147532238920` posted at `2026-03-12T11:48:01+09:00`
  - `id=30` tweet_id `2032003729113985453` posted at `2026-03-12T17:00:15+09:00`
  - `id=31` tweet_id `2032050316032540854` posted at `2026-03-12T20:05:23+09:00`
- Open Items:
  - なし
- Next Action:
  - なし
