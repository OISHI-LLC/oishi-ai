# CODEX_ONLY.md

Project-specific Codex overrides for `oishi-ai`.
Global defaults: `/Users/eisukeoishi/AGENTS.md`.

## Startup

1. Read latest `tasks/handoff.md` first.
2. Apply this file as project-local overrides.

## Project Guardrails

1. Do not edit, reinterpret, or depend on `CLAUDE.md` unless the user explicitly asks.
2. Do not modify `.claude/*` config files unless explicitly asked.
3. Keep Codex operational docs and logs under `tasks/*`.

## Deploy Verification (only when deploy-related)

Verify at least:
- `/`
- `/blog/`
- `/wp-login.php`
- `/favicon.ico` (HTTP status)

## Deploy Path Policy

1. Production deploy must use GitHub Actions workflow only (`.github/workflows/deploy.yml`).
2. Do not use manual `scp` / direct server upload in normal operation.
3. Manual deploy is allowed only for emergency rollback with explicit user instruction.
4. For deploy tasks, record workflow status and URL checks in `tasks/handoff.md`.
