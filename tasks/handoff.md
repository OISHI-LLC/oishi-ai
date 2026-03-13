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

### 2026-03-14 08:40 JST | Agent: Claude
- Task: 三大AI比較記事デプロイ + X投稿6本キュー登録 + X投稿ワークフロー自動化
- Changed Files:
  - `assets/blog/20260315-ai-comparison/` — 記事画像4枚(SVG/PNG/WebP) + hero画像
  - `assets/twitter/20260314/` — 3/14用ツイート画像2枚
  - `assets/twitter/20260315/` — 3/15用ツイート画像2枚(use-case-pick, team-cost)
  - `tasks/x-content-queue.json` — id:35-37(3/14分)status修正 + id:38-40(3/15分)追加
  - `.github/workflows/x-post.yml` — cronを毎日定時固定(JST 7:00/11:00/15:00)に変更 + 失敗時GitHub Issue自動作成
  - `scripts/x-post.py` — エラーカウント追加、失敗時exit(1)でワークフロー失敗をトリガー
  - `tasks/todo.md` — 前回記事のX投稿タスク完了
- Deploy:
  - WP: post ID=69 は前セッションで作成済み（status=future, 2026-03-15 05:00:00）
  - GitHub push: `4c5a9a9` → `3575855` → `fc44c8c` → `master`
  - GitHub Actions:
    - `Deploy to Xserver` run `23074174897`: success（画像5枚すべてHTTP 200確認）
    - `X Auto Post` manual run `23074769030`: success（id=35投稿 → tweet 2032600960523186590）
- Verification:
  - 画像: 本番5枚すべてHTTP 200（hero + img-01〜04）
  - WP: post 69 — status=future, post_date=2026-03-15 05:00:00
  - X投稿: id=35 posted確認。id:36(12:00), id:37(15:00)はcronで自動投稿予定
  - ツイート画像: SVG→PNG変換後Readで目視確認、テキストはみ出し・崩れなし
  - ツイート文字数(URL除外): id:38=401字, id:39=399字, id:40=396字（目標300-400）
- Open Items:
  - 3/14 id:36(12:00 JST), id:37(15:00 JST) — cron自動投稿待ち
  - 3/15 id:38(7:00), id:39(11:00), id:40(15:00) — cron自動投稿待ち
  - 3/15 05:00 — WP記事 ID=69 自動公開待ち
- Next Action:
  - X投稿の自動投稿結果を確認（失敗時はGitHub Issueが自動作成される）
  - 3/16、3/17の記事テーマ決定・作成

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

