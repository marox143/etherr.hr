# Project Structure

This document describes the cPanel-compatible directory structure of the Etherr website as of the current stable snapshot.

## Directory Layout

The project uses a **two-tier structure** with `public_html/` as the web root:

```
etherr-website/
├── public_html/              # WEB ROOT - publicly accessible
│   ├── index.html            # Homepage
│   ├── projekti.html         # Projects page
│   ├── about.html            # About page
│   ├── privacy.html          # Privacy policy
│   ├── *-demo.html           # Local demo pages used by the projects showcase
│   ├── style.css             # Global styles
│   ├── script.js             # Global JavaScript
│   ├── shared-header.js      # Shared navigation component
│   ├── debug-archive/        # Optional mobile-debug assets
│   ├── api/
│   │   ├── contact-intake.php  # Contact form endpoint
│   │   └── README.md
│   └── assets/               # Media and project assets
│       ├── images/           # Global UI assets
│       ├── clouds/           # Hero background images
│       ├── icons/            # Shared action-arrow mask assets
│       ├── content/          # Source/reference content files
│       ├── almagea/          # Project-specific assets
│       ├── dfa/
│       ├── juvy/             # Local Juvy hero media (mp4 + poster)
│       ├── juvy-site/        # Bundled Juvy WordPress snapshot
│       ├── keepgoing-site/   # Bundled Keep Going WordPress snapshot
│       ├── kota/             # Legacy showcase assets retained in repo
│       ├── projects-mobile/  # Lightweight preview imagery
│       ├── qr-digital-pricelist/
│       └── ripple/
├── .env                      # Environment configuration (OUTSIDE web root)
├── .env.example              # Environment template
├── composer.json             # PHP dependencies
├── composer.lock             # Dependency lock file
├── vendor/                   # Composer packages (OUTSIDE web root)
│   └── phpmailer/
├── var/                      # Runtime data (OUTSIDE web root)
│   ├── contact-rate-limit.json
│   └── contact-intake-log.ndjson
├── docs/                     # Documentation (OUTSIDE web root)
│   ├── PROJECT-STRUCTURE.md
│   ├── DEPLOYMENT-CHECKLIST.md
│   ├── SECURITY-CHECKLIST.md
│   ├── MAINTENANCE.md
│   ├── CODEBASE-AUDIT.md
│   ├── CPANEL-SETUP.md
│   ├── STABLE-SNAPSHOT-2026-04-23.md
│   ├── 01-services-content-spec.md
│   ├── 02-implementation-brief.md
│   └── 03-codex-starter-prompt.md
└── scripts/                  # Development scripts (OUTSIDE web root)
    ├── start-localhost3000.sh
    ├── stop-localhost3000.sh
    ├── check-site.sh
    ├── smoke-contact-api.sh
    └── build-hosting-package.sh
```

## Public vs. Private File Separation

### Public Files (in `public_html/`)

All files that need to be accessible via HTTP:
- HTML pages
- CSS and JavaScript files
- Images and media assets
- PHP API endpoints
- Self-hosted project demo snapshots and preview assets
- Optional debug helpers activated only by explicit query/localStorage flags

### Private Files (outside `public_html/`)

Sensitive files that should NOT be accessible via HTTP:
- `.env` - environment configuration with credentials
- `vendor/` - Composer dependencies (PHPMailer)
- `var/` - runtime data (rate limiting, submission logs)
- `docs/` - internal documentation
- `scripts/` - development and validation scripts
- `composer.json`, `composer.lock` - dependency manifests

## Security Benefits

This structure provides enhanced security:

1. **Credentials Protected**: `.env` file cannot be accessed via HTTP
2. **Logs Protected**: `var/` directory with rate limit and submission logs is not web-accessible
3. **Dependencies Protected**: `vendor/` directory prevents library enumeration
4. **Documentation Protected**: Internal docs remain private

## PHP Path Resolution

The PHP API (`public_html/api/contact-intake.php`) uses `dirname(__DIR__, 2)` to traverse from the API directory to the project root:

```php
// From: public_html/api/contact-intake.php
$rootDir = dirname(__DIR__, 2);  // Goes up: api/ -> public_html/ -> root
$env = loadEnvFile($rootDir . '/.env');
$autoload = $rootDir . '/vendor/autoload.php';
$storageDir = $rootDir . '/var';
```

## Local Development

The local development server serves from `public_html/`:

```bash
bash scripts/start-localhost3000.sh
# Server starts at http://localhost:3000
# Serving from: public_html/
```

## Deployment

Only `public_html/` contents are deployed to the cPanel web root:

```yaml
# .cpanel.yml
deployment:
  tasks:
    - export DEPLOYPATH=/home/sipandst/public_html/
    - /usr/bin/rsync -av --delete --exclude=".git" ./public_html/ $DEPLOYPATH
```

Sensitive files (`.env`, `vendor/`, `var/`) must be configured separately on the server outside the web root.

Project demos are part of the public runtime. In the current stable version that includes:

- top-level `*-demo.html` files
- local demo dependencies in `public_html/assets/juvy-site/`
- local demo dependencies in `public_html/assets/keepgoing-site/`
- QR pricelist assets in `public_html/assets/qr-digital-pricelist/`
- mobile preview images in `public_html/assets/projects-mobile/`

The optional `public_html/debug-archive/` files are also deployed with the site, but they stay inactive unless the projects page is opened with `?debug-mobile=1` or the matching localStorage flag is set.

## File Placement Rules

When adding new files:

- **Public files** (HTML, CSS, JS, images, public APIs) → `public_html/`
- **Self-hosted demo dependencies** (iframe assets, preview images, copied site snapshots) → `public_html/assets/`
- **Private files** (configs, logs, dependencies, docs) → project root (outside `public_html/`)
- **Path references** within `public_html/` → use relative paths
- **PHP paths** to root resources → use `dirname(__DIR__, 2)` from `public_html/api/`
- **Generated packages / ad-hoc archives** → local output only, keep out of version control unless explicitly required

## Improvements Implemented

1. Migrated to cPanel-compatible `public_html/` structure
2. Moved sensitive files outside web root for security
3. Updated PHP path resolution for new structure
4. Updated local development scripts to serve from `public_html/`
5. Updated deployment configuration to deploy only `public_html/`
6. Added self-hosted project demo assets for Juvy, Keep Going, and Keef
7. Added mobile project preview assets and optional debug archive support
8. Added a packaging script for cPanel-ready hosting bundles
9. Updated all documentation to reflect the current structure

## Optional Future Refactors

These are safe to do later in a dedicated cleanup pass:

1. Split `script.js` into modules (`core`, `projects`, `contact-intake`, `i18n`)
2. Split `style.css` into section-based files and combine in build step
3. Move large reference-only assets from `assets/` to `archive/` to keep production bundle lean
4. Add build process for asset optimization (minification, compression)

For prioritized next-step refactors, see `docs/CODEBASE-AUDIT.md`.
