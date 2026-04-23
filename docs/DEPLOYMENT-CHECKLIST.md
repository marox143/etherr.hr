# Deployment Checklist

Use this checklist before publishing to production.

## 1) Server Prerequisites

- PHP 8.1+ (8.5 tested locally)
- Composer available during deploy or CI build step
- Outbound SMTP on port `587` enabled
- SSH access for server-side configuration

## 2) cPanel Configuration

### 2.1 Document Root Setup

Configure cPanel to serve from `public_html/`:

1. Log into cPanel
2. Navigate to "Domains" or "Document Root" settings
3. Set document root to: `/home/username/public_html/`
4. Save changes and verify configuration

**Note**: The project structure separates public files (in `public_html/`) from sensitive files (outside web root).

### 2.2 PHP Version

1. Navigate to "Select PHP Version" or "MultiPHP Manager" in cPanel
2. Select PHP 8.1 or higher
3. Apply to your domain

## 3) App Files Deployment

### 3.1 Upload Files

Upload all project files to your cPanel account root directory (e.g., `/home/username/`):

- `public_html/` - Web-accessible files (HTML, CSS, JS, API, assets)
- `.env` - Environment configuration (OUTSIDE web root)
- `composer.json` and `composer.lock` - Dependency definitions (OUTSIDE web root)
- `var/` - Runtime storage directory (OUTSIDE web root)

**Security Note**: Sensitive files (`.env`, `vendor/`, `var/`) remain outside `public_html/` and are not web-accessible.

Optional local packaging flow:

```bash
bash scripts/build-hosting-package.sh
```

This creates a ready-to-upload zip in `dist/` after dependencies are installed locally.

### 3.2 Install Dependencies

Connect via SSH and run:

```bash
cd /home/username/
composer install --no-dev --optimize-autoloader
```

This installs PHPMailer and other dependencies into `vendor/` directory outside the web root.

## 4) Environment Config

Create `.env` in project root (OUTSIDE `public_html/`) from `.env.example` and fill:

- `MAIL_TRANSPORT=smtp`
- `MAIL_TO`
- `MAIL_FROM`
- `MAIL_FROM_NAME`
- `SMTP_HOST`
- `SMTP_PORT=587`
- `SMTP_ENCRYPTION=tls`
- `SMTP_AUTH=true`
- `SMTP_USERNAME`
- `SMTP_PASSWORD`
- `ALLOWED_ORIGINS` (exact domain list)
- `TURNSTILE_SECRET_KEY` (recommended)
- `TURNSTILE_ENFORCED=true`

## 5) Frontend Turnstile Key

In `public_html/index.html`, set:

- `window.ETHERR_CONTACT_CONFIG.turnstileSiteKey`
- `window.ETHERR_CONTACT_CONFIG.requireTurnstile`

## 6) Storage and Permissions

### 6.1 Create var/ Directory

Ensure `var/` directory exists in project root (OUTSIDE `public_html/`):

```bash
cd /home/username/
mkdir -p var
chmod 755 var
```

### 6.2 Set Permissions

The `var/` directory must be writable by PHP:

```bash
chmod 755 var/
```

Runtime files (rate limit, logs) will be created automatically with appropriate permissions.

**Security Note**: The `var/` directory is outside `public_html/` and is not web-accessible. This protects rate limit data and submission logs from public access.

## 7) DNS and Email Deliverability

- Correct MX records for Exchange
- SPF record includes Microsoft sender
- DKIM enabled and CNAMEs added
- DMARC configured

## 8) Verification

- Submit a test inquiry via the contact form
- Confirm API response `status` is `sent` or `queued`
- Check `var/contact-intake-log.ndjson` entry (if logging enabled)
- Confirm message arrival in mailbox + spam folder check
- Verify all pages load correctly from `public_html/`
- Verify self-hosted project demos load correctly from local bundled assets
- Check browser console for any errors

## File Structure Reference

```
/home/username/                    # Project root (cPanel account root)
├── .env                           # Environment config (PROTECTED - outside web root)
├── composer.json                  # Dependencies (PROTECTED - outside web root)
├── composer.lock                  # Dependency lock (PROTECTED - outside web root)
├── vendor/                        # Composer packages (PROTECTED - outside web root)
│   └── phpmailer/
├── var/                           # Runtime storage (PROTECTED - outside web root)
│   ├── contact-rate-limit.json
│   └── contact-intake-log.ndjson
└── public_html/                   # WEB ROOT - publicly accessible
    ├── index.html
    ├── projekti.html
    ├── about.html
    ├── privacy.html
    ├── *-demo.html
    ├── style.css
    ├── script.js
    ├── api/
    │   └── contact-intake.php
    ├── assets/
    └── debug-archive/
```
