# cPanel Setup Guide

This guide walks through setting up the Etherr marketing website on cPanel shared hosting.

## Prerequisites

- cPanel hosting account with SSH access
- Domain configured and pointing to your hosting
- PHP 8.1+ support
- Outbound SMTP on port 587 enabled

## Overview

The project uses a security-enhanced structure:

- **`public_html/`** - Web-accessible files (HTML, CSS, JS, API, assets)
- **Project root** - Sensitive files outside web root (`.env`, `vendor/`, `var/`)

This structure prevents credentials, logs, and dependencies from being accessed via HTTP.

## Step 1: Upload Project Files

### 1.1 Connect via FTP/SFTP or File Manager

Upload all project files to your cPanel account root directory (e.g., `/home/username/`):

```
/home/username/
├── .env.example           # Upload this, will configure in Step 3
├── composer.json          # Dependency definitions
├── composer.lock          # Dependency lock file
├── public_html/           # Web root directory
│   ├── index.html
│   ├── projekti.html
│   ├── about.html
│   ├── privacy.html
│   ├── style.css
│   ├── script.js
│   ├── shared-header.js
│   ├── api/
│   │   └── contact-intake.php
│   ├── assets/
│   └── demos/
├── docs/                  # Documentation (optional)
└── scripts/               # Development scripts (optional)
```

**Important**: Upload the entire `public_html/` directory, not just its contents.

### 1.2 Verify Upload

Check that:
- `public_html/` directory exists in `/home/username/`
- `public_html/index.html` exists
- `public_html/api/contact-intake.php` exists
- `.env.example` exists in `/home/username/`

## Step 2: Configure cPanel

### 2.1 Set Document Root

1. Log into cPanel
2. Navigate to **"Domains"** or **"Addon Domains"**
3. Find your domain and click **"Manage"** or **"Document Root"**
4. Set document root to: `/home/username/public_html/`
5. Save changes

**Verification**: Visit your domain in a browser. You should see the homepage.

### 2.2 Select PHP Version

1. In cPanel, navigate to **"Select PHP Version"** or **"MultiPHP Manager"**
2. Select **PHP 8.1** or higher (8.2, 8.3, 8.5 all work)
3. Apply to your domain
4. Save changes

**Verification**: Create a test file `public_html/phpinfo.php`:

```php
<?php phpinfo();
```

Visit `https://yourdomain.com/phpinfo.php` and verify PHP version is 8.1+. Delete the file after verification.

## Step 3: Install Composer Dependencies

### 3.1 Connect via SSH

```bash
ssh username@yourdomain.com
```

Or use cPanel's **"Terminal"** feature.

### 3.2 Navigate to Project Root

```bash
cd /home/username/
pwd  # Should show: /home/username
```

### 3.3 Check Composer Availability

```bash
composer --version
```

If Composer is not installed, install it:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
alias composer='php /home/username/composer.phar'
```

### 3.4 Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

This creates `vendor/` directory with PHPMailer and other dependencies.

**Verification**:

```bash
ls -la vendor/
ls -la vendor/phpmailer/
```

You should see PHPMailer installed.

## Step 4: Configure Environment Variables

### 4.1 Create .env File

```bash
cd /home/username/
cp .env.example .env
nano .env  # Or use cPanel File Manager editor
```

### 4.2 Fill Required Values

```env
# Mail Transport
MAIL_TRANSPORT=smtp
MAIL_TO=your-email@example.com
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME=Etherr Contact Form

# SMTP Configuration (Microsoft 365 example)
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_AUTH=true
SMTP_USERNAME=your-email@yourdomain.com
SMTP_PASSWORD=your-app-password

# Security
ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
TURNSTILE_SECRET_KEY=your-turnstile-secret-key
TURNSTILE_ENFORCED=true

# Rate Limiting
RATE_LIMIT_WINDOW_SEC=3600
RATE_LIMIT_MAX_REQUESTS=5

# Storage
INTAKE_STORAGE_DIR=var
STORE_SUBMISSIONS=true
```

### 4.3 Set Secure Permissions

```bash
chmod 600 .env
```

This makes `.env` readable only by your user account.

**Verification**:

```bash
ls -la .env
# Should show: -rw------- (600 permissions)
```

## Step 5: Configure Runtime Storage

### 5.1 Create var/ Directory

```bash
cd /home/username/
mkdir -p var
chmod 755 var
```

### 5.2 Verify Permissions

```bash
ls -ld var/
# Should show: drwxr-xr-x (755 permissions)
```

The API will automatically create:
- `var/contact-rate-limit.json` - Rate limiting state
- `var/contact-intake-log.ndjson` - Submission logs (if enabled)

## Step 6: Configure Cloudflare Turnstile

### 6.1 Get Turnstile Keys

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Navigate to **Turnstile**
3. Create a new site
4. Copy **Site Key** and **Secret Key**

### 6.2 Add Secret Key to .env

Already done in Step 4.2:

```env
TURNSTILE_SECRET_KEY=your-secret-key
TURNSTILE_ENFORCED=true
```

### 6.3 Add Site Key to Frontend

Edit `public_html/index.html` and find:

```javascript
window.ETHERR_CONTACT_CONFIG = {
  turnstileSiteKey: 'your-site-key-here',  // Replace with your Site Key
  requireTurnstile: true,
  // ...
};
```

Replace `'your-site-key-here'` with your actual Turnstile Site Key.

## Step 7: Configure SMTP

### 7.1 Microsoft 365 / Outlook.com

```env
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_AUTH=true
SMTP_USERNAME=your-email@yourdomain.com
SMTP_PASSWORD=your-app-password
```

**Important**: Use an app-specific password, not your main account password.

### 7.2 Gmail

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_AUTH=true
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
```

**Important**: Enable 2FA and create an [App Password](https://myaccount.google.com/apppasswords).

### 7.3 Other SMTP Providers

Consult your email provider's documentation for SMTP settings.

## Step 8: Verify Installation

### 8.1 Check Homepage

Visit `https://yourdomain.com/` and verify:
- Homepage loads correctly
- No 404 errors in browser console
- All images and assets load
- Language switcher works (HR, EN, DE)

### 8.2 Check Projects Page

Visit `https://yourdomain.com/projekti.html` and verify:
- Project cards display correctly
- Demo pages load in iframes
- All assets load without errors

### 8.3 Test Contact Form

1. Open homepage and click contact button
2. Fill out the guided intake form
3. Submit the form
4. Verify success message appears
5. Check your email for the submission

### 8.4 Check Logs

```bash
cd /home/username/
cat var/contact-intake-log.ndjson
```

You should see a JSON entry for your test submission.

## Step 9: DNS and Email Deliverability

### 9.1 SPF Record

Add SPF record to your domain's DNS:

```
TXT @ "v=spf1 include:spf.protection.outlook.com -all"
```

(Adjust for your email provider)

### 9.2 DKIM

Enable DKIM in your email provider's settings and add the provided DNS records.

### 9.3 DMARC

Add DMARC record:

```
TXT _dmarc "v=DMARC1; p=quarantine; rua=mailto:dmarc@yourdomain.com"
```

### 9.4 Verify

Use tools like [MXToolbox](https://mxtoolbox.com/) to verify SPF, DKIM, and DMARC.

## Troubleshooting

### Issue: Homepage shows 404 or directory listing

**Cause**: Document root not set correctly.

**Solution**:
1. Verify document root is `/home/username/public_html/`
2. Verify `public_html/index.html` exists
3. Check file permissions: `chmod 644 public_html/index.html`

### Issue: Contact form returns "Autoloader not found"

**Cause**: Composer dependencies not installed.

**Solution**:
```bash
cd /home/username/
composer install --no-dev --optimize-autoloader
ls -la vendor/phpmailer/  # Verify PHPMailer exists
```

### Issue: Contact form returns "Environment file not found"

**Cause**: `.env` file missing or in wrong location.

**Solution**:
```bash
cd /home/username/
ls -la .env  # Should exist in project root
# If missing:
cp .env.example .env
nano .env  # Fill in values
chmod 600 .env
```

### Issue: Contact form returns "Failed to send email"

**Cause**: SMTP configuration incorrect or credentials invalid.

**Solution**:
1. Verify SMTP settings in `.env`
2. Test SMTP credentials with your email client
3. Check if outbound port 587 is open
4. Verify app-specific password is used (not main password)
5. Check `var/contact-intake-log.ndjson` for detailed error messages

### Issue: Rate limiting not working

**Cause**: `var/` directory not writable.

**Solution**:
```bash
cd /home/username/
chmod 755 var/
ls -ld var/  # Verify permissions
```

### Issue: Submissions not logged

**Cause**: `STORE_SUBMISSIONS` disabled or `var/` not writable.

**Solution**:
1. Check `.env`: `STORE_SUBMISSIONS=true`
2. Verify `var/` permissions: `chmod 755 var/`
3. Check if `var/contact-intake-log.ndjson` is created after submission

### Issue: Assets (images, CSS, JS) not loading

**Cause**: Files not uploaded or incorrect paths.

**Solution**:
1. Verify files exist in `public_html/assets/`
2. Check browser console for 404 errors
3. Verify file permissions: `chmod 644 public_html/style.css`
4. Check that paths in HTML are relative (e.g., `href="style.css"`, not `href="/style.css"`)

### Issue: PHP version too old

**Cause**: cPanel using PHP 7.x or older.

**Solution**:
1. In cPanel, go to **"Select PHP Version"** or **"MultiPHP Manager"**
2. Select PHP 8.1 or higher
3. Apply to your domain
4. Verify with `php -v` in SSH or create `phpinfo.php`

### Issue: Permission denied errors

**Cause**: Incorrect file permissions.

**Solution**:
```bash
cd /home/username/
chmod 600 .env
chmod 755 var/
chmod 755 public_html/
chmod 644 public_html/*.html
chmod 644 public_html/*.css
chmod 644 public_html/*.js
chmod 644 public_html/api/*.php
```

## Security Checklist

After setup, verify:

- [ ] `.env` is in project root (outside `public_html/`)
- [ ] `.env` has 600 permissions (not web-accessible)
- [ ] `vendor/` is in project root (outside `public_html/`)
- [ ] `var/` is in project root (outside `public_html/`)
- [ ] `ALLOWED_ORIGINS` in `.env` matches your domain(s)
- [ ] Turnstile is enabled and configured
- [ ] SMTP uses app-specific password (not main password)
- [ ] SPF, DKIM, DMARC records configured
- [ ] Test contact form submission works
- [ ] Check `var/contact-intake-log.ndjson` for test entry

## Maintenance

### Update Dependencies

```bash
cd /home/username/
composer update --no-dev --optimize-autoloader
```

### View Logs

```bash
cd /home/username/
tail -f var/contact-intake-log.ndjson
```

### Clear Rate Limits

```bash
cd /home/username/
rm var/contact-rate-limit.json
```

### Backup

```bash
cd /home/username/
tar -czf backup-$(date +%Y%m%d).tar.gz \
  public_html/ \
  .env \
  var/ \
  composer.json \
  composer.lock
```

Download the backup file via FTP/SFTP.

## Support

For issues not covered here:

1. Check `var/contact-intake-log.ndjson` for detailed error messages
2. Check cPanel error logs
3. Review [docs/DEPLOYMENT-CHECKLIST.md](DEPLOYMENT-CHECKLIST.md)
4. Review [docs/SECURITY-CHECKLIST.md](SECURITY-CHECKLIST.md)
5. Review [public_html/api/README.md](../public_html/api/README.md)

## File Structure Reference

```
/home/username/                    # Project root (cPanel account root)
├── .env                           # Environment config (PROTECTED - outside web root)
├── .env.example                   # Environment template
├── composer.json                  # Dependency definitions (PROTECTED)
├── composer.lock                  # Dependency lock file (PROTECTED)
├── vendor/                        # Composer packages (PROTECTED - outside web root)
│   └── phpmailer/
├── var/                           # Runtime storage (PROTECTED - outside web root)
│   ├── contact-rate-limit.json
│   └── contact-intake-log.ndjson
├── docs/                          # Documentation (PROTECTED - outside web root)
└── public_html/                   # WEB ROOT - publicly accessible
    ├── index.html                 # Homepage
    ├── projekti.html              # Projects page
    ├── about.html                 # About page
    ├── privacy.html               # Privacy policy
    ├── style.css                  # Global styles
    ├── script.js                  # Global JavaScript
    ├── shared-header.js           # Shared navigation
    ├── api/
    │   └── contact-intake.php     # Contact form endpoint
    ├── assets/                    # Media and project assets
    │   ├── images/
    │   ├── almagea/
    │   ├── juvy/
    │   ├── dfa/
    │   ├── kota/
    │   └── qr-digital-pricelist/
    └── demos/                     # Demo pages for iframes
        ├── almagea/
        ├── juvy/
        └── ...
```

**Legend**:
- **PROTECTED** - Files outside `public_html/`, not web-accessible
- **PUBLIC** - Files inside `public_html/`, web-accessible
