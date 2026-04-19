# Contact Intake Endpoint

The guided contact form posts JSON to:

- `POST /api/contact-intake.php`

**File Location**: `public_html/api/contact-intake.php` (web-accessible)

## Project Structure

The API endpoint is located in `public_html/api/` (web root), while dependencies and configuration are outside the web root for security:

```
/home/username/                    # Project root
├── .env                           # Environment config (PROTECTED - outside web root)
├── composer.json                  # Dependencies (PROTECTED - outside web root)
├── vendor/                        # Composer packages (PROTECTED - outside web root)
│   └── phpmailer/
├── var/                           # Runtime storage (PROTECTED - outside web root)
│   ├── contact-rate-limit.json
│   └── contact-intake-log.ndjson
└── public_html/                   # WEB ROOT - publicly accessible
    └── api/
        └── contact-intake.php     # This endpoint
```

**Security Benefit**: Credentials (`.env`), dependencies (`vendor/`), and logs (`var/`) are not web-accessible.

## Security and delivery behavior

The endpoint now includes:

1. Required field validation (`company`, `name`, `email`, `details`)
2. Mandatory server-side consent check (`consent === true`)
3. Honeypot bot filter (`honeypot`)
4. IP rate limiting (configurable by `.env`)
5. Optional Cloudflare Turnstile verification
6. SMTP sending (PHPMailer + `.env` config)
7. Safe queue fallback when mail sending fails (`status: queued`)
8. Optional submission storage in NDJSON log file

## Environment configuration

Copy `.env.example` to `.env` **in project root** (outside `public_html/`) and fill values:

- `MAIL_TRANSPORT` (`smtp` recommended, `mail` optional)
- `MAIL_TO`, `MAIL_FROM`, `MAIL_FROM_NAME`
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_AUTH`, `SMTP_USERNAME`, `SMTP_PASSWORD`
- `RATE_LIMIT_WINDOW_SEC`, `RATE_LIMIT_MAX_REQUESTS`
- `TURNSTILE_SECRET_KEY`, `TURNSTILE_ENFORCED`
- `ALLOWED_ORIGINS`
- `INTAKE_STORAGE_DIR`, `STORE_SUBMISSIONS`

**File Location**: `/home/username/.env` (outside `public_html/`, not web-accessible)

Default storage path is `var/` in project root (outside `public_html/`).

## Dependencies

SMTP transport uses PHPMailer.

Install dependencies in project root (outside `public_html/`):

```bash
cd /home/username/
composer install --no-dev --optimize-autoloader
```

This creates `vendor/` directory in project root (outside `public_html/`, not web-accessible).

## Path Resolution

The API endpoint uses `dirname(__DIR__, 2)` to reach project root from `public_html/api/`:

```php
// From public_html/api/contact-intake.php
$rootDir = dirname(__DIR__, 2);  // Go up: api/ -> public_html/ -> root

// Load environment from project root
$env = loadEnvFile($rootDir . '/.env');

// Load Composer autoloader from project root
require_once $rootDir . '/vendor/autoload.php';

// Use storage directory from project root
$storageDir = $rootDir . '/var';
```

## Local testing

Use project scripts:

- `bash scripts/start-localhost3000.sh`
- `bash scripts/stop-localhost3000.sh`
- `bash scripts/check-site.sh`
- `bash scripts/smoke-contact-api.sh`

Then test:

- Site: `http://localhost:3000/`
- Endpoint: `POST http://localhost:3000/api/contact-intake.php`

## Frontend payload shape

- `version`
- `submittedAt`
- `locale`
- `source` (`page`, `url`, `referrer`, `userAgent`)
- `project` (`services[]`, `projectType`, `timeline`, `details`)
- `contact` (`company`, `website`, `name`, `email`, `phone`, `preferredContact`)
- `consent`
- `turnstileToken` (optional unless Turnstile is enforced)
- `honeypot`
