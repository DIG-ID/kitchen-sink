# Changelog

All notable changes to this theme are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/) (`MAJOR.MINOR.PATCH`).

- **MAJOR** (`1.0.0` → `2.0.0`) — structural or breaking changes (redesign, feature removal, changes requiring manual intervention).
- **MINOR** (`1.0.0` → `1.1.0`) — new features / implementations (new template, new CPT, new section).
- **PATCH** (`1.0.0` → `1.0.1`) — bug fixes and small adjustments that do not add functionality.

---

## [1.1.0] — 2026-10-20

### Added
- Full-viewport split hero section on the home template.

### Fixed
- Vite dev server detection on Local sites that map the dev host to `::1` first — assets now load from the dev server (HMR) instead of falling back to `dist/`.

---

## [1.0.0] — 2026-10-20

### Added
- Initial project setup from dig.id starter theme (`theme-starter-vite` v2.0.1).
