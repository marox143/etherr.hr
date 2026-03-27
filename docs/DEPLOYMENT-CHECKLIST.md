# Deployment Checklist

Use this checklist before publishing to production.

## 1) Server Prerequisites

- PHP 8.1+ (8.5 tested locally)
- Composer available during deploy or CI build step
- Outbound SMTP on port `587` enabled

## 2) App Files

- Upload site files
- Run:

```bash
composer install --no-dev --optimize-autoloader
```

## 3) Environment Config

Create `.env` from `.env.example` and fill:

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

## 4) Frontend Turnstile Key

In `index.html`, set:

- `window.ETHERR_CONTACT_CONFIG.turnstileSiteKey`
- `window.ETHERR_CONTACT_CONFIG.requireTurnstile`

## 5) Storage and Access

- Ensure `var/` exists and is writable by PHP
- Prefer placing runtime storage outside public web root if possible
- If `var/` is public-root-based, enforce deny rules (`.htaccess` or server-level)

## 6) DNS and Email Deliverability

- Correct MX records for Exchange
- SPF record includes Microsoft sender
- DKIM enabled and CNAMEs added
- DMARC configured

## 7) Verification

- Submit a test inquiry
- Confirm API response `status` is `sent` or `queued`
- Check `var/contact-intake-log.ndjson` entry
- Confirm message arrival in mailbox + spam folder check
