# Security Checklist

## Project Structure Security

### Files Outside Web Root (Protected)

The cPanel hosting structure places sensitive files outside `public_html/`, making them inaccessible via HTTP:

- **`.env`** - Environment configuration with credentials (PROTECTED)
- **`vendor/`** - Composer dependencies including PHPMailer (PROTECTED)
- **`var/`** - Runtime data: rate limits, submission logs (PROTECTED)
- **`docs/`** - Internal documentation (PROTECTED)
- **`scripts/`** - Development and validation scripts (PROTECTED)

### Files Inside Web Root (Public)

Only public-facing assets are in `public_html/`:

- HTML pages (`index.html`, `projekti.html`, etc.)
- CSS and JavaScript files
- Images and media assets
- API endpoint (`api/contact-intake.php`)
- Demo pages

**Security Benefit**: Credentials, logs, and dependencies cannot be accessed via browser, even if web server misconfiguration occurs.

## Contact Intake API

Implemented:

- Honeypot bot field validation
- Server-side consent requirement
- IP-based rate limiting
- Optional Cloudflare Turnstile validation
- SMTP transport with authentication support
- Structured API error codes

## AI Assistant

Implemented:

- Server-side OpenAI API calls only
- MariaDB-backed sessions, conversations, messages, settings and logs
- Same-origin checks on public assistant endpoints
- IP-based assistant message rate limiting
- Admin panel protected by `.env` username and password hash
- Admin CSRF protection and HTTP-only session cookie

## Required Before Production

1. Set `ALLOWED_ORIGINS` in `.env` to exact production domains.
2. Enable Turnstile:
   - `TURNSTILE_SECRET_KEY` in `.env`
   - site key in `window.ETHERR_CONTACT_CONFIG` (`public_html/index.html`)
3. Keep `.env` private and never commit it (stored outside `public_html/`).
4. Ensure `var/` is not publicly readable (stored outside `public_html/`).
5. Ensure SMTP credentials are app-specific and least-privilege.
6. Set `STORE_SUBMISSIONS=true/false` based on privacy policy.
7. Configure `OPENAI_API_KEY`, database credentials, and assistant admin credentials for `/admin/assistant/`.

## File Location Security

### Environment Configuration (`.env`)

- **Location**: Project root (e.g., `/home/username/.env`)
- **Protection**: Outside `public_html/`, not web-accessible
- **Contains**: SMTP credentials, API keys, security settings
- **Permissions**: `chmod 600 .env` (read/write for owner only)

### Composer Dependencies (`vendor/`)

- **Location**: Project root (e.g., `/home/username/vendor/`)
- **Protection**: Outside `public_html/`, not web-accessible
- **Contains**: PHPMailer and other libraries
- **Benefit**: Prevents library enumeration and version disclosure

### Runtime Storage (`var/`)

- **Location**: Project root (e.g., `/home/username/var/`)
- **Protection**: Outside `public_html/`, not web-accessible
- **Contains**: Rate limit data, submission logs (if enabled)
- **Permissions**: `chmod 755 var/` (writable by PHP, not web-accessible)

## Recommended Hardening

1. Add web server rate limiting and fail2ban rules.
2. Add CSP and stricter security headers at web-server level.
3. Add log rotation/retention policy for `var/contact-intake-log.ndjson`.
4. Add uptime checks for API endpoint and SMTP relay health.
5. Verify `.env`, `vendor/`, and `var/` are outside `public_html/` after deployment.
6. Set restrictive file permissions:
   ```bash
   chmod 600 .env
   chmod 755 var/
   chmod 644 var/*.json var/*.ndjson  # If files exist
   ```

## Privacy Note

Submitted payload can include personal data. If you keep submission logging enabled:

- document legal basis and retention period,
- restrict access to logs,
- purge logs on schedule.
