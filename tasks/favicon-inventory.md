# Favicon Inventory (Oishi AI Theme)

## Canonical Files (Keep)

1. `favicon.ico`
- Why needed: browsers and crawlers still request `/favicon.ico` first. Our theme serves this path directly.

2. `site-icon.png` (512x512)
- Why needed: explicitly referenced by theme head tags and used by `chatbot.php`.

3. `site-icon-192.png` (192x192)
- Why needed: explicitly referenced by theme head tags; common Android/Chrome icon size.

4. `apple-touch-icon.png` (180x180)
- Why needed: explicitly referenced for iOS home-screen icon.

5. `favicon.png` (512x512, compatibility alias)
- Why kept: not strictly required by current PHP hooks, but retained as a compatibility fallback and matches `site-icon.png`.

6. `assets/favicon/source.png` (favicon master source)
- Why needed: single source image for favicon regeneration (current high-contrast design).

7. `logo.png` (header logo source)
- Why kept: used by site header brand mark. Do not overwrite it for favicon experiments.

## Removed As Redundant

1. Local backup folder `tasks/backups/20260306-favicon-contrast/`
- Why removed: superseded by a single baseline snapshot.

2. Production backup folders:
- `~/oishillc.jp/backups/20260306-favicon-darkmode/`
- `~/oishillc.jp/backups/20260306-favicon-login-admin/`
- Why removed: merged into one baseline snapshot to avoid duplicate rollback sets.

## Active Baseline Snapshot

- Local: `tasks/backups/20260306-favicon-baseline/`
- Production: `~/oishillc.jp/backups/20260306-favicon-baseline/`

## Regeneration Rule

Always regenerate from one source image to avoid drift:

```bash
python3 tasks/scripts/build_favicon_set.py --source assets/favicon/source.png --out-dir .
```

Then verify all of:

1. `https://www.oishillc.jp/` has favicon tags.
2. `https://www.oishillc.jp/wp-login.php` has same favicon tags.
3. `https://www.oishillc.jp/favicon.ico` returns `HTTP 200`.
