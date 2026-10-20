# Workflow — dig.id Starter Theme

Canonical description of the two flows around this theme. The monday.com guide
mirrors this document — when this changes, update the guide.

---

## Flow A — Working on the starter (this repo)

Day-to-day improvements to the starter itself.

```
1. Make the change (follow CLAUDE.md standards: PHPDoc, HTML5, escaping, i18n)
2. npm run php:lint                  → must pass
3. Update CHANGELOG.md               → new [X.Y.Z] section (PATCH fix / MINOR feature / MAJOR breaking)
4. Update Version: in style.css     → same number
5. Commit: "vX.Y.Z: short summary"  → push
6. Tag the release: git tag vX.Y.Z && git push origin vX.Y.Z
   (or GitHub Desktop: History → right-click commit → Create Tag → Push)
   → GitHub Action builds the theme zip and publishes the Release automatically
```

> With Claude Code: the `/release` skill runs steps 2–6 for you.

**Check it worked:** repo → Actions tab (workflow "Release" green) → Releases
page shows `vX.Y.Z` with `kitchen-sink.zip` attached.

---

## Flow B — Starting a new client project

```
 1. Download the zip from the latest GitHub Release of the starter
    (or npm run zip locally)
 2. Extract into wp-content/themes/ of the new WP install (Local by WPEngine)
 3. Follow SETUP.md steps 1–9: rename folder, find & replace slugs/prefix,
    style.css metadata, dev URL, colours/fonts, nav menus, reset versioning
    (project restarts at 1.0.0, changelog notes the starter version used)
    → With Claude Code: the /setup-project skill does steps 1–9 interactively
 4. npm install && npm run prod      → first build (then npm run dev while developing)
 5. Create the new project's git repository and push initial commit
 6. Run /figma-analysis with the project's Figma link
    → generates figma-architecture-analysis.txt (build roadmap)
 7. Update the project's CLAUDE.md: real CPTs, ACF field groups,
    project-specific plugins (the starter examples are placeholders)
 8. Build: sections → components (with $args) → modules, following the
    analysis file and CLAUDE.md standards. Version each delivery (Flow A
    steps 2–6 apply to the project repo too, minus the release zip).
 9. Pre-launch: wp i18n make-pot . languages/<prefix>.pot, wp-config
    hardening (see SETUP.md), WP Rocket + SG Security active on live
10. Launch 🚀 → with Claude Code: the /deploy skill (production build,
    versioning, changelog, short commit, push only after confirmation)
```

---

## Available Claude Code skills (ship with the theme in .claude/skills/)

| Skill | What it does | When |
|---|---|---|
| `/setup-project` | Runs SETUP.md steps 1–9 interactively (asks name, slug, prefix, URL) | Once, at project start |
| `/figma-analysis` | Reads the Figma file and generates `figma-architecture-analysis.txt` | Project start + design updates |
| `/release` | Lint → changelog → version sync → commit message → tag instructions | Every version close |
| `/deploy` | Detects build/lint/version setup → SemVer → changelog → production build → short commit → push to the production branch (only with explicit confirmation) | Project repos, every go-live |

---

## Quick reference

| Task | Command |
|---|---|
| Dev server (HMR, no dist/ output) | `npm run dev` |
| Dev build + watch (writes dist/, no reload) | `npm run watch` |
| Production build (writes dist/) | `npm run prod` |
| Lint / fix PHP | `npm run php:lint` / `npm run php:fix` |
| Theme zip (local) | `npm run zip` |
| Translation template | `wp i18n make-pot . languages/kitchensink.pot --domain=kitchensink` |
| Publish starter release | `git tag vX.Y.Z && git push origin vX.Y.Z` |
