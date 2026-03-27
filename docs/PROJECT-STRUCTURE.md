# Project Structure Review

This file documents the current layout and practical improvements already applied.

## Current Layout

- Root page files:
  - `index.html`
  - `projekti.html`
  - `about.html`
- Global frontend logic:
  - `style.css`
  - `script.js`
  - `shared-header.js`
- API:
  - `api/contact-intake.php`
- Runtime data:
  - `var/`
- Build/dependency:
  - `composer.json`
  - `composer.lock`
  - `vendor/` (generated)
- Utilities:
  - `scripts/start-localhost3000.sh`
  - `scripts/stop-localhost3000.sh`
  - `scripts/check-site.sh`
  - `scripts/smoke-contact-api.sh`

## Improvements Implemented

1. Added root `README.md` for onboarding and deployment.
2. Added `docs/` folder for structure, security, and deploy documentation.
3. Added validation scripts for repeatable checks.
4. Strengthened `.gitignore` to avoid committing runtime/sensitive artifacts.
5. Added `docs/MAINTENANCE.md` for repeatable update workflow.

## Optional Next Refactor (Recommended Later)

These are safe to do later in a dedicated cleanup pass:

1. Move demo pages to `demos/` and update all iframe/src references.
2. Split `script.js` into modules (`core`, `projects`, `contact-intake`, `i18n`).
3. Split `style.css` into section-based files and combine in build step.
4. Move large reference-only assets from `assets/` to `archive/` to keep production bundle lean.

No structural moves were performed in this pass to avoid breaking existing references.

For prioritized next-step refactors, see `docs/CODEBASE-AUDIT.md`.
