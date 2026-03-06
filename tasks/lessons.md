# Lessons

## Deploy Safety
- **NEVER deploy local files without first pulling the latest from the server.** Local files may be stale and overwrite newer changes on the server.
- Before any `scp` to production, always `ssh xserver` and check the current state of the files being replaced.
- Pattern: `ssh xserver "head -25 ~/oishillc.jp/public_html/wp-content/themes/oishi-ai/<file>"` to verify before overwriting.

## Theme Color Changes
- When switching dark/light theme, ALL hardcoded rgba values must be updated, not just CSS variables. Check: header backdrop, mobile nav, button borders, form inputs, selection color, flow gradients, logo filters, box-shadows.

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

## Model Variant Confirmation
- When a user specifies an Ollama model, confirm the exact suffix before long downloads or config changes. `gpt-oss:20b` and `gpt-oss:20b-cloud` are different targets with different runtime behavior.
- If the user gives a correction on the model name, update `.env`, defaults, and verification commands together in the same pass so the code, local runtime, and notes stay aligned.
