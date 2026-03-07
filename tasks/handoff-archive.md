# Handoff Archive

完了済みエントリのアーカイブ。最新は `handoff.md` を参照。

---

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
