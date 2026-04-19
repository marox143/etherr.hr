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
- **Demo pages**: All demo content in `demos/`

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
- Keep project-specific assets grouped under `public_html/assets/<project-name>/`.
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
   - Project assets: `public_html/assets/<project-name>/`
2. Reference using relative path from HTML (e.g., `src="assets/images/new-image.jpg"`)
3. Optimize images before adding (compress, resize as needed)

## Page Update Workflow

When adding or editing a project card:

1. Update markup in `public_html/projekti.html`.
2. Update language keys in `public_html/script.js` (HR/EN/DE).
3. Validate media paths under `public_html/assets/`.
4. Run:
   - `bash scripts/check-site.sh`
   - `bash scripts/smoke-contact-api.sh`

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
7. Test all pages load correctly after deployment.

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
    ├── style.css
    ├── script.js
    ├── api/
    ├── assets/
    └── demos/
```

