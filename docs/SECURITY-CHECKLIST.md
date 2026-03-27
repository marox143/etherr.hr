# Security Checklist

## Contact Intake API

Implemented:

- Honeypot bot field validation
- Server-side consent requirement
- IP-based rate limiting
- Optional Cloudflare Turnstile validation
- SMTP transport with authentication support
- Structured API error codes

## Required Before Production

1. Set `ALLOWED_ORIGINS` in `.env` to exact production domains.
2. Enable Turnstile:
   - `TURNSTILE_SECRET_KEY` in `.env`
   - site key in `window.ETHERR_CONTACT_CONFIG` (`index.html`)
3. Keep `.env` private and never commit it.
4. Ensure `var/` is not publicly readable.
5. Ensure SMTP credentials are app-specific and least-privilege.
6. Set `STORE_SUBMISSIONS=true/false` based on privacy policy.

## Recommended Hardening

1. Add web server rate limiting and fail2ban rules.
2. Add CSP and stricter security headers at web-server level.
3. Add log rotation/retention policy for `var/contact-intake-log.ndjson`.
4. Add uptime checks for API endpoint and SMTP relay health.

## Privacy Note

Submitted payload can include personal data. If you keep submission logging enabled:

- document legal basis and retention period,
- restrict access to logs,
- purge logs on schedule.
