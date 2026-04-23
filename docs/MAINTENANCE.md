# Maintenance Guide

This guide keeps the project structure consistent and prevents regressions as content grows.

## File Placement Rules

### Public Files (Inside `public_html/`)

Place these files inside `public_html/` directory (web-accessible):

- **HTML pages**: `index.html`, `projekti.html`, `about.html`, `privacy.html`
- **CSS files**: `style.css` and any new stylesheets
- **JavaScript files**: `script.js`, `shared-header.js` and any new scripts
- **API endpoints**: `api/contact-intake.php` and any new API files
- **Media assets**: All images, videos, fonts in `assets/`
- **Demo pages**: Top-level `*-demo.html` files used by the projects showcase
- **Optional diagnostics**: `debug-archive/` files only when they are intentionally part of the browser-accessible debug flow

### Private Files (Outside `public_html/`)

Keep these files in project root (NOT web-accessible):

- **Environment config**: `.env` (never commit, never expose)
- **Dependencies**: `vendor/` (installed via Composer)
- **Runtime data**: `var/` (rate limits, logs)
- **Documentation**: `docs/` (internal guides)
- **Scripts**: `scripts/` (development and validation tools)
- **Config files**: `composer.json`, `composer.lock`, `.gitignore`, `.cpanel.yml`

**Security Rule**: If a file contains credentials, logs, or internal documentation, it must stay outside `public_html/`.

## Asset Conventions

- Put global UI assets in `public_html/assets/images/`.
- Put shared showcase/action icons in `public_html/assets/icons/`.
- Keep project-specific assets grouped under `public_html/assets/<project-name>/`.
- Keep imported self-hosted demo snapshots grouped under dedicated folders such as `public_html/assets/juvy-site/` and `public_html/assets/keepgoing-site/`.
- Keep lightweight mobile preview images in `public_html/assets/projects-mobile/`.
- Prefer lowercase kebab-case filenames (`project-shot-01.jpg`).
- Remove unused assets when replacing old previews.

## Adding New Files

### New HTML Page

1. Create file in `public_html/` (e.g., `public_html/services.html`)
2. Use relative paths for assets (e.g., `href="style.css"`, `src="assets/images/logo.png"`)
3. Update navigation in `shared-header.js` if needed
4. Test locally with `bash scripts/start-localhost3000.sh`

### New CSS or JavaScript File

1. Create file in `public_html/` (e.g., `public_html/custom.css`)
2. Reference from HTML using relative path (e.g., `<link href="custom.css">`)
3. Verify file loads without 404 errors

### New API Endpoint

1. Create file in `public_html/api/` (e.g., `public_html/api/newsletter.php`)
2. Use `dirname(__DIR__, 2)` to reach project root for `.env`, `vendor/`, `var/`
3. Example path resolution:
   ```php
   $rootDir = dirname(__DIR__, 2);  // From api/ -> public_html/ -> root
   $env = loadEnvFile($rootDir . '/.env');
   require_once $rootDir . '/vendor/autoload.php';
   ```
4. Test endpoint with validation scripts

### New Media Asset

1. Place in appropriate directory:
   - Global assets: `public_html/assets/images/`
   - Shared showcase icons: `public_html/assets/icons/`
   - Project assets: `public_html/assets/<project-name>/`
   - Mobile preview assets: `public_html/assets/projects-mobile/`
2. Reference using relative path from HTML (e.g., `src="assets/images/new-image.jpg"`)
3. Optimize images before adding (compress, resize as needed)

## Project Showcase Workflow

When adding or editing a project card in `public_html/projekti.html`:

1. Update markup in `public_html/projekti.html`.
2. Update language keys in `public_html/script.js` (HR/EN/DE).
3. Validate media paths under `public_html/assets/`.
4. For iframe previews, prefer `data-project-embed-src` so `script.js` can lazy-load them correctly.
5. For mobile-first previews, add or refresh matching assets in `public_html/assets/projects-mobile/`.
6. If the project uses the fullscreen mobile demo overlay, update the overlay source maps in `public_html/script.js`.
7. Run:
   - `bash scripts/check-site.sh`
   - `bash scripts/smoke-contact-api.sh`

## Self-Hosted Demo Snapshot Workflow

When refreshing imported project demos such as Juvy or Keep Going:

1. Keep the public entry page in the root (`public_html/juvy-demo.html`, `public_html/keepgoing-demo.html`, etc.).
2. Rewrite remote asset URLs so the demo resolves against bundled local assets in `public_html/assets/<snapshot-folder>/`.
3. Prefer local copies for CSS, JS, fonts, images, and AJAX endpoints that the snapshot requires to render.
4. Keep the showcase functional without depending on the production domain being online.
5. Verify the demo still loads inside the `projekti.html` iframe and in the mobile overlay where relevant.

## Services Section Workflow

The current services section and intake options share one source of truth:

- `SERVICE_CATALOG` in `public_html/script.js`

When changing services:

1. Update `SERVICE_CATALOG`.
2. Verify the services section and contact intake both reflect the same taxonomy.
3. Keep translations aligned across `hr`, `en`, and `de`.
4. Review mobile detail behavior on narrower breakpoints.

## Contact Intake Workflow

For contact form/API changes:

1. Update frontend behavior/translations in `public_html/script.js` and `public_html/index.html`.
2. Update endpoint logic in `public_html/api/contact-intake.php`.
3. Reflect new fields/settings in:
   - `.env.example` (in project root)
   - `public_html/api/README.md`
4. Run `php -l public_html/api/contact-intake.php` and the two scripts above.

## Backup Procedures

### Local Development Backup

Before major changes:

```bash
git checkout -b feature/my-changes
git add .
git commit -m "Backup before changes"
```

### Production Backup

Backup both public and private files:

```bash
# On server via SSH
cd /home/username/
tar -czf backup-$(date +%Y%m%d).tar.gz \
  public_html/ \
  .env \
  var/ \
  composer.json \
  composer.lock

# Download backup
scp username@server:/home/username/backup-*.tar.gz ./backups/
```

**Important**: Backup includes:
- `public_html/` - All web-accessible files
- `.env` - Environment configuration
- `var/` - Runtime data (rate limits, logs)
- `composer.json`, `composer.lock` - Dependency definitions

### Restore from Backup

```bash
# On server via SSH
cd /home/username/
tar -xzf backup-YYYYMMDD.tar.gz
```

## Pre-Deploy Checklist

Always confirm:

1. `.env` is not committed (stored outside `public_html/`).
2. SMTP and Turnstile settings are set for production in `.env`.
3. `var/` is writable by PHP and protected (outside `public_html/`).
4. `composer install --no-dev --optimize-autoloader` has run on target server.
5. All files in `public_html/` use relative paths for assets.
6. API endpoints use `dirname(__DIR__, 2)` to reach project root.
7. Self-hosted project demos load without depending on their original production domains.
8. Test all pages load correctly after deployment.

## Packaging and Release Artifacts

To create a cPanel-ready package locally:

```bash
bash scripts/build-hosting-package.sh
```

Notes:

- The script expects `vendor/`, `.env.example`, `composer.json`, and `composer.lock` to exist.
- It creates a zip in `dist/`.
- Keep generated archives, editor settings, and test-result output out of release commits unless they are intentionally part of the deliverable.

## Directory Structure Reference

```
/home/username/                    # Project root
├── .env                           # PROTECTED - outside web root
├── composer.json                  # PROTECTED - outside web root
├── vendor/                        # PROTECTED - outside web root
├── var/                           # PROTECTED - outside web root
├── docs/                          # PROTECTED - outside web root
├── scripts/                       # PROTECTED - outside web root
└── public_html/                   # PUBLIC - web-accessible
    ├── index.html
    ├── projekti.html
    ├── *-demo.html
    ├── style.css
    ├── script.js
    ├── api/
    ├── assets/
    └── debug-archive/
```
