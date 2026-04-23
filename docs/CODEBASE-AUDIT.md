# Codebase Audit (2026-04-23)

This audit summarizes the current stable snapshot after the recent manual showcase, services, and mobile-stability changes.

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
5. Centralized homepage services and intake data in `SERVICE_CATALOG`.
6. Converted major project demos from external dependencies to self-hosted local snapshots.
7. Added mobile demo overlay behavior and touch support for reservation demos.
8. Added a cPanel packaging helper via `scripts/build-hosting-package.sh`.

## Current Risks / Growth Pressure

1. Large frontend files:
   - `style.css` and `script.js` are both very large and handling many responsibilities.
2. Imported showcase snapshots are heavy:
   - `public_html/assets/juvy-site/` and `public_html/assets/keepgoing-site/` add a large number of bundled assets that need deliberate refresh discipline.
3. Mixed production and support assets:
   - `public_html/debug-archive/`, ad-hoc zip exports, and local build/test artifacts can be staged accidentally unless release scope is checked carefully.
4. Legacy showcase code remains in the runtime bundle:
   - Kota-specific translations, styles, and JavaScript are still present even though the current projects page no longer renders the Kota card.

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
3. Extract project showcase configuration into a dedicated data module instead of mixing project copy, overlay maps, and behavior inside `script.js`.
4. Decide whether inactive Kota showcase code should be removed or formally retained as an archived variant.
5. Add an image and video optimization pass for mobile preview assets and imported project screenshots.

## Operational Recommendation

Before each deploy run:

```bash
bash scripts/check-site.sh
bash scripts/smoke-contact-api.sh
```

Also verify:

- the projects page on mobile widths
- reservation overlay and demo behavior
- Keef QR demo stability on iOS-class devices
