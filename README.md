# Digid Starter Theme

Custom WordPress starter theme by [dig.id](https://dig.id) — the foundation for every new project. WooCommerce-ready, WPML-compatible, built with Tailwind CSS and Vite.

## Stack

- **WordPress** custom theme (PHP 8.0+, text domain `kitchensink`)
- **Tailwind CSS v3** + modular **SASS**, compiled with **Vite**
- **JS**: GSAP, Lenis (smooth scroll), Swiper, Isotope, Fancybox
- **ACF Pro** for content fields, **Yoast** for SEO/schema, **WPML** for translations

## Getting started

Starting a new project from this starter? Follow the full checklist in **[SETUP.md](SETUP.md)** (rename, prefixes, fonts, colours). To just run it:

```bash
npm install        # also runs composer install
npm run dev        # Vite dev server — HMR for CSS/JS, full reload on PHP changes
npm run watch      # development build to dist/ on every change (no HMR/reload)
npm run prod       # production build to dist/ (minified, hashed file names)
```

The local dev URL is set in `vite.config.mjs` (`DEV_HOST`, overridable per machine in an untracked `.env.local`). While `npm run dev` is running, WordPress loads assets from the dev server; it does **not** write `dist/` — run `npm run prod` before committing or deploying.

## Code quality

```bash
npm run php:lint   # PHPCS (WordPress-Core, -Docs, -Extra)
npm run php:fix    # PHPCBF auto-fix
```

All PHP follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/). Every file carries a PHPDoc header (`@package kitchensink`, `@subpackage`, `@since`) — see [CLAUDE.md](CLAUDE.md) for the full conventions (also used by AI tooling).

## Structure

```
├── assets/
│   ├── js/               # source JS (main.js + utils)
│   └── sass/             # modular SASS (_vars, _components, _modules, _pages…)
├── dist/                 # compiled assets — never edit directly
├── inc/                  # theme functionality (setup, enqueue, Vite loader, security…)
├── page-templates/       # WordPress page templates
├── template-parts/       # reusable sections and components
├── functions.php         # config constants + loads inc/ files
└── vite.config.mjs       # build config (entries, dev server, Tailwind/PostCSS)
```

## Workflow

The full development flow (starter maintenance, releases, starting a new project, available Claude Code skills) is documented in **[WORKFLOW.md](WORKFLOW.md)**.

## Versioning

[Semantic Versioning](https://semver.org/) (`MAJOR.MINOR.PATCH`), tracked in **[CHANGELOG.md](CHANGELOG.md)** and kept in sync with the `Version:` header in `style.css`.

Planned improvements live in **[TODO.md](TODO.md)**.
