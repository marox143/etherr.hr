# Implementation Plan: cPanel Hosting Migration

## Overview

This implementation plan restructures the Etherr marketing website to work with cPanel shared hosting by creating a `public_html/` web root directory and moving sensitive files outside the web-accessible area. The migration follows a 7-phase approach: preparation, file migration, path updates, development environment updates, deployment configuration, documentation updates, and testing.

## Tasks

- [x] 1. Preparation - Create backup and target structure
  - Create Git branch `feature/cpanel-migration`
  - Create Git tag `pre-cpanel-migration` to mark current state
  - Create `public_html/` directory at project root
  - Create subdirectories: `public_html/api/`, `public_html/assets/`, `public_html/demos/`
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 2. File Migration - Move public assets to public_html/
  - [x] 2.1 Move HTML files to public_html/
    - Move `index.html`, `projekti.html`, `about.html`, `privacy.html` to `public_html/`
    - Preserve file permissions during move
    - _Requirements: 2.1_
  
  - [x] 2.2 Move CSS and JavaScript files to public_html/
    - Move `style.css`, `script.js`, `shared-header.js` to `public_html/`
    - Preserve file permissions during move
    - _Requirements: 2.3, 2.4_
  
  - [x] 2.3 Move assets directory to public_html/
    - Move entire `assets/` directory to `public_html/assets/`
    - Preserve exact directory structure including all subdirectories (images/, almagea/, juvy/, dfa/, kota/, qr-digital-pricelist/, ripple/, clouds/)
    - Preserve file permissions during move
    - _Requirements: 2.5, 2.7, 19.1, 19.2, 19.3, 19.4, 19.5, 19.6_
  
  - [x] 2.4 Move api directory to public_html/
    - Move entire `api/` directory to `public_html/api/`
    - Preserve file permissions during move
    - _Requirements: 2.6_
  
  - [x] 2.5 Move demos directory to public_html/
    - Move entire `demos/` directory to `public_html/demos/`
    - Preserve exact directory structure
    - Preserve file permissions during move
    - _Requirements: 2.2_
  
  - [x] 2.6 Verify private files remain outside public_html/
    - Confirm `.env` is in project root (not in public_html/)
    - Confirm `vendor/` is in project root (not in public_html/)
    - Confirm `var/` is in project root (not in public_html/)
    - Confirm `docs/` is in project root (not in public_html/)
    - Confirm `scripts/` is in project root (not in public_html/)
    - Confirm `composer.json` and `composer.lock` are in project root
    - _Requirements: 1.3, 5.1, 5.2, 6.1_

- [x] 3. Checkpoint - Verify file migration completed
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Path Reference Updates - Update all file paths
  - [x] 4.1 Update PHP path resolution in contact-intake.php
    - Change `$rootDir = dirname(__DIR__);` to `$rootDir = dirname(__DIR__, 2);` to traverse from `public_html/api/` to project root
    - Update `.env` file path to use new `$rootDir`
    - Update `vendor/autoload.php` path to use new `$rootDir`
    - Update `var/` storage directory path to use new `$rootDir`
    - Verify all path resolutions point to correct locations
    - _Requirements: 3.6, 3.7, 3.8, 4.4, 5.3, 6.4_
  
  - [x] 4.2 Update HTML asset references (if needed)
    - Check all `<link href="...">` tags for CSS references
    - Check all `<script src="...">` tags for JavaScript references
    - Check all `<img src="...">` tags for image references
    - Check all `<iframe src="...">` tags for demo page references
    - Update paths if any referenced files outside public_html/ (should be relative within public_html/)
    - _Requirements: 3.1, 3.2, 3.3, 3.4_
  
  - [x] 4.3 Update JavaScript API endpoint references (if needed)
    - Check API endpoint URLs in `script.js` and other JS files
    - Verify API paths remain correct relative to public_html/
    - Update paths if needed to maintain `/api/contact-intake.php` endpoint
    - _Requirements: 3.5_
  
  - [x] 4.4 Update CSS asset references (if needed)
    - Check all `url(...)` references for background images
    - Check `@import` statements
    - Check font file references
    - Update paths if needed to maintain relative paths within public_html/
    - _Requirements: 3.1, 3.9_

- [x] 5. Development Environment - Update local server scripts
  - [x] 5.1 Update start-localhost3000.sh script
    - Add `-t "$ROOT_DIR/public_html"` parameter to PHP server command
    - Update script to serve content from public_html/ directory
    - Update success message to clarify serving from public_html/
    - _Requirements: 10.3, 10.4_
  
  - [x] 5.2 Update stop-localhost3000.sh script (if needed)
    - Verify script still works with updated start script
    - Update any path references if needed
    - _Requirements: 10.2_
  
  - [x] 5.3 Test local development server
    - Run `bash scripts/start-localhost3000.sh`
    - Verify server starts successfully
    - Verify server serves from public_html/
    - Access http://localhost:3000 and verify homepage loads
    - Stop server with `bash scripts/stop-localhost3000.sh`
    - _Requirements: 10.1, 10.5_

- [x] 6. Deployment Configuration - Update .cpanel.yml
  - [x] 6.1 Update .cpanel.yml deployment source
    - Change rsync source from `./` to `./public_html/`
    - Remove `.cpanel.yml` from exclusion list (file is outside public_html/)
    - Keep `.git` exclusion
    - Verify deployment will only copy public_html/ contents
    - _Requirements: 13.1, 13.2, 13.3_
  
  - [x] 6.2 Verify deployment configuration
    - Review updated .cpanel.yml syntax
    - Confirm sensitive files (.env, vendor/, var/) will not be deployed
    - Document that composer install must be run on server after deployment
    - _Requirements: 13.5_

- [x] 7. Documentation Updates - Update all markdown files
  - [x] 7.1 Update README.md
    - Add section explaining public_html/ structure
    - Update local development instructions to reference new scripts
    - Update deployment instructions for cPanel
    - Document relationship between project root and public_html/
    - _Requirements: 11.1, 11.8_
  
  - [x] 7.2 Update docs/PROJECT-STRUCTURE.md
    - Document new directory layout with public_html/ as web root
    - Explain public vs. private file separation
    - Update all file location references
    - Add directory structure diagram
    - _Requirements: 11.2, 11.8_
  
  - [x] 7.3 Update docs/DEPLOYMENT-CHECKLIST.md
    - Add cPanel-specific deployment steps
    - Document composer install requirement on server
    - Document .env configuration on server
    - Document var/ directory permissions setup
    - Document how to configure cPanel to serve from public_html/
    - _Requirements: 11.3, 11.9, 13.4, 13.5, 13.6_
  
  - [x] 7.4 Update docs/SECURITY-CHECKLIST.md
    - Highlight security benefits of new structure (sensitive files outside web root)
    - Update file location references
    - Document that .env, vendor/, var/ are protected
    - _Requirements: 11.5_
  
  - [x] 7.5 Update docs/MAINTENANCE.md
    - Update file placement rules (public files in public_html/, private files outside)
    - Document where new files should go
    - Update backup procedures to include both public_html/ and root files
    - _Requirements: 11.4_
  
  - [x] 7.6 Update api/README.md
    - Update API path references to reflect public_html/api/ location
    - Document new file locations for dependencies
    - Update configuration instructions for .env and vendor/ paths
    - _Requirements: 11.6_
  
  - [x] 7.7 Create docs/CPANEL-SETUP.md
    - Document cPanel configuration instructions
    - Document PHP version requirements (8.1+)
    - Document Composer installation via SSH
    - Document .env file setup on server
    - Document directory permissions for var/
    - Document SMTP configuration requirements
    - Add troubleshooting section for common issues
    - _Requirements: 11.7, 12.1, 12.2, 12.3, 12.4, 12.9, 12.10_

- [x] 8. Checkpoint - Review documentation updates
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Validation Scripts - Update testing scripts
  - [x] 9.1 Update scripts/check-site.sh
    - Update script to check for HTML files in public_html/ instead of root
    - Update script to verify asset references resolve correctly from public_html/
    - Add check to verify sensitive files (.env, vendor/, var/) are outside public_html/
    - Update PHP syntax validation to check public_html/api/ files
    - Update iframe src validation to check public_html/demos/ paths
    - _Requirements: 10.6, 20.1, 20.3, 20.4, 20.9_
  
  - [x] 9.2 Update scripts/smoke-contact-api.sh
    - Update script to test API at public_html/api/contact-intake.php
    - Add verification that .env loads from project root
    - Add verification that vendor/autoload.php loads from project root
    - Add verification that var/ storage is accessible
    - Test rate limiting functionality
    - Test validation logic
    - _Requirements: 10.6, 20.2, 20.5, 20.6_

- [ ]* 10. Testing and Validation - Run all tests
  - [ ]* 10.1 Run validation scripts
    - Execute `bash scripts/check-site.sh` and verify all checks pass
    - Execute `bash scripts/smoke-contact-api.sh` and verify API tests pass
    - Fix any issues reported by validation scripts
    - _Requirements: 10.7, 14.8, 20.7_
  
  - [ ]* 10.2 Test homepage functionality
    - Load http://localhost:3000 in browser
    - Verify hero section displays with Floating Grid animation
    - Verify all CSS loads without 404 errors
    - Verify all JavaScript loads without 404 errors
    - Verify all images load without 404 errors
    - Verify console shows no JavaScript errors
    - _Requirements: 14.1, 14.9, 14.10, 14.11_
  
  - [ ]* 10.3 Test projects page functionality
    - Load http://localhost:3000/projekti.html in browser
    - Verify all project showcase cards display with correct images
    - Verify project cards flip on hover or click
    - Verify demo pages load correctly in iframes
    - Verify all assets load without 404 errors
    - _Requirements: 14.2, 14.3, 14.7_
  
  - [ ]* 10.4 Test about and privacy pages
    - Load http://localhost:3000/about.html in browser
    - Load http://localhost:3000/privacy.html in browser
    - Verify pages load correctly
    - Verify all assets load without 404 errors
    - _Requirements: 14.1_
  
  - [ ]* 10.5 Test language switcher
    - Open homepage and switch between Croatian (HR), English (EN), and German (DE)
    - Verify all text content updates immediately
    - Verify language preference persists in localStorage
    - Test on multiple pages to ensure consistency
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 14.6_
  
  - [ ]* 10.6 Test contact form functionality
    - Open contact form on homepage
    - Verify Guided Intake Form wizard displays correctly
    - Test form validation with invalid data
    - Test form validation with valid data
    - Submit form and verify success message
    - Verify API processes submission (check logs if enabled)
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10, 14.4, 14.5_
  
  - [ ]* 10.7 Verify all translation keys
    - Check that all elements with `data-i18n` attributes have corresponding translations
    - Verify no missing translation keys in any language
    - _Requirements: 20.10_

- [ ] 11. Final checkpoint - Verify migration complete
  - Ensure all tests pass, ask the user if questions arise.
  - Confirm all files migrated successfully
  - Confirm all path references updated correctly
  - Confirm all validation scripts pass
  - Confirm all documentation updated
  - Ready for deployment to cPanel

## Notes

- Tasks marked with `*` are optional testing tasks and can be skipped for faster implementation
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation throughout the migration
- The migration is purely structural - no business logic changes
- All existing features must continue to work exactly as before
- Sensitive files (.env, vendor/, var/) remain outside public_html/ for security
- PHP path resolution uses `dirname(__DIR__, 2)` to reach project root from public_html/api/
- Local development server serves from public_html/ directory
- Deployment only copies public_html/ contents to cPanel web root
