# Maintenance Guide

This guide keeps the project structure consistent and prevents regressions as content grows.

## File Placement Rules

- Page shells stay at root:
  - `index.html`
  - `projekti.html`
  - `about.html`
- Shared frontend logic:
  - `script.js`
  - `style.css`
  - `shared-header.js`
- API and server-side handlers:
  - `api/`
- Runtime-only data (never commit real logs):
  - `var/`
- Utility scripts:
  - `scripts/`
- Design/media assets:
  - `assets/`

## Asset Conventions

- Put global UI assets in `assets/images/`.
- Keep project-specific assets grouped under `assets/<project-name>/`.
- Prefer lowercase kebab-case filenames (`project-shot-01.jpg`).
- Remove unused assets when replacing old previews.

## Page Update Workflow

When adding or editing a project card:

1. Update markup in `projekti.html`.
2. Update language keys in `script.js` (HR/EN/DE).
3. Validate media paths under `assets/`.
4. Run:
   - `bash scripts/check-site.sh`
   - `bash scripts/smoke-contact-api.sh`

## Contact Intake Workflow

For contact form/API changes:

1. Update frontend behavior/translations in `script.js` and `index.html`.
2. Update endpoint logic in `api/contact-intake.php`.
3. Reflect new fields/settings in:
   - `.env.example`
   - `api/README.md`
4. Run `php -l api/contact-intake.php` and the two scripts above.

## Pre-Deploy Checklist

Always confirm:

1. `.env` is not committed.
2. SMTP and Turnstile settings are set for production.
3. `var/` is writable by PHP and protected from public access.
4. `composer install --no-dev --optimize-autoloader` has run on target server.

