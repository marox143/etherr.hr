# Contact Intake Endpoint

The guided contact form posts JSON to:

- `POST /api/contact-intake.php`

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

Copy `.env.example` to `.env` and fill values:

- `MAIL_TRANSPORT` (`smtp` recommended, `mail` optional)
- `MAIL_TO`, `MAIL_FROM`, `MAIL_FROM_NAME`
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_AUTH`, `SMTP_USERNAME`, `SMTP_PASSWORD`
- `RATE_LIMIT_WINDOW_SEC`, `RATE_LIMIT_MAX_REQUESTS`
- `TURNSTILE_SECRET_KEY`, `TURNSTILE_ENFORCED`
- `ALLOWED_ORIGINS`
- `INTAKE_STORAGE_DIR`, `STORE_SUBMISSIONS`

Default storage path is `var/`.

## Dependencies

SMTP transport uses PHPMailer.

Install dependencies in project root:

- `composer install --no-dev --optimize-autoloader`

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
