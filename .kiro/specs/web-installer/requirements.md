# Requirements Document

## Introduction

This document specifies the requirements for a web-based installer for the Etherr marketing website that enables deployment to cPanel hosting environments without SSH access. The installer automates the setup process including system requirements checking, Composer dependency installation, environment configuration, directory creation, and installation validation. The goal is to provide a browser-accessible installation wizard that transforms a freshly extracted site archive into a fully functional website through a guided web interface.

## Glossary

- **Installer**: The web-based installation script (`install.php`) that automates site setup
- **System_Requirements_Checker**: Component that validates PHP version, extensions, and server capabilities
- **Composer_Installer**: Component that downloads and executes Composer to install dependencies
- **Environment_Configurator**: Component that generates the `.env` file from user-provided settings
- **Directory_Manager**: Component that creates and sets permissions for required directories
- **Installation_Validator**: Component that verifies the installation completed successfully
- **Security_Lock**: Mechanism that prevents the Installer from running after successful installation
- **Installation_Archive**: ZIP file containing the site files to be extracted on the server
- **Web_Root**: The `public_html/` directory containing publicly accessible files
- **Project_Root**: The parent directory of Web_Root containing configuration and dependencies
- **PHPMailer**: Third-party library for SMTP email sending, installed via Composer
- **Turnstile**: Cloudflare bot protection service requiring API keys
- **SMTP_Credentials**: Email server authentication details (host, port, username, password)
- **Rate_Limiting_Config**: Settings controlling request throttling (window, max requests)
- **Runtime_Directory**: The `var/` directory for logs and rate limit data
- **Installation_Session**: Browser session tracking installation progress through wizard steps
- **Composer_Phar**: Standalone Composer executable downloaded during installation
- **Autoloader**: Composer-generated file that enables automatic class loading
- **Installation_Log**: Record of installation steps and any errors encountered
- **Lock_File**: File created after successful installation to prevent re-running
- **Wizard_Step**: Individual page in the multi-step installation interface
- **Validation_Test**: Automated check verifying a specific installation requirement

## Requirements

### Requirement 1: Browser-Based Installation Access

**User Story:** As a site administrator, I want to access the installer through my web browser, so that I can set up the site without SSH access.

#### Acceptance Criteria

1. THE Installer SHALL be accessible at `https://domain.com/install.php` in the Web_Root
2. WHEN the Installer is accessed, THE Installer SHALL display a welcome screen with installation instructions
3. THE Installer SHALL use HTML forms for all user input
4. THE Installer SHALL use HTTP POST for form submissions
5. THE Installer SHALL maintain Installation_Session state across Wizard_Steps
6. THE Installer SHALL work with standard cPanel PHP configurations
7. THE Installer SHALL not require command-line access
8. THE Installer SHALL not require shell_exec or similar functions

### Requirement 2: System Requirements Validation

**User Story:** As a site administrator, I want the installer to check system requirements, so that I know if my hosting environment is compatible.

#### Acceptance Criteria

1. THE System_Requirements_Checker SHALL verify PHP version is 8.1 or higher
2. THE System_Requirements_Checker SHALL verify the `curl` extension is available
3. THE System_Requirements_Checker SHALL verify the `json` extension is available
4. THE System_Requirements_Checker SHALL verify the `mbstring` extension is available
5. THE System_Requirements_Checker SHALL verify the `openssl` extension is available
6. THE System_Requirements_Checker SHALL verify the Project_Root is writable
7. THE System_Requirements_Checker SHALL verify the Web_Root is writable
8. WHEN a requirement fails, THE System_Requirements_Checker SHALL display a specific error message
9. WHEN a requirement fails, THE System_Requirements_Checker SHALL prevent proceeding to the next step
10. WHEN all requirements pass, THE System_Requirements_Checker SHALL display a success message

### Requirement 3: Composer Dependency Installation

**User Story:** As a site administrator, I want the installer to install Composer dependencies automatically, so that I don't need to run composer install manually.

#### Acceptance Criteria

1. WHEN Composer_Phar does not exist, THE Composer_Installer SHALL download it from getcomposer.org
2. THE Composer_Installer SHALL verify the downloaded Composer_Phar is executable
3. THE Composer_Installer SHALL execute `composer install --no-dev --optimize-autoloader` in the Project_Root
4. THE Composer_Installer SHALL display real-time progress during installation
5. WHEN Composer installation succeeds, THE Composer_Installer SHALL verify the Autoloader exists
6. WHEN Composer installation succeeds, THE Composer_Installer SHALL verify PHPMailer is installed
7. WHEN Composer installation fails, THE Composer_Installer SHALL display the error output
8. WHEN Composer installation fails, THE Composer_Installer SHALL allow retrying the installation
9. THE Composer_Installer SHALL set appropriate file permissions on the vendor directory
10. THE Composer_Installer SHALL clean up temporary files after installation

### Requirement 4: Environment Configuration Interface

**User Story:** As a site administrator, I want to configure environment settings through a web form, so that I can set up SMTP, Turnstile, and other options without editing files manually.

#### Acceptance Criteria

1. THE Environment_Configurator SHALL display a form for SMTP_Credentials input
2. THE Environment_Configurator SHALL display a form for Turnstile API keys input
3. THE Environment_Configurator SHALL display a form for Rate_Limiting_Config input
4. THE Environment_Configurator SHALL display a form for recipient email address input
5. THE Environment_Configurator SHALL display a form for allowed origins input
6. THE Environment_Configurator SHALL provide default values for all optional settings
7. THE Environment_Configurator SHALL validate email addresses using PHP filter_var
8. THE Environment_Configurator SHALL validate numeric values for ports and rate limits
9. THE Environment_Configurator SHALL allow testing SMTP connection before saving
10. WHEN SMTP test succeeds, THE Environment_Configurator SHALL display a success message
11. WHEN SMTP test fails, THE Environment_Configurator SHALL display the error details
12. THE Environment_Configurator SHALL generate a `.env` file in the Project_Root
13. THE Environment_Configurator SHALL set file permissions 0600 on the `.env` file
14. THE Environment_Configurator SHALL escape special characters in configuration values

### Requirement 5: Directory Structure Creation

**User Story:** As a site administrator, I want the installer to create required directories, so that the site has proper storage for logs and rate limiting data.

#### Acceptance Criteria

1. THE Directory_Manager SHALL create the Runtime_Directory if it does not exist
2. THE Directory_Manager SHALL set permissions 0700 on the Runtime_Directory
3. THE Directory_Manager SHALL verify the Runtime_Directory is writable
4. WHEN directory creation fails, THE Directory_Manager SHALL display a specific error message
5. WHEN directory creation fails, THE Directory_Manager SHALL provide manual instructions
6. THE Directory_Manager SHALL create placeholder files to test write permissions
7. THE Directory_Manager SHALL remove placeholder files after testing

### Requirement 6: Installation Validation

**User Story:** As a site administrator, I want the installer to verify everything is working, so that I know the installation completed successfully.

#### Acceptance Criteria

1. THE Installation_Validator SHALL verify the `.env` file exists and is readable
2. THE Installation_Validator SHALL verify the Autoloader exists and is readable
3. THE Installation_Validator SHALL verify PHPMailer class can be loaded
4. THE Installation_Validator SHALL verify the Runtime_Directory exists and is writable
5. THE Installation_Validator SHALL verify the contact API endpoint is accessible
6. THE Installation_Validator SHALL test loading environment variables from `.env`
7. THE Installation_Validator SHALL test SMTP connection using configured credentials
8. WHEN a Validation_Test fails, THE Installation_Validator SHALL display the specific failure
9. WHEN all Validation_Tests pass, THE Installation_Validator SHALL display a success summary
10. THE Installation_Validator SHALL provide links to test the contact form

### Requirement 7: Security Lock Mechanism

**User Story:** As a security engineer, I want the installer to lock itself after successful installation, so that it cannot be run again by unauthorized users.

#### Acceptance Criteria

1. WHEN installation completes successfully, THE Security_Lock SHALL create a Lock_File
2. THE Security_Lock SHALL set permissions 0600 on the Lock_File
3. WHEN the Installer is accessed and Lock_File exists, THE Installer SHALL display a locked message
4. WHEN the Installer is locked, THE Installer SHALL not display any installation forms
5. WHEN the Installer is locked, THE Installer SHALL provide instructions for manual unlock
6. THE Security_Lock SHALL store the installation completion timestamp in the Lock_File
7. THE Security_Lock SHALL store the installation completion IP address in the Lock_File
8. THE Installer SHALL provide an option to delete itself after successful installation
9. WHEN self-deletion is requested, THE Installer SHALL delete `install.php` from the Web_Root
10. WHEN self-deletion succeeds, THE Installer SHALL display a confirmation message

### Requirement 8: Multi-Step Wizard Interface

**User Story:** As a site administrator, I want a guided step-by-step installation process, so that I can complete the setup without confusion.

#### Acceptance Criteria

1. THE Installer SHALL display a progress indicator showing current and total steps
2. THE Installer SHALL organize installation into distinct Wizard_Steps
3. THE Installer SHALL include a System Requirements step as the first Wizard_Step
4. THE Installer SHALL include a Composer Installation step as the second Wizard_Step
5. THE Installer SHALL include an Environment Configuration step as the third Wizard_Step
6. THE Installer SHALL include a Directory Setup step as the fourth Wizard_Step
7. THE Installer SHALL include a Validation step as the fifth Wizard_Step
8. THE Installer SHALL include a Completion step as the final Wizard_Step
9. WHEN a step is incomplete, THE Installer SHALL prevent advancing to the next step
10. WHEN a step is complete, THE Installer SHALL allow returning to previous steps
11. THE Installer SHALL persist step completion status in the Installation_Session
12. THE Installer SHALL display clear instructions for each Wizard_Step

### Requirement 9: Error Handling and Recovery

**User Story:** As a site administrator, I want helpful error messages and recovery options, so that I can resolve installation issues.

#### Acceptance Criteria

1. WHEN an error occurs, THE Installer SHALL display a user-friendly error message
2. WHEN an error occurs, THE Installer SHALL display technical details in a collapsible section
3. WHEN an error occurs, THE Installer SHALL log the error to the Installation_Log
4. WHEN an error occurs, THE Installer SHALL provide suggested resolution steps
5. WHEN an error is recoverable, THE Installer SHALL provide a retry button
6. WHEN an error is not recoverable, THE Installer SHALL provide manual instructions
7. THE Installer SHALL handle PHP errors gracefully without exposing sensitive information
8. THE Installer SHALL handle file permission errors with specific guidance
9. THE Installer SHALL handle network errors during Composer download with retry options
10. THE Installer SHALL handle SMTP connection errors with troubleshooting tips

### Requirement 10: Installation Archive Preparation

**User Story:** As a developer, I want to prepare a deployment archive, so that site administrators can extract and install the site easily.

#### Acceptance Criteria

1. THE Installation_Archive SHALL include all files from the Web_Root directory
2. THE Installation_Archive SHALL include `composer.json` and `composer.lock` in the Project_Root
3. THE Installation_Archive SHALL include `.env.example` in the Project_Root
4. THE Installation_Archive SHALL include `install.php` in the Web_Root
5. THE Installation_Archive SHALL include installation documentation
6. THE Installation_Archive SHALL exclude the `.env` file
7. THE Installation_Archive SHALL exclude the `vendor/` directory
8. THE Installation_Archive SHALL exclude the `var/` directory
9. THE Installation_Archive SHALL exclude `.git` directory and Git files
10. THE Installation_Archive SHALL exclude development scripts and tools
11. THE Installation_Archive SHALL maintain correct directory structure when extracted
12. THE Installation_Archive SHALL include a README with extraction instructions

### Requirement 11: SMTP Connection Testing

**User Story:** As a site administrator, I want to test SMTP settings before saving, so that I know email sending will work.

#### Acceptance Criteria

1. THE Environment_Configurator SHALL provide an "Test SMTP Connection" button
2. WHEN SMTP test is requested, THE Environment_Configurator SHALL attempt to connect to the SMTP server
3. WHEN SMTP test is requested, THE Environment_Configurator SHALL verify authentication succeeds
4. WHEN SMTP test succeeds, THE Environment_Configurator SHALL display connection details
5. WHEN SMTP test fails, THE Environment_Configurator SHALL display the specific error
6. WHEN SMTP test fails, THE Environment_Configurator SHALL suggest common fixes
7. THE Environment_Configurator SHALL test SMTP without sending actual emails
8. THE Environment_Configurator SHALL timeout SMTP tests after 15 seconds
9. THE Environment_Configurator SHALL allow proceeding without SMTP test
10. THE Environment_Configurator SHALL warn if proceeding without successful SMTP test

### Requirement 12: Configuration Presets

**User Story:** As a site administrator, I want configuration presets for common email providers, so that I can set up SMTP quickly.

#### Acceptance Criteria

1. THE Environment_Configurator SHALL provide a preset for Microsoft 365 / Exchange
2. THE Environment_Configurator SHALL provide a preset for Gmail
3. THE Environment_Configurator SHALL provide a preset for generic SMTP
4. WHEN a preset is selected, THE Environment_Configurator SHALL populate SMTP host and port
5. WHEN a preset is selected, THE Environment_Configurator SHALL populate encryption type
6. WHEN a preset is selected, THE Environment_Configurator SHALL leave credentials empty
7. THE Environment_Configurator SHALL allow manual override of preset values
8. THE Environment_Configurator SHALL display preset-specific setup instructions

### Requirement 13: Installation Progress Persistence

**User Story:** As a site administrator, I want installation progress saved, so that I can resume if my browser closes.

#### Acceptance Criteria

1. THE Installer SHALL store Installation_Session data in PHP sessions
2. THE Installer SHALL store completed step status in the Installation_Session
3. THE Installer SHALL store user-provided configuration in the Installation_Session
4. WHEN the browser is closed and reopened, THE Installer SHALL restore Installation_Session
5. WHEN installation is complete, THE Installer SHALL clear the Installation_Session
6. THE Installer SHALL set session timeout to 2 hours
7. WHEN session expires, THE Installer SHALL allow restarting from the beginning

### Requirement 14: Responsive Design

**User Story:** As a site administrator, I want the installer to work on mobile devices, so that I can complete installation from any device.

#### Acceptance Criteria

1. THE Installer SHALL use responsive CSS for layout
2. THE Installer SHALL be usable on screens 320px wide and larger
3. THE Installer SHALL display forms in a mobile-friendly layout
4. THE Installer SHALL use readable font sizes on mobile devices
5. THE Installer SHALL provide touch-friendly buttons and inputs
6. THE Installer SHALL test successfully on iOS Safari and Android Chrome

### Requirement 15: Installation Documentation

**User Story:** As a site administrator, I want clear installation documentation, so that I understand the process and requirements.

#### Acceptance Criteria

1. THE Installer SHALL include inline help text for each configuration field
2. THE Installer SHALL provide a link to full installation documentation
3. THE Installer SHALL document minimum hosting requirements
4. THE Installer SHALL document how to obtain Turnstile API keys
5. THE Installer SHALL document how to configure SMTP credentials
6. THE Installer SHALL document how to unlock or delete the installer after completion
7. THE Installer SHALL document troubleshooting steps for common issues
8. THE Installer SHALL document security best practices post-installation

### Requirement 16: Composer Fallback Options

**User Story:** As a site administrator, I want alternative installation methods if Composer download fails, so that I can complete installation despite network restrictions.

#### Acceptance Criteria

1. WHEN Composer download fails, THE Composer_Installer SHALL display manual installation instructions
2. WHEN Composer download fails, THE Composer_Installer SHALL provide a direct download link
3. WHEN Composer download fails, THE Composer_Installer SHALL allow uploading Composer_Phar manually
4. THE Composer_Installer SHALL detect if Composer is already installed on the server
5. WHEN Composer is pre-installed, THE Composer_Installer SHALL use the system Composer
6. THE Composer_Installer SHALL verify Composer version is 2.0 or higher

### Requirement 17: Environment Variable Validation

**User Story:** As a site administrator, I want the installer to validate my configuration, so that I catch errors before saving.

#### Acceptance Criteria

1. THE Environment_Configurator SHALL validate SMTP host is not empty when SMTP transport is selected
2. THE Environment_Configurator SHALL validate SMTP port is between 1 and 65535
3. THE Environment_Configurator SHALL validate rate limit window is a positive integer
4. THE Environment_Configurator SHALL validate rate limit max requests is a positive integer
5. THE Environment_Configurator SHALL validate allowed origins are valid URLs
6. THE Environment_Configurator SHALL validate timezone is a valid PHP timezone
7. WHEN validation fails, THE Environment_Configurator SHALL highlight the invalid field
8. WHEN validation fails, THE Environment_Configurator SHALL display the validation error
9. WHEN validation fails, THE Environment_Configurator SHALL prevent form submission

### Requirement 18: Installation Logging

**User Story:** As a developer, I want detailed installation logs, so that I can troubleshoot issues.

#### Acceptance Criteria

1. THE Installer SHALL create an Installation_Log file in the Project_Root
2. THE Installer SHALL log each Wizard_Step completion with timestamp
3. THE Installer SHALL log all errors with full details
4. THE Installer SHALL log all warnings with context
5. THE Installer SHALL log Composer output during dependency installation
6. THE Installer SHALL log SMTP test results
7. THE Installer SHALL log Validation_Test results
8. THE Installer SHALL set permissions 0600 on the Installation_Log
9. WHEN installation completes, THE Installer SHALL offer to download the Installation_Log
10. THE Installer SHALL provide instructions for deleting the Installation_Log after review

### Requirement 19: Minimal Dependencies

**User Story:** As a developer, I want the installer to have minimal dependencies, so that it works in restricted hosting environments.

#### Acceptance Criteria

1. THE Installer SHALL be a single PHP file
2. THE Installer SHALL not require external JavaScript libraries
3. THE Installer SHALL not require external CSS frameworks
4. THE Installer SHALL use only standard PHP extensions available in cPanel
5. THE Installer SHALL not require database access
6. THE Installer SHALL not require write access outside Project_Root and Web_Root
7. THE Installer SHALL work with PHP safe_mode disabled
8. THE Installer SHALL work with open_basedir restrictions

### Requirement 20: Post-Installation Cleanup

**User Story:** As a security engineer, I want the installer to clean up after itself, so that no installation artifacts remain.

#### Acceptance Criteria

1. THE Installer SHALL offer to delete itself after successful installation
2. THE Installer SHALL offer to delete the Installation_Log after successful installation
3. THE Installer SHALL offer to delete the Composer_Phar after successful installation
4. WHEN cleanup is requested, THE Installer SHALL delete the specified files
5. WHEN cleanup is requested, THE Installer SHALL verify deletion succeeded
6. WHEN cleanup fails, THE Installer SHALL provide manual deletion instructions
7. THE Installer SHALL display a list of files to delete manually if automatic cleanup fails
8. THE Installer SHALL recommend keeping the Lock_File for security

