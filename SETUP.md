# New Project Setup

Step-by-step checklist for starting a new project from this starter theme.

---

## 0. Get the theme

**Option A (preferred):** download the zip from the latest [GitHub Release](../../releases) of the starter repo — every `vX.Y.Z` tag publishes a release with `theme-starter-vite.zip` attached automatically.

**Option B (local):** in the starter repo, generate the zip yourself:

```bash
npm run zip
```

Both produce the same thing: tracked files only — no `node_modules/`, `vendor/` or git history. Extract into `wp-content/themes/` of the new project, then create the new project's git repository from that folder.

> `npm run zip` builds from `HEAD`, not from the working tree — commit pending changes first.

---

## 1. Rename the theme folder

Rename the theme directory from `theme-starter-vite` to your project slug (e.g. `project-name`).

---

## 2. Find & replace across the entire project

Run these replacements in order (VS Code: `Ctrl+Shift+H`, check "Match Case"):

| Find | Replace with | Where |
|------|-------------|-------|
| `theme-starter-vite` | `project-name` | Theme folder references |
| `Theme Starter Vite` | `Project Name` | Human-readable theme name |
| `digid` | `project_prefix` | Text domain & function prefix |

> **Note:** Keep the prefix short and lowercase with underscores (e.g. `meinprojekt`).

> **Careful with `digid`:** don't replace `dig.id` (Author/Author URI) or Local dev hostnames ending in `.digid` (e.g. `project-name.digid` in `vite.config.mjs`) — the Vite dev server needs the real Local hostname to reach the site and its SSL certificate. Review the matches before replacing all.

---

## 3. `style.css` — update theme metadata

```css
Theme Name:  Project Name
Theme URI:   #
Author:      dig.id
Author URI:  https://dig.id
Description: Custom WordPress theme for Project Name
Version:     1.0.0
Text Domain: project_prefix
```

---

## 4. `vite.config.mjs` — update local dev URL

Set the fallback hostname (no protocol, no trailing slash):

```js
const host = env.DEV_HOST || 'project-name.digid';
```

> Each dev can override it on their machine without touching the repo: create an untracked `.env.local` with `DEV_HOST=project-name.digid`. The dev server reuses Local's SSL certificate for that host (Local → site → SSL → **Trust**); if the certificate isn't found it falls back to HTTP.

---

## 5. `assets/sass/_vars.sass` — define project colours and fonts

```sass
--color-primary: #______
--color-secondary: #______
--font-primary: 'FontName', sans-serif
--font-secondary: 'FontName', sans-serif
```

---

## 6. `inc/enqueue.php` — update Google Fonts URL

Replace the Inter import with the project font URL.

---

## 7. `tailwind.config.js` — update font families and colours

```js
fontFamily: {
  primary: ['FontName', 'sans-serif'],
  secondary: ['FontName', 'sans-serif'],
},
colors: {
  'brand-primary': '#______',
},
```

---

## 8. `inc/theme-setup.php` — review nav menus

Add or remove nav menus to match the project's navigation structure.

---

## 9. Reset versioning & changelog

The starter's version history belongs to the starter repo — a new project starts fresh:

1. In `style.css`, set `Version: 1.0.0`.
2. Replace the contents of `CHANGELOG.md` with a single initial entry (keep the rules header at the top):

```markdown
## [1.0.0] — YYYY-MM-DD

### Added
- Initial project setup from dig.id starter theme (vX.Y.Z).
```

> Reference the starter version you branched from — useful to know later which improvements the project inherited.

3. Delete `TODO.md` (it tracks starter improvements, not project work) — or reset it if you want a project TODO.
4. Clean `.gitattributes`: remove the `export-ignore` rules (`dist/`, `TODO.md`, `.github/`). They only make sense for the starter's own release zip (`npm run zip` / `git archive`); left in a project repo they silently strip those paths from any deploy tool that reads `export-ignore` the same way (e.g. WP Pusher), which can ship a site with no compiled `dist/` and no visible error. Keep only the line-ending rule:

```
# Auto detect text files and force LF line endings on checkout,
# regardless of each dev's local core.autocrlf setting.
* text=auto eol=lf
```

---

## 10. Install dependencies

Check your Node version first — Vite 8 requires `^20.19.0 || >=22.12.0` (see `engines` in `package.json`):

```bash
node -v
npm install
```

---


## 11. First build

```bash
npm run prod
```

This writes `dist/` (hashed files + `dist/.vite/manifest.json`), which is what the site loads when the dev server is not running. While developing, run:

```bash
npm run dev
```

> `npm run dev` starts the Vite dev server (HMR) and does **not** write `dist/` — always run `npm run prod` before committing/deploying.

---

## 12. Analyse the Figma design (optional but recommended)

If the project has a Figma design, run the `/figma-analysis` skill in Claude Code (it ships with this theme in `.claude/skills/`). Provide the Figma URL and the page name (e.g. "Hifi Wireframe") — it generates `figma-architecture-analysis.txt` mapping the design to the theme architecture: sections, components (+ suggested `$args`), JS modules, WP templates/CPTs and ACF field suggestions. Use it as the build roadmap and to align `CLAUDE.md` (CPTs, field groups) with the real project.

---

## 13. `wp-config.php` — secrets & hardening

### Secrets (any environment)

Theme constants in `functions.php` ship with safe empty defaults and are wrapped in `if ( ! defined( ... ) )` — override them per environment in `wp-config.php`, **above** the `/* That's all, stop editing! */` line. Real API keys live only in `wp-config.php` (outside the repo), never in the theme:

```php
define( 'PREFIX_GOOGLE_MAPS_API_KEY', 'real-key-here' );
define( 'PREFIX_FONT_PROVIDER', 'google' );
```

### Hardening (live sites — before launch)

```php
define( 'DISALLOW_FILE_EDIT', true );   // Removes the code editors from wp-admin.
define( 'WP_DEBUG', false );            // Never leave debug on in production.
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_POST_REVISIONS', 10 );      // Cap revision build-up (optional).
```

Local/staging keep `WP_DEBUG` as needed — `digid_console_log()` and debug output are gated by it.

> The rest of the hardening is already covered by the stack: SG Security (login, users), the theme's `inc/security.php` (REST/XML-RPC/headers/search) and WP Rocket on live. Don't duplicate it here.

---

## Done. Start building.
