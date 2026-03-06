# Task Todo

## Task: Remove Visible Model Label

- [x] Remove the visible model label from the chatbot header UI.
- [x] Remove client-side references that updated the deleted DOM node.
- [x] Validate syntax, deploy to production, and confirm the served HTML no longer contains the label.
- [x] Add review notes.

## Review: Remove Visible Model Label

- Removed the visible `モデル: gpt-oss:20b-cloud` header text from `chatbot.php`, leaving only the `OISHI AI` title in the header UI.
- Removed the browser-side `active-model` DOM lookup and model text updates, since that UI element no longer exists.
- Verified `php -l chatbot.php` reports no syntax errors.
- Backed up production `chatbot.php` to `~/oishillc.jp/backups/20260306-chatbot-streaming/chatbot-hide-model-label.php` before upload.
- Deployed updated `chatbot.php` to production and confirmed the served chatbot HTML contains `OISHI AI` but no `モデル:` or `active-model`.
- Re-checked required deploy URLs and confirmed `200` for `/`, `/blog/`, `/wp-login.php`, and `/favicon.ico`.

## Task: Switch Waiting Indicator To Header Logo

- [x] Identify the homepage header logo asset used by the main site.
- [x] Replace the chatbot waiting dots with the same logo asset family.
- [x] Validate syntax, deploy to production, and confirm the served HTML references the logo assets.
- [x] Add review notes.

## Review: Switch Waiting Indicator To Header Logo

- Replaced the chatbot waiting dots in `chatbot.php` with the same logo asset family used by the homepage header: `logo-56.png` and `logo-112.png`.
- Added a small theme-asset URL helper in `chatbot.php` so the loading indicator uses cache-busted theme asset URLs without hardcoding stale versions.
- Changed the waiting animation from three dots to a subtle logo pulse/float animation while the assistant has not started streaming visible answer text yet.
- Verified `php -l chatbot.php` reports no syntax errors.
- Backed up production `chatbot.php` to `~/oishillc.jp/backups/20260306-chatbot-streaming/chatbot-logo-loading-indicator.php` before upload.
- Deployed updated `chatbot.php` to production and confirmed the served chatbot HTML contains `typing-indicator-logo` and references `logo-56.png` / `logo-112.png`.
- Confirmed the homepage header still references the same `logo-56.png` / `logo-112.png` asset family.
- Re-checked required deploy URLs and confirmed `200` for `/`, `/blog/`, `/wp-login.php`, and `/favicon.ico`.

## Task: Add Chat Waiting Dots

- [x] Add a waiting animation for the assistant while the streamed answer has not started yet.
- [x] Stop the animation as soon as answer text arrives or an error completes the request.
- [x] Validate syntax and confirm the deployed HTML includes the new markers.
- [x] Add review notes.

## Review: Add Chat Waiting Dots

- Added a three-dot waiting indicator to the assistant bubble in `chatbot.php` so users see activity immediately after sending a message.
- The indicator is attached only to the in-flight assistant entry and is removed when streamed `content` begins, or when `error` / `done` finalizes the request.
- Verified `php -l chatbot.php` reports no syntax errors.
- Backed up production `chatbot.php` to `~/oishillc.jp/backups/20260306-chatbot-streaming/chatbot-typing-indicator.php` before upload.
- Deployed updated `chatbot.php` to production and confirmed the served HTML contains `typing-indicator`, `is-waiting`, and `typing-dot`.
- Re-checked required deploy URLs and confirmed `200` for `/`, `/blog/`, `/wp-login.php`, and `/favicon.ico`.

## Task: Stream Production Chatbot UI

- [x] Remove demo/template text and switch the chat page to AI-only presentation.
- [x] Replace the inner message scroll area with natural page scrolling.
- [x] Add upstream-to-browser streaming for both reasoning and answer content.
- [x] Verify local syntax, local streaming behavior, and live production behavior.
- [x] Add review notes.

## Review: Stream Production Chatbot UI

- Reworked `chatbot.php` into a streaming AI UI: the browser now POSTs to `?stream=1`, receives SSE-style events, and renders `reasoning` and `content` deltas separately in real time.
- Removed demo-era copy and templates from the public UI, including the fixed greeting, mode label, and fixed-response hint text; the page now starts as an empty AI chat surface.
- Removed the inner message panel scroll lock by dropping the bounded/overflowed message area and using normal page scroll, while keeping conditional auto-scroll only when the user is already near the bottom.
- Kept a non-JavaScript fallback POST path so the page still works if streaming is unavailable, but the main runtime is now the streaming AI path.
- Verified `php -l chatbot.php` reports no syntax errors.
- Verified local page render via `php -S 127.0.0.1:8090 -t .` and `curl` shows no old mock/template text.
- Verified local `POST /chatbot.php?stream=1` streams `event: reasoning`, then `event: content`, then `event: done`.
- Created a production backup at `~/oishillc.jp/backups/20260306-chatbot-streaming/chatbot.php` before deploy.
- Deployed updated `chatbot.php` to `~/oishillc.jp/public_html/wp-content/themes/oishi-ai/chatbot.php`.
- Verified live `https://www.oishillc.jp/wp-content/themes/oishi-ai/chatbot.php` serves the new HTML (`OISHI AI`, no mock text) and live streaming returns reasoning/content/done events.
- Re-verified `https://www.oishillc.jp/wp-content/themes/oishi-ai/.env` returns `HTTP 403`.
- Re-checked required deploy URLs and confirmed `200` for `/`, `/blog/`, `/wp-login.php`, and `/favicon.ico`.

## Task: Restore Ollama Chatbot

- [x] Define the target runtime design and secret-handling rules.
- [x] Replace the fixed-response chatbot with an Ollama-backed AI chat flow.
- [x] Add `.env` support and ignore/protect secret files.
- [x] Verify syntax and end-to-end Ollama responses with the requested model.
- [x] Add review notes.

## Review: Restore Ollama Chatbot

- Replaced the fixed-response flow in `chatbot.php` with an Ollama-backed `/v1/chat/completions` integration using session chat history and proper POST/Redirect/GET (`303`) behavior.
- Added lightweight `.env` loading in `chatbot.php`, ignored local secret files in `.gitignore`, and added theme-level `.htaccess` rules to deny direct access to `.env*`.
- Stored the user-provided secret only in local `.env` (ignored by Git); committed template values live in `.env.example`.
- Updated the runtime target to `gpt-oss:20b-cloud` and confirmed Ollama now exposes that model via `/api/tags`.
- Verified direct model response from `http://127.0.0.1:11434/v1/chat/completions` using `gpt-oss:20b-cloud`.
- Verified end-to-end page behavior via local PHP server + form POST; rendered HTML showed `モデル: gpt-oss:20b-cloud` and a real assistant response.
- For production, switched the deployed env to direct Ollama Cloud API usage (`https://ollama.com/v1`) so the public chatbot does not depend on a local Ollama daemon.
- Deployed `chatbot.php`, uploaded `.env` to the theme directory as a fallback, uploaded `.htaccess` to block direct `.env` access, and kept a non-public env copy outside `public_html`.
- Verified live POST to `https://www.oishillc.jp/wp-content/themes/oishi-ai/chatbot.php` renders a real AI answer in production.
- Verified direct HTTP access to `https://www.oishillc.jp/wp-content/themes/oishi-ai/.env` returns `403`.
- Re-checked required deploy URLs and confirmed `200` for `/`, `/blog/`, `/wp-login.php`, and `/favicon.ico`.

- [x] Define implementation plan and constraints.
- [x] Add a PHP chatbot endpoint that calls an OpenAI-compatible API.
- [x] Default model to `gpt-oss:120b`.
- [x] Keep API key out of source and use environment variables.
- [x] Run syntax validation.
- [x] Add short review notes.

## Review

- `chatbot.php` added with session-based chat history and OpenAI-compatible `/chat/completions` calls.
- Default model set to `gpt-oss:120b` and override supported via `CHATBOT_MODEL`.
- API key is read from `CHATBOT_API_KEY`; no hardcoded secret was committed.
- Updated `chatbot.php` to allow Ollama usage without an API key and only attach `Authorization` when provided.
- Verified local Ollama OpenAI-compatible endpoint (`http://127.0.0.1:11434/v1/chat/completions`) responds.
- Started `ollama pull gpt-oss:120b` in background (large download in progress; model not yet available).
- Syntax check completed: `php -l chatbot.php` reported no syntax errors.

## Task: Add Chatbot To Portfolio

- [x] Define a minimal-impact implementation plan.
- [x] Add a featured chatbot project card to `page-portfolio.php`.
- [x] Add scoped CSS for the featured card and CTA link in `style.css`.
- [x] Validate PHP syntax and check for obvious UI regressions.
- [x] Add review notes for this task.

## Review: Add Chatbot To Portfolio

- Added a new featured portfolio card at the top of the portfolio grid for `OISHI 日本語チャットボット`.
- Added a direct demo link to `chatbot.php` using `get_template_directory_uri()` and safe URL escaping.
- Added scoped styles for featured state, badge, and CTA link in the portfolio section only.
- Verified PHP syntax with `php -l page-portfolio.php` (no syntax errors).
- Checked class references and markup/style linkage with `rg` to reduce UI regression risk.

## Task: Add Chatbot Entry On Homepage

- [x] Create rollback backup for target files before edits.
- [x] Add a homepage section that introduces the Japanese chatbot and links to demo.
- [x] Add scoped CSS for the new homepage chatbot section.
- [x] Validate syntax/link references and check for obvious regression.
- [x] Add review notes for this task.

## Review: Add Chatbot Entry On Homepage

- Created rollback backups at `tasks/backups/20260304-home-chatbot/` before edits.
- Added a new homepage section (`#chatbot-demo`) right after Services to highlight `OISHI 日本語チャットボット`.
- Added two CTAs: `チャットボットを試す` (to `chatbot.php`) and `実績を見る` (to portfolio page).
- Added scoped styles for the highlight card, badge, tags, and responsive behavior in mobile layout.
- Verified PHP syntax with `php -l index.php` and checked class/link references with `rg`.

## Task: Move Chatbot Into Service/Portfolio Areas

- [x] Remove standalone chatbot highlight section from homepage.
- [x] Add chatbot entry directly inside the `services` grid.
- [x] Keep featured chatbot card in portfolio (`page-portfolio.php`).
- [x] Update CSS to service-level chatbot card styles.
- [x] Validate syntax and selector references.

## Review: Move Chatbot Into Service/Portfolio Areas

- Chatbot is now placed directly in the `services` section as a featured service card in `index.php`.
- Standalone `chatbot-highlight` section was removed to avoid duplication and keep layout clean.
- Existing featured chatbot project in `page-portfolio.php` was kept, so it appears in both requested areas (サービス/実績).
- Added `.service-card--chatbot`, `.service-badge`, `.service-link` styles and confirmed references with `rg`.
- Verified `php -l index.php` reports no syntax errors.

## Task: Implement Service SVG Icons

- [x] Define minimal-impact implementation plan for service icon integration.
- [x] Embed 7 inline SVG icons from `preview/icon-preview.html` into `index.php` service cards.
- [x] Add minimal `.service-icon` styles in `style.css` for consistent rendering.
- [x] Run syntax and reference checks (`php -l`, `rg`) for the updated service section.
- [x] Add review notes for this task.

## Review: Implement Service SVG Icons

- Added 7 inline SVG icons to the homepage Services cards in `index.php`, based on `preview/icon-preview.html`.
- Switched icon drawing color to `currentColor` so icon tone follows theme text color (`--text-1`) instead of hardcoded hex.
- Added `.service-icon`, `.service-icon--large`, and nested SVG/text rules in `style.css` for consistent sizing and rendering.
- Verified PHP syntax with `php -l index.php` (no syntax errors).
- Verified icon markup and selectors with `rg -n "service-icon|<svg|</svg" index.php style.css`.
- Confirmed production mismatch (`https://www.oishillc.jp/` served old HTML without `service-icon`), then deployed `index.php` and `style.css` to `~/oishillc.jp/public_html/wp-content/themes/oishi-ai/`.
- Created rollback backups on server at `~/oishillc.jp/backups/20260305-service-svg/` before upload.
- Re-checked production HTML via `curl` and confirmed `service-icon` markers are now present in served output.

## Task: Replace Service SVG Icons With Generated Images

- [x] Define implementation plan and target file mapping for 7 generated service images.
- [x] Normalize and optimize generated images into theme assets.
- [x] Replace inline SVG blocks in `index.php` with per-service image markup.
- [x] Update `style.css` to use card media layout tuned for generated images.
- [x] Run syntax checks and selector/reference validation.
- [x] Deploy updated files to production with server-side rollback backup.
- [x] Add review notes for this task.

## Review: Replace Service SVG Icons With Generated Images

- Converted 7 generated images to optimized WebP (`16:9`, width `1600`) and placed them in `assets/services/`.
- Replaced all service-card inline SVG blocks with image media blocks in `index.php` using `loading="lazy"` and `decoding="async"`.
- Added `.service-media` styles in `style.css` (16:9 aspect ratio, border radius, cover fit, subtle hover zoom).
- Verified syntax and references with `php -l index.php` and `rg`.
- Created production rollback backup at `~/oishillc.jp/backups/20260305-service-images/` before deploy.
- Uploaded `index.php`, `style.css`, and `assets/services/*.webp` to production.
- Verified served HTML contains all 7 `assets/services/service-0*.webp` references and confirmed image URL returns `HTTP/2 200`.

## Task: Improve Favicon Visibility On Dark Tabs

- [x] Confirm whether current-task edits changed icon binaries.
- [x] Back up existing favicon/site-icon assets before regeneration.
- [x] Regenerate `favicon.ico`, `favicon.png`, `site-icon.png`, `site-icon-192.png`, and `apple-touch-icon.png` with dark-mode-safe contrast.
- [x] Verify output dimensions and preview visibility on dark background.
- [x] Add review notes for this task.

## Review: Improve Favicon Visibility On Dark Tabs

- Confirmed current working diff only modified `functions.php`; icon binaries were not changed by the previous step.
- Backed up previous icon assets to `tasks/backups/20260306-favicon-contrast/`.
- Rebuilt favicon/image assets from `logo.png` by adding a light circular plate and border to preserve visibility on dark browser UI.
- Replaced theme icon files: `favicon.ico`, `favicon.png`, `site-icon.png`, `site-icon-192.png`, `apple-touch-icon.png`.
- Verified dimensions via `sips` (`512`, `192`, `180`, and multi-size `.ico`) and visually checked the rendered icon preview.
- Created production rollback backup at `~/oishillc.jp/backups/20260306-favicon-darkmode/` before deploy.
- Uploaded 6 files to production theme and verified live responses:
  - `https://www.oishillc.jp/favicon.ico` returns `HTTP 200` with `image/x-icon`.
  - Homepage HTML includes `rel="icon"` and `rel="shortcut icon"` pointing to `/favicon.ico`.

## Task: Publish PoC Roadmap Article With 3 Images

- [x] Determine final policy for article tail sections (`要点` keep, `メタディスクリプション` move out of body).
- [x] Locate and clean 3 generated images (remove Gemini watermark; remove palette legend from one image).
- [x] Place images at optimized positions inside article sections.
- [x] Upload assets and publish article to production WordPress blog.
- [x] Verify post URL, blog index listing, and live image URLs.

## Review: Publish PoC Roadmap Article With 3 Images

- Created article source at `tasks/article-20260306-poc-roadmap.html`.
- Kept `この記事の要点（3行）` in body for readers; removed visible `メタディスクリプション` block.
- Set meta-description-equivalent text via WordPress `post_excerpt` so it appears in list/meta without cluttering article body.
- Generated cleaned image assets:
  - `assets/blog/20260306-poc-roadmap/img-01-poc-trap-to-roadmap.webp`
  - `assets/blog/20260306-poc-roadmap/img-02-90day-steps.webp`
  - `assets/blog/20260306-poc-roadmap/img-03-governance-kpi.webp`
- Uploaded image assets to production theme directory and confirmed each URL returns `HTTP 200`.
- Published post ID `29`:
  - URL: `https://www.oishillc.jp/2026/03/06/ai-poc-roadmap-90days/`
  - Title: `PoC地獄を抜け出すAI導入ロードマップ：中小・中堅企業が90日で成果を出す実行設計`
- Confirmed the post is listed at `https://www.oishillc.jp/blog/` as the latest entry.

## Task: Restore Favicon Display On WordPress Tabs

- [x] Verify live favicon behavior on homepage and WordPress login.
- [x] Identify root cause for WordPress `W` icon fallback.
- [x] Update theme function hooks to output favicon tags for login/admin as well.
- [x] Deploy only `functions.php` with production backup.
- [x] Verify `wp-login.php` and homepage both emit custom favicon tags.

## Review: Restore Favicon Display On WordPress Tabs

- Root cause: custom favicon tags were emitted only on `wp_head`; WordPress login/admin tabs could still fall back to default icon.
- Updated `functions.php` to centralize icon URL generation and print the same icon tags on:
  - `wp_head`
  - `admin_head`
  - `login_head`
- Kept `/favicon.ico` direct 200 serving logic unchanged.
- Created production backup at `~/oishillc.jp/backups/20260306-favicon-login-admin/functions.php`.
- Deployed `functions.php` to production and confirmed:
  - `https://www.oishillc.jp/` emits custom favicon tags.
  - `https://www.oishillc.jp/wp-login.php` now emits the same custom favicon tags.
  - `https://www.oishillc.jp/favicon.ico` returns `HTTP 200`.

## Task: Favicon Hygiene And Artifact Cleanup

- [x] Inventory favicon-related files and map real runtime references.
- [x] Consolidate favicon backups into one baseline set (local + production).
- [x] Remove redundant favicon backup directories.
- [x] Add a single-source favicon generation script.
- [x] Document keep/remove rationale and future operating rules.

## Review: Favicon Hygiene And Artifact Cleanup

- Runtime references were confirmed in `functions.php` and `chatbot.php`; canonical favicon set is now explicitly documented.
- Local favicon backup was consolidated to `tasks/backups/20260306-favicon-baseline/`.
- Production favicon backups were consolidated to `~/oishillc.jp/backups/20260306-favicon-baseline/`.
- Removed redundant backup directories:
  - local: `tasks/backups/20260306-favicon-contrast/`
  - production: `~/oishillc.jp/backups/20260306-favicon-darkmode/`, `~/oishillc.jp/backups/20260306-favicon-login-admin/`
- Added single-source generator script: `tasks/scripts/build_favicon_set.py`.
- Added governance/rationale doc: `tasks/favicon-inventory.md`.
- Added dedicated favicon source asset for regeneration stability: `assets/favicon/source.png`.
- Re-verified live state after cleanup:
  - `https://www.oishillc.jp/` and `https://www.oishillc.jp/wp-login.php` both emit custom favicon tags.
  - `https://www.oishillc.jp/favicon.ico` returns `HTTP 200`.

## Task: Simplify Multi-Agent Operation (Codex + Claude)

- [x] Create one shared operation rule file for both agents.
- [x] Reduce `CLAUDE.md` and `CODEX_ONLY.md` to minimal wrappers.
- [x] Update startup rules to enforce shared rule reading order.
- [x] Add `tasks/handoff.md` so either agent can continue without user overhead.
- [x] Provide minimal user operation phrase.

## Review: Simplify Multi-Agent Operation (Codex + Claude)

- Added shared rulebook: `PROJECT_RULES.md`.
- Simplified `CLAUDE.md` to shared-rule reference + Claude-specific server facts.
- Replaced verbose `CODEX_ONLY.md` with slim Codex-only delta instructions.
- Updated `AGENTS.md` startup order to:
  1) `PROJECT_RULES.md`
  2) `tasks/handoff.md`
  3) `CODEX_ONLY.md`
- Added `tasks/handoff.md` with template and current state entry.
- This reduces user instruction burden to one sentence:
  - `tasks/handoff.md の最新を読んで、未完了タスクを続けて。デプロイと検証まで実施して。`

## Task: Complete Image SEO Hardening

- [x] Add theme-level image SEO helpers in `functions.php` (dimensions/srcset/meta/schema/sitemap).
- [x] Optimize header logo delivery and replace template logo markup with shared helper.
- [x] Add responsive `srcset` variants for homepage service images and article images.
- [x] Deploy changes to production with rollback backup.
- [x] Run production crawl checks for core pages and all published posts.

## Review: Complete Image SEO Hardening

- Implemented image SEO utility layer in `functions.php`:
  - image path resolution for theme/uploads
  - width/height extraction
  - local `srcset` builder
  - post content image normalization (`alt/loading/decoding/fetchpriority/width/height/srcset/sizes`)
  - Open Graph + Twitter image tags
  - JSON-LD `BlogPosting` for single posts
  - dynamic `/image-sitemap.xml` generation + `robots.txt` sitemap line
- Added compact logo variants (`logo-56.png`, `logo-112.png`) and switched all templates to `oishi_ai_get_logo_image_html()`.
- Added responsive service image variants (`assets/services/*-800.webp`) and wired `srcset/sizes` + descriptive `alt`.
- Added responsive blog image variants (`assets/blog/20260306-poc-roadmap/*-768.webp`) used by content filter `srcset`.
- Deployed to production after backup at `~/oishillc.jp/backups/20260306-image-seo/`.
- Verified production:
  - `/`, `/blog/`, `/contact/`, `/portfolio/`, and all 6 posts have zero missing `img` attributes (`alt`, `width`, `height`, `loading`, `srcset`).
  - `og:image`/`twitter:image` present on home/blog/posts.
  - article pages output `application/ld+json`.
  - `https://www.oishillc.jp/image-sitemap.xml` returns image entries.
  - `robots.txt` contains both default sitemap and image sitemap.
  - `/wp-login.php` favicon tags and `/favicon.ico` HTTP 200 remain valid.
