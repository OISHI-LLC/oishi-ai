# Lessons

## Deploy Safety
- **Use CI-only deploy.** Production uploads must go through GitHub Actions workflow, not manual `scp`.
- Before pushing deploy-related changes, confirm only intended runtime files are included in the commit.
- Manual server upload is emergency-only and requires explicit user instruction.

## Theme Color Changes
- When switching dark/light theme, ALL hardcoded rgba values must be updated, not just CSS variables. Check: header backdrop, mobile nav, button borders, form inputs, selection color, flow gradients, logo filters, box-shadows.
- If the user shares a reference image, treat it as a direction only. Keep the chatbot aligned with the site’s established design system unless the user explicitly asks for a deliberate visual departure.

## State Communication
- When warning about local uncommitted changes, explain in plain Japanese first: "ローカル作業中の差分があるので、上書き事故防止のため編集対象を限定する" and avoid Git jargon-heavy phrasing.
- If the user says deployment is already complete, immediately align to that context and continue with scoped edits instead of re-arguing repository state.

## Reflection Verification
- When a user reports "not reflected", do not assume UI/rendering bugs first. Immediately compare:
  - local file content,
  - server theme file content, and
  - served production HTML (`curl`).
- Completion gate for deploy-related work: verify the served HTML includes the new markers (e.g., `service-icon`), not just that local files changed.

## Favicon Contrast
- When updating brand icons, always preview favicon output on both light and dark browser tabs before finishing.
- Keep a high-contrast fallback plate (light base or clear outline) so the mark remains visible in dark-mode tab strips.
- Back up previous favicon assets before regeneration to make rollback immediate.

## WordPress Favicon Scope
- For favicon fixes on WordPress themes, do not validate only frontend (`wp_head`).
- Also verify and, if needed, inject matching icon tags for `login_head` and `admin_head`; otherwise tabs can fall back to the default WordPress icon.
- Completion gate: check both `https://<domain>/` and `https://<domain>/wp-login.php` head tags plus `/favicon.ico` HTTP status.

## Artifact Hygiene
- When a user requests repeated favicon/image updates, always run an explicit inventory and define canonical runtime files before making another change.
- Keep exactly one active rollback snapshot per workstream (e.g., `.../favicon-baseline`) and remove superseded intermediate backups to avoid ambiguity.
- Separate source assets from generated outputs (e.g., `assets/favicon/source.png` -> generated icon set) and document this mapping in a dedicated inventory note.

## Multi-Agent Continuity
- If the user alternates between Codex and Claude due usage limits, optimize for minimum user coordination.
- Maintain a shared rule file + a single handoff log so either agent can resume with one user sentence.
- Keep agent-specific files thin wrappers; avoid duplicated long rule texts across multiple files.
- If the user explicitly says `RULES+handoff読んで` (or equivalent), stop and re-read `PROJECT_RULES.md` plus the latest `tasks/handoff.md` before resuming edits or verification.

## Model Variant Confirmation
- When a user specifies an Ollama model, confirm the exact suffix before long downloads or config changes. `gpt-oss:20b` and `gpt-oss:20b-cloud` are different targets with different runtime behavior.
- If the user gives a correction on the model name, update `.env`, defaults, and verification commands together in the same pass so the code, local runtime, and notes stay aligned.

## Model Alias Overrides
- When a user says the chatbot still fails model-name questions, verify with multiple natural phrasings, not just `モデル名は何ですか？`. Include variants like `何のモデル？`, `このチャットは何を使ってるの？`, `このシステムのLLMは？`, and `あなたはChatGPT?`.
- After broadening model or identity regexes, always run nearby negative tests such as `会社の名前は？` and `代表者の名前は？`. Over-broad `名前` or `モデル` matches can silently break homepage-grounded company answers.

## WordPress Scheduling
- `wp post update --post_status=future --post_date="YYYY-MM-DD HH:MM:SS"` だけではタイムゾーンずれで即時公開になる場合がある。必ず `--post_date_gmt` も明示的に設定すること（JST - 9h = GMT）。
- スケジュール後は `wp post get <ID> --fields=post_status,post_date,post_date_gmt` で `future` になっていることを必ず確認する。

## Quality Gate: Fix Broken Output Immediately
- 成果物に明らかな品質問題（画像の黒い四角、壊れたレイアウト等）がある場合、ユーザーに「直しますか？」と聞かずに即座に修正すること。
- 壊れた成果物を納品してから確認するのは失礼。気づいた時点で修正→差し替え→検証を完了させる。
- rsvg-convert は Unicode 絵文字を描画できない。SVG内で絵文字を使わず、SVG vector path で代替アイコンを描くこと。

## Structural Complexity
- 単一ファイルに設定、分岐、AI上書きロジック、HTML、JS初期化が集まり始めたら、その時点で「最小変更」を言い訳にせず分割へ切り替えること。`chatbot.php` のような入口ファイルは薄く保ち、ロジックは `inc/chatbot/*` に逃がす。
- AGENTS.md の `Simplicity First` と `Demand Elegance` は「とりあえず1ファイルに足す」を正当化しない。非自明な拡張が2回以上続いたら、保守性を優先して責務分離を先に行う。
