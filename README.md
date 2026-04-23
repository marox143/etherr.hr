# Etherr Website

Marketing website for Etherr with multilingual content, animated hero/project showcases, self-hosted demo embeds, and a guided contact intake form connected to a hardened PHP API.

The current stable snapshot also includes:

- a reworked `projekti.html` flow with Keef, Keep Going, reservation, Almagea, Juvy, Ripple, and DFA showcases
- a centralized services and intake taxonomy in `public_html/script.js`
- self-hosted local demo assets for Keef, Juvy, and Keep Going
- mobile hardening for reservation demos, project overlays, and the Keef QR pricelist

## Stack

- Static frontend: HTML + CSS + vanilla JavaScript
- Shared behavior: `shared-header.js`, `script.js`
- Shared services data: `SERVICE_CATALOG` in `public_html/script.js`
- Backend endpoint: PHP (`api/contact-intake.php`)
- Mail transport: SMTP via PHPMailer (`composer`)

## Main Pages

- `index.html` - homepage (hero, services, guided contact intake)
- `projekti.html` - projects showcase with interactive mockups, mobile demo overlay, and lazy-loaded local iframes
- `about.html` - about page

Additional local demo pages are used as embedded project sources:

- `keef-demo.html`
- `keepgoing-demo.html`
- `juvy-demo.html`
- `reservation-schedule-demo.html`
- `reservation-calendar-demo.html`
- `dfa-demo.html`

## Project Structure

The project uses a **cPanel-compatible directory structure** with `public_html/` as the web root:

### Public Files (in `public_html/`)
- `*.html` - all HTML pages (index, projekti, about, privacy, demo pages)
- `style.css`, `script.js`, `shared-header.js` - frontend assets
- `assets/` - project media, imported demo assets, shared UI icons, and mobile preview images
- `api/` - contact intake endpoint and API docs
- `debug-archive/` - optional diagnostic files loaded only when explicit mobile-debug flags are enabled

### Private Files (outside `public_html/`)
- `.env` - environment configuration (sensitive, not committed)
- `composer.json`, `composer.lock` - PHP dependencies
- `vendor/` - Composer packages (not committed)
- `var/` - runtime data (rate limit + intake logs, not committed)
- `docs/` - documentation
- `scripts/` - local server and validation scripts

This structure keeps sensitive files (`.env`, `vendor/`, `var/`) outside the web-accessible directory for enhanced security.

For a detailed map, cleanup guidance, and maintenance workflow, see:

- `docs/PROJECT-STRUCTURE.md`
- `docs/MAINTENANCE.md`
- `docs/CODEBASE-AUDIT.md`
- `docs/STABLE-SNAPSHOT-2026-04-23.md`

## Local Development

The local development server serves content from the `public_html/` directory.

Start local server:

```bash
bash scripts/start-localhost3000.sh
```

The server will start at `http://localhost:3000` and serve from `public_html/`.

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

Create a deployable hosting package after dependencies are installed:

```bash
bash scripts/build-hosting-package.sh
```

## Contact Form Configuration

1. Copy `.env.example` to `.env` in the project root (outside `public_html/`)
2. Fill SMTP and security values
3. Keep `.env` out of git (already ignored)

The `.env` file is loaded by the PHP API from outside the web root for security.

See:

- `public_html/api/README.md`
- `docs/DEPLOYMENT-CHECKLIST.md`
- `docs/SECURITY-CHECKLIST.md`

## Deployment Notes

### cPanel Hosting

This site is structured for standard cPanel shared hosting:

- **Web root**: cPanel serves from `public_html/` directory
- **Sensitive files**: `.env`, `vendor/`, and `var/` remain outside the web root for security
- **Deployment**: Only `public_html/` contents are deployed to the server
- **Demo assets**: imported Juvy and Keep Going snapshots plus local demo pages are part of the deployed runtime

### Deployment Steps

1. Deploy `public_html/` contents to cPanel web root (automated via `.cpanel.yml`)
2. Upload `.env` file to project root on server (outside web root)
3. Run `composer install --no-dev --optimize-autoloader` on server via SSH
4. Create `var/` directory on server (outside web root) with write permissions
5. Ensure outbound SMTP (port `587`) is allowed
6. Configure DNS email records (`SPF`, `DKIM`, `DMARC`)

For detailed cPanel setup instructions, see `docs/CPANEL-SETUP.md`.

## Quick Quality Workflow

Before each release:

1. `bash scripts/check-site.sh`
2. `bash scripts/smoke-contact-api.sh`
3. Visual pass on all pages in all three languages (`HR`, `EN`, `DE`):
   - `http://localhost:3000/index.html`
   - `http://localhost:3000/projekti.html`
   - `http://localhost:3000/about.html`
4. On mobile widths, verify:
   - projects page inline demo loading
   - reservation demo overlay and rotation prompt
   - Keef QR menu behavior

## Important

- Do not commit real credentials to the repository
- `.env`, `vendor/`, and `var/` are kept outside `public_html/` for security
- Only `public_html/` contents are deployed to the web server
- Local build artifacts such as ad-hoc zip exports, editor settings, and test-run output should stay out of release commits unless explicitly needed
