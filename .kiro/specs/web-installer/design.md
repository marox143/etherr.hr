# Design Document: Web Installer

## Overview

This design document specifies the technical approach for creating a browser-based installation wizard for the Etherr marketing website. The installer automates the deployment process for cPanel hosting environments where SSH access is unavailable, guiding administrators through system requirements checking, Composer dependency installation, environment configuration, directory setup, and installation validation.

### Goals

1. **Zero-SSH Deployment**: Enable complete site setup through a web browser without command-line access
2. **Guided Experience**: Provide a step-by-step wizard interface that prevents configuration errors
3. **Robust Error Handling**: Offer clear error messages and recovery options for common issues
4. **Security First**: Implement self-destruct mechanisms and prevent unauthorized re-execution
5. **Minimal Dependencies**: Work in restricted hosting environments with only standard PHP extensions

### Non-Goals

1. Modifying the core application functionality or features
2. Providing a control panel for ongoing site management
3. Supporting non-cPanel hosting environments (though may work elsewhere)
4. Handling database migrations or schema management (site has no database)
5. Providing automated updates or version management

### Success Criteria

- Installer accessible at `https://domain.com/install.php`
- All system requirements validated before proceeding
- Composer dependencies installed successfully
- `.env` file generated with user-provided configuration
- SMTP connection tested and verified
- Runtime directories created with correct permissions
- Installation locked after successful completion
- Installer can self-delete after completion
- Works on standard cPanel shared hosting with PHP 8.1+

## Architecture

### Single-File Design Philosophy

The installer is implemented as a single PHP file (`install.php`) that contains:
- All HTML markup (embedded in PHP)
- All CSS styles (inline `<style>` block)
- All JavaScript (inline `<script>` block)
- All PHP logic (functions and execution flow)

This design eliminates external dependencies and ensures the installer works even when the directory structure is incomplete or misconfigured.

### Execution Flow

```mermaid
graph TD
    A[Access install.php] --> B{Lock file exists?}
    B -->|Yes| C[Display locked message]
    B -->|No| D{Session exists?}
    D -->|No| E[Initialize session]
    D -->|Yes| F[Load session state]
    E --> G[Display current step]
    F --> G
    G --> H{User submits form}
    H --> I[Validate input]
    I --> J{Valid?}
    J -->|No| K[Show errors, stay on step]
    J -->|Yes| L[Execute step logic]
    L --> M{Success?}
    M -->|No| K
    M -->|Yes| N[Update session state]
    N --> O{Last step?}
    O -->|No| P[Advance to next step]
    O -->|Yes| Q[Create lock file]
    Q --> R[Display completion]
    P --> G
    
    style C fill:#ffcccc
    style R fill:#ccffcc
