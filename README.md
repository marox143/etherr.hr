# Etherr Website

Marketing website for Etherr with multilingual content, animated hero/project showcases, and a guided contact intake form connected to a hardened PHP API.

## Stack

- Static frontend: HTML + CSS + vanilla JavaScript
- Shared behavior: `shared-header.js`, `script.js`
- Backend endpoint: PHP (`api/contact-intake.php`)
- Mail transport: SMTP via PHPMailer (`composer`)

## Main Pages

- `index.html` - homepage (hero, services, guided contact intake)
- `projekti.html` - projects showcase with interactive mockups
- `about.html` - about page

Additional local demo pages are used as embedded project sources.

## Project Structure

- `assets/` - project media and imported reference/demo assets
- `api/` - contact intake endpoint and API docs
- `scripts/` - local server and validation scripts
- `var/` - runtime data (rate limit + intake logs), intentionally not committed
- `vendor/` - Composer packages (not committed)

For a detailed map, cleanup guidance, and maintenance workflow, see:

- `docs/PROJECT-STRUCTURE.md`
- `docs/MAINTENANCE.md`
- `docs/CODEBASE-AUDIT.md`

## Local Development

Start local server:

```bash
bash scripts/start-localhost3000.sh
```

Stop local server:

```bash
bash scripts/stop-localhost3000.sh
```

Run code and endpoint checks:

```bash
bash scripts/check-site.sh
```

Run API smoke test:

```bash
bash scripts/smoke-contact-api.sh
```

## Contact Form Configuration

1. Copy `.env.example` to `.env`
2. Fill SMTP and security values
3. Keep `.env` out of git (already ignored)

See:

- `api/README.md`
- `docs/DEPLOYMENT-CHECKLIST.md`
- `docs/SECURITY-CHECKLIST.md`

## Deployment Notes

- Use PHP-capable hosting
- Run `composer install --no-dev --optimize-autoloader` on server
- Ensure outbound SMTP (port `587`) is allowed
- Configure DNS email records (`SPF`, `DKIM`, `DMARC`)
- Prefer storing `var/` outside public web root in production

## Quick Quality Workflow

Before each release:

1. `bash scripts/check-site.sh`
2. `bash scripts/smoke-contact-api.sh`
3. Visual pass on `index.html`, `projekti.html`, and `about.html` in all three languages (`HR`, `EN`, `DE`)

## Important

- Do not commit real credentials to the repository
- Do not expose `var/contact-intake-log.ndjson` publicly
