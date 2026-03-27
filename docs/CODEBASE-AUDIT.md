# Codebase Audit (2026-03-27)

This audit summarizes practical improvements after a full structure and quality pass.

## Already Improved

1. Added automated project checks:
   - `scripts/check-site.sh`
   - `scripts/smoke-contact-api.sh`
2. Added reference-integrity validation for local `src`/`href` targets.
3. Added maintainability docs:
   - root `README.md`
   - `docs/MAINTENANCE.md`
   - deployment/security docs.
4. Standardized script executability in `scripts/`.

## Current Risks / Growth Pressure

1. Large frontend files:
   - `style.css` and `script.js` are both very large and handling many responsibilities.
2. Root contains many demo pages:
   - easier to break references over time without explicit ownership.
3. Heavy media footprint:
   - project assets are sizable and can impact page load if not lazy-loaded consistently.

## Recommended Next Refactor (Safe, Incremental)

1. Split JavaScript by responsibility:
   - `js/core.js`
   - `js/projects.js`
   - `js/contact-intake.js`
   - `js/i18n.js`
2. Split CSS into layered files:
   - `css/base.css`
   - `css/header.css`
   - `css/home.css`
   - `css/projects.css`
   - `css/about.css`
3. Move demo HTML sources to `demos/` and keep only page shells at root.
4. Add image optimization pass (`webp/avif` where possible) for non-transparent screenshots.

## Operational Recommendation

Before each deploy run:

```bash
bash scripts/check-site.sh
bash scripts/smoke-contact-api.sh
```

