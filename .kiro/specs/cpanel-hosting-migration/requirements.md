# Requirements Document

## Introduction

This document specifies the requirements for migrating the Etherr marketing website from its current structure to a cPanel-compatible shared hosting environment. The migration must preserve all existing functionality, design, and features while restructuring the codebase to work within the constraints of standard cPanel shared hosting (primarily used for WordPress sites). The goal is to eliminate the need for separate hosting while maintaining complete feature parity.

## Glossary

- **Site**: The Etherr marketing website system
- **Public_Html_Directory**: The web-accessible root directory (`public_html/`) required by cPanel hosting
- **Root_Directory**: The project root directory containing both public and non-public files
- **Static_Assets**: HTML, CSS, JavaScript, and media files served directly to browsers
- **Contact_API**: The PHP endpoint (`api/contact-intake.php`) that processes contact form submissions
- **Runtime_Storage**: The `var/` directory containing rate limit data and submission logs
- **Composer_Dependencies**: Third-party PHP libraries managed by Composer (PHPMailer)
- **Environment_Config**: The `.env` file containing sensitive configuration values
- **Demo_Pages**: HTML pages embedded as iframes in project showcases
- **Language_Switcher**: The multilingual content system supporting HR, EN, and DE
- **Guided_Intake_Form**: The multi-step contact form with service selection and validation
- **Turnstile_Integration**: Cloudflare Turnstile bot protection for the contact form
- **SMTP_Transport**: Email delivery mechanism using PHPMailer and SMTP authentication
- **Rate_Limiter**: IP-based request throttling mechanism for the Contact_API
- **Honeypot_Filter**: Bot detection mechanism using hidden form fields
- **Project_Showcase**: Interactive project cards with device mockups and flip animations
- **Floating_Grid**: Animated canvas-based hero background with network visualization
- **Shared_Header**: Common navigation component used across all pages
- **Local_Dev_Scripts**: Bash scripts for running PHP built-in server during development
- **Documentation_Files**: Markdown files in `docs/` describing structure, deployment, security, and maintenance
- **Validation_Scripts**: Bash scripts in `scripts/` for automated quality checks

## Requirements

### Requirement 1: Directory Structure Reorganization

**User Story:** As a developer, I want the site organized with a `public_html/` directory, so that it works with standard cPanel hosting conventions.

#### Acceptance Criteria

1. THE Site SHALL create a new Public_Html_Directory at the project root
2. THE Site SHALL place all web-accessible Static_Assets inside the Public_Html_Directory
3. THE Site SHALL place all non-public files (Environment_Config, Composer_Dependencies, Runtime_Storage, Documentation_Files, Validation_Scripts) outside the Public_Html_Directory
4. THE Site SHALL maintain the current project root as the Root_Directory containing both public and non-public resources
5. WHERE Composer_Dependencies are installed, THE Site SHALL place the `vendor/` directory outside the Public_Html_Directory
6. WHERE Runtime_Storage exists, THE Site SHALL place the `var/` directory outside the Public_Html_Directory
7. THE Site SHALL update all file path references to reflect the new directory structure

### Requirement 2: Static Asset Migration

**User Story:** As a developer, I want all HTML, CSS, JavaScript, and media files moved to `public_html/`, so that they are accessible via the web server.

#### Acceptance Criteria

1. THE Site SHALL move all root-level HTML files (index.html, projekti.html, about.html, privacy.html) into the Public_Html_Directory
2. THE Site SHALL move all Demo_Pages into the Public_Html_Directory
3. THE Site SHALL move all CSS files (style.css) into the Public_Html_Directory
4. THE Site SHALL move all JavaScript files (script.js, shared-header.js) into the Public_Html_Directory
5. THE Site SHALL move the `assets/` directory into the Public_Html_Directory
6. THE Site SHALL move the `api/` directory into the Public_Html_Directory
7. THE Site SHALL preserve the exact directory structure of the `assets/` folder during migration
8. THE Site SHALL preserve all file permissions during migration

### Requirement 3: Path Reference Updates

**User Story:** As a developer, I want all file references updated automatically, so that the site continues to work after restructuring.

#### Acceptance Criteria

1. WHEN HTML files reference CSS files, THE Site SHALL update the paths to reflect the new structure
2. WHEN HTML files reference JavaScript files, THE Site SHALL update the paths to reflect the new structure
3. WHEN HTML files reference image assets, THE Site SHALL update the paths to reflect the new structure
4. WHEN HTML files embed Demo_Pages via iframes, THE Site SHALL update the iframe src paths
5. WHEN JavaScript files reference API endpoints, THE Site SHALL update the paths to reflect the new structure
6. WHEN the Contact_API references Composer_Dependencies, THE Site SHALL update the require paths to point outside Public_Html_Directory
7. WHEN the Contact_API references Environment_Config, THE Site SHALL update the path to point outside Public_Html_Directory
8. WHEN the Contact_API references Runtime_Storage, THE Site SHALL update the path to point outside Public_Html_Directory
9. THE Site SHALL use relative paths where possible to maintain portability

### Requirement 4: PHP Dependency Management

**User Story:** As a developer, I want Composer dependencies accessible to PHP scripts, so that PHPMailer continues to work.

#### Acceptance Criteria

1. THE Site SHALL keep `composer.json` in the Root_Directory
2. THE Site SHALL keep `composer.lock` in the Root_Directory
3. THE Site SHALL install Composer_Dependencies outside the Public_Html_Directory
4. WHEN the Contact_API loads PHPMailer, THE Site SHALL resolve the autoloader path relative to the Root_Directory
5. THE Site SHALL document the Composer installation command in deployment documentation
6. THE Site SHALL verify PHPMailer can be loaded successfully after path updates

### Requirement 5: Environment Configuration Security

**User Story:** As a developer, I want sensitive configuration files outside the web root, so that they cannot be accessed via HTTP requests.

#### Acceptance Criteria

1. THE Site SHALL place `.env` outside the Public_Html_Directory
2. THE Site SHALL place `.env.example` outside the Public_Html_Directory
3. THE Site SHALL update the Contact_API to load Environment_Config from outside the Public_Html_Directory
4. THE Site SHALL document the Environment_Config location in deployment documentation
5. THE Site SHALL verify the Contact_API can load environment variables after path updates
6. THE Site SHALL maintain the existing `.gitignore` rules for `.env`

### Requirement 6: Runtime Storage Security

**User Story:** As a developer, I want runtime data stored outside the web root, so that logs and rate limit data cannot be accessed publicly.

#### Acceptance Criteria

1. THE Site SHALL place the `var/` directory outside the Public_Html_Directory
2. WHEN the Contact_API writes rate limit data, THE Site SHALL write to Runtime_Storage outside the Public_Html_Directory
3. WHEN the Contact_API writes submission logs, THE Site SHALL write to Runtime_Storage outside the Public_Html_Directory
4. THE Site SHALL update the Contact_API to resolve Runtime_Storage paths relative to the Root_Directory
5. THE Site SHALL verify the Contact_API can read and write to Runtime_Storage after path updates
6. THE Site SHALL document the Runtime_Storage location and permissions in deployment documentation

### Requirement 7: Contact Form API Preservation

**User Story:** As a user, I want the contact form to work exactly as before, so that I can submit inquiries without noticing any changes.

#### Acceptance Criteria

1. THE Contact_API SHALL remain accessible at `/api/contact-intake.php` relative to the Public_Html_Directory
2. WHEN a user submits the Guided_Intake_Form, THE Contact_API SHALL validate all required fields
3. WHEN a user submits the Guided_Intake_Form, THE Contact_API SHALL enforce the Honeypot_Filter
4. WHEN a user submits the Guided_Intake_Form, THE Contact_API SHALL enforce the Rate_Limiter
5. WHERE Turnstile_Integration is enabled, THE Contact_API SHALL validate the Turnstile token
6. WHEN validation passes, THE Contact_API SHALL send email via SMTP_Transport
7. WHEN email sending succeeds, THE Contact_API SHALL return status `sent`
8. WHEN email sending fails, THE Contact_API SHALL return status `queued`
9. WHERE submission logging is enabled, THE Contact_API SHALL write to Runtime_Storage in NDJSON format
10. THE Contact_API SHALL return the same JSON response structure as before migration

### Requirement 8: Multilingual Content Preservation

**User Story:** As a user, I want to switch between Croatian, English, and German, so that I can read content in my preferred language.

#### Acceptance Criteria

1. THE Language_Switcher SHALL support Croatian (HR), English (EN), and German (DE)
2. WHEN a user selects a language, THE Site SHALL store the preference in localStorage
3. WHEN a user returns to the site, THE Site SHALL load the previously selected language
4. WHEN localStorage is unavailable, THE Site SHALL fall back to cookie-based language storage
5. THE Site SHALL apply language-specific content to all text elements with `data-i18n` attributes
6. THE Site SHALL update the HTML `lang` attribute when language changes
7. THE Site SHALL preserve all existing translation keys and values

### Requirement 9: Interactive Features Preservation

**User Story:** As a user, I want all interactive features to work exactly as before, so that my experience is unchanged.

#### Acceptance Criteria

1. THE Floating_Grid SHALL render the animated canvas background on the homepage
2. THE Project_Showcase SHALL display interactive device mockups (laptop and phone)
3. THE Project_Showcase SHALL support card flip animations on hover or click
4. THE Guided_Intake_Form SHALL display the multi-step wizard interface
5. THE Guided_Intake_Form SHALL track progress through form steps
6. THE Guided_Intake_Form SHALL validate input at each step
7. THE Shared_Header SHALL render consistently across all pages
8. THE Site SHALL embed Demo_Pages in iframes within project showcases
9. THE Site SHALL preserve all CSS animations and transitions
10. THE Site SHALL preserve all JavaScript event handlers

### Requirement 10: Local Development Environment

**User Story:** As a developer, I want to run the site locally for testing, so that I can verify changes before deployment.

#### Acceptance Criteria

1. THE Site SHALL provide Local_Dev_Scripts for starting a PHP built-in server
2. THE Site SHALL provide Local_Dev_Scripts for stopping the PHP built-in server
3. WHEN Local_Dev_Scripts start the server, THE Site SHALL serve content from the Public_Html_Directory
4. WHEN Local_Dev_Scripts start the server, THE Site SHALL make the Contact_API accessible
5. THE Site SHALL document the local development workflow in README.md
6. THE Site SHALL update Validation_Scripts to work with the new directory structure
7. THE Site SHALL verify all Validation_Scripts pass after migration

### Requirement 11: Documentation Updates

**User Story:** As a developer, I want updated documentation, so that I understand the new structure and deployment process.

#### Acceptance Criteria

1. THE Site SHALL update `README.md` to reflect the new directory structure
2. THE Site SHALL update `docs/PROJECT-STRUCTURE.md` to document the Public_Html_Directory organization
3. THE Site SHALL update `docs/DEPLOYMENT-CHECKLIST.md` to include cPanel-specific deployment steps
4. THE Site SHALL update `docs/MAINTENANCE.md` to reflect new file placement rules
5. THE Site SHALL update `docs/SECURITY-CHECKLIST.md` to document security benefits of the new structure
6. THE Site SHALL update `api/README.md` to document updated API paths
7. WHERE new deployment considerations exist, THE Site SHALL create additional documentation
8. THE Site SHALL document the relationship between Root_Directory and Public_Html_Directory
9. THE Site SHALL document how to configure cPanel to serve from Public_Html_Directory

### Requirement 12: cPanel Compatibility

**User Story:** As a system administrator, I want the site to work on standard cPanel shared hosting, so that I can host it alongside WordPress sites.

#### Acceptance Criteria

1. THE Site SHALL require only PHP 8.1+ support from the hosting environment
2. THE Site SHALL work with standard cPanel directory conventions (public_html as web root)
3. THE Site SHALL support Composer dependency installation via SSH or cPanel terminal
4. THE Site SHALL work with SMTP outbound connections on port 587
5. THE Site SHALL support `.htaccess` files for URL rewriting and access control
6. THE Site SHALL not require custom Apache modules beyond standard cPanel installations
7. THE Site SHALL not require Node.js or other runtime environments
8. THE Site SHALL not require custom build steps for deployment
9. THE Site SHALL document minimum PHP version requirements
10. THE Site SHALL document required PHP extensions (if any beyond standard cPanel PHP)

### Requirement 13: Deployment Automation

**User Story:** As a developer, I want automated deployment to cPanel, so that I can publish changes efficiently.

#### Acceptance Criteria

1. WHERE `.cpanel.yml` exists, THE Site SHALL update it to deploy from the Public_Html_Directory
2. THE Site SHALL configure `.cpanel.yml` to exclude non-public files from deployment
3. THE Site SHALL configure `.cpanel.yml` to exclude `.git` directory from deployment
4. THE Site SHALL document manual deployment steps as an alternative to `.cpanel.yml`
5. THE Site SHALL document how to run `composer install` on the server after deployment
6. THE Site SHALL document how to create and configure the `.env` file on the server

### Requirement 14: Backward Compatibility Verification

**User Story:** As a quality assurance tester, I want to verify all features work after migration, so that I can confirm nothing broke.

#### Acceptance Criteria

1. WHEN the homepage loads, THE Site SHALL display the hero section with Floating_Grid animation
2. WHEN the projects page loads, THE Site SHALL display all Project_Showcase cards with correct images
3. WHEN a user clicks a project card, THE Site SHALL flip the card to show additional details
4. WHEN a user opens the contact form, THE Site SHALL display the Guided_Intake_Form wizard
5. WHEN a user submits the contact form with valid data, THE Contact_API SHALL send an email
6. WHEN a user switches languages, THE Site SHALL update all text content immediately
7. WHEN a user views Demo_Pages, THE Site SHALL load iframe content correctly
8. WHEN Validation_Scripts run, THE Site SHALL pass all checks
9. THE Site SHALL load all CSS without 404 errors
10. THE Site SHALL load all JavaScript without 404 errors
11. THE Site SHALL load all images without 404 errors
12. THE Site SHALL load all fonts without 404 errors

### Requirement 15: Git Repository Management

**User Story:** As a developer, I want the Git repository updated appropriately, so that version control reflects the new structure.

#### Acceptance Criteria

1. THE Site SHALL update `.gitignore` to reflect the new directory structure
2. THE Site SHALL continue to exclude `.env` from version control
3. THE Site SHALL continue to exclude `vendor/` from version control
4. THE Site SHALL continue to exclude `var/` from version control
5. THE Site SHALL continue to exclude runtime artifacts (`.pid`, `.log` files) from version control
6. THE Site SHALL track all files in the Public_Html_Directory that are part of the site
7. THE Site SHALL track all Documentation_Files
8. THE Site SHALL track all Validation_Scripts
9. THE Site SHALL track `composer.json` and `composer.lock`

### Requirement 16: Performance Preservation

**User Story:** As a user, I want the site to load as fast as before, so that my experience is not degraded.

#### Acceptance Criteria

1. THE Site SHALL serve Static_Assets with the same caching headers as before
2. THE Site SHALL maintain the same asset file sizes as before migration
3. THE Site SHALL not introduce additional HTTP redirects
4. THE Site SHALL not introduce additional network requests
5. THE Site SHALL preserve lazy loading behavior for images where implemented
6. THE Site SHALL preserve font loading strategy
7. THE Site SHALL maintain the same JavaScript execution order

### Requirement 17: Security Hardening Preservation

**User Story:** As a security engineer, I want all existing security measures to remain active, so that the site stays protected.

#### Acceptance Criteria

1. THE Contact_API SHALL continue to enforce CORS via `ALLOWED_ORIGINS` configuration
2. THE Contact_API SHALL continue to enforce IP-based rate limiting
3. THE Contact_API SHALL continue to validate the Honeypot_Filter
4. THE Contact_API SHALL continue to require server-side consent validation
5. THE Contact_API SHALL continue to sanitize all input fields
6. THE Contact_API SHALL continue to use parameterized SMTP authentication
7. THE Site SHALL serve security headers (X-Content-Type-Options, Cache-Control, Referrer-Policy)
8. THE Site SHALL keep Environment_Config outside the Public_Html_Directory
9. THE Site SHALL keep Runtime_Storage outside the Public_Html_Directory
10. WHERE `.htaccess` is used, THE Site SHALL deny direct access to sensitive file types

### Requirement 18: Error Handling Preservation

**User Story:** As a user, I want helpful error messages when something goes wrong, so that I understand what happened.

#### Acceptance Criteria

1. WHEN the Contact_API receives invalid input, THE Site SHALL return a descriptive error code
2. WHEN the Contact_API rate limit is exceeded, THE Site SHALL return HTTP 429 with retry-after header
3. WHEN the Contact_API Turnstile validation fails, THE Site SHALL return a specific error code
4. WHEN the Contact_API SMTP sending fails, THE Site SHALL return status `queued` instead of failing
5. WHEN a required environment variable is missing, THE Contact_API SHALL return an error response
6. WHEN Runtime_Storage is not writable, THE Contact_API SHALL log the error and continue
7. THE Site SHALL maintain the same error response JSON structure as before migration

### Requirement 19: Asset Organization Preservation

**User Story:** As a developer, I want the assets folder structure unchanged, so that project-specific media remains organized.

#### Acceptance Criteria

1. THE Site SHALL preserve the `assets/images/` directory for global UI assets
2. THE Site SHALL preserve project-specific asset directories (assets/almagea/, assets/juvy/, assets/dfa/, assets/kota/, etc.)
3. THE Site SHALL preserve the `assets/qr-digital-pricelist/` WordPress plugin reference
4. THE Site SHALL preserve the `assets/ripple/` project documentation
5. THE Site SHALL preserve all image file paths within the assets directory
6. THE Site SHALL preserve all video file paths within the assets directory
7. THE Site SHALL maintain the same asset URL structure for external references

### Requirement 20: Testing and Validation

**User Story:** As a developer, I want automated tests to verify the migration, so that I can catch issues early.

#### Acceptance Criteria

1. THE Site SHALL update `scripts/check-site.sh` to validate the new directory structure
2. THE Site SHALL update `scripts/smoke-contact-api.sh` to test the Contact_API from the new location
3. WHEN `scripts/check-site.sh` runs, THE Site SHALL verify all HTML files exist in Public_Html_Directory
4. WHEN `scripts/check-site.sh` runs, THE Site SHALL verify all asset references resolve correctly
5. WHEN `scripts/smoke-contact-api.sh` runs, THE Site SHALL verify the Contact_API responds correctly
6. WHEN `scripts/smoke-contact-api.sh` runs, THE Site SHALL verify SMTP_Transport can be initialized
7. THE Site SHALL document the testing workflow in updated documentation
8. THE Site SHALL verify PHP syntax for all PHP files after migration
9. THE Site SHALL verify all iframe src attributes point to valid Demo_Pages
10. THE Site SHALL verify all `data-i18n` keys have corresponding translations
