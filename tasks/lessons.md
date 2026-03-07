# Lessons（上限50行。超えたら統合・削除する）

## Deploy
- 本番反映は CI のみ。手動 scp は緊急+ユーザー明示指示時のみ。
- commit に意図しないファイルを含めない。

## Verification
- 成果物（画像・HTML等）は本番反映前に必ず目視確認する。
- 「反映済み」は URL/レスポンスで事実確認。ローカル変更だけで完了としない。
- WP スケジュール: `--post_date` と `--post_date_gmt` を両方設定し、`post_status=future` を確認。

## Quality Gate
- 明らかな品質問題は聞かずに即修正。壊れた成果物を「直しますか？」と聞くのは失礼。
- rsvg-convert は Unicode 絵文字を描画不可。SVG vector path で代替する。

## WordPress / Theme
- favicon は `wp_head` / `login_head` / `admin_head` すべてに設定。
- テーマカラー変更時は CSS 変数だけでなく、ハードコードされた rgba も全て更新。
- ユーザーの参考画像はあくまで方向性。サイトのデザインシステムに合わせる。

## Code Structure
- 単一ファイルが肥大化したら「最小変更」を言い訳にせず分割する。
- 非自明な拡張が2回以上続いたら責務分離を先に行う。

## Communication
- ユーザーへの応答は常に丁寧語（です/ます）で統一。タメ語を混ぜない。
- ローカル差分の警告等は Git 用語を避け、平易な日本語で伝える。

## Chatbot Patterns
- モデル名検知を広げたら、`会社の名前は？` 等の負例テストも必ず実施。
- Ollama モデル指定はサフィックスまで正確に確認してから作業する。
- チャットボットの文面品質は、実際の自然文プロンプトで確認する。`**強調**` や `|` 区切り表が生表示のままなら未完了。
- PHP から JS/CSS を分離した後は、`node --check` や `php -l` で構文確認し、HTML断片の混入を見逃さない。

## Multi-Agent
- handoff.md + PROJECT_RULES.md で引き継ぎ。エージェント固有ファイルは薄く保つ。
