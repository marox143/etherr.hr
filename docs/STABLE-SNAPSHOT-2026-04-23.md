# Stable Snapshot (2026-04-23)

This document captures the current stable manual changes that were present in the repository before the documentation pass.

## Summary

The stable snapshot shifts the site from a lighter project-preview setup to a fuller self-hosted showcase:

- the homepage services section now runs from a centralized multi-group service catalog
- the projects page has been reordered and expanded around local live demos and mobile-first previews
- previously external project demo dependencies were rewritten to local bundled assets
- several mobile and iOS stability fixes were added across the demo experience

## User-Facing Changes

### Homepage

- The hero secondary CTA on `public_html/index.html` now routes to `/projekti.html`.
- The services section is now driven by `SERVICE_CATALOG` in `public_html/script.js`.
- The contact intake service options are synchronized with the same service taxonomy used in the services section.
- The active service panels now support richer detail content, including includes-lists and mobile detail handling.

### Projects Page

- `public_html/projekti.html` now leads with:
  - Keef Bar mobile menu
  - Keep Going website and AI assistant
  - reservation system showcase
  - Almagea webshop
  - Juvy Skin webshop
  - Ripple project dashboard
  - DFA education platform
- Reservation and Ripple gained explicit mobile demo triggers that open a fullscreen overlay instead of relying only on inline embeds.
- Inline project iframes now use `data-project-embed-src` so they can be lazy-loaded and handled differently on smaller viewports.
- Mobile poster images were added under `public_html/assets/projects-mobile/` to reduce perceived loading cost while iframe demos initialize.

### Self-Hosted Demo Assets

- `public_html/keef-demo.html` was converted from externally dependent WordPress and plugin URLs to local assets under `public_html/assets/qr-digital-pricelist/`.
- `public_html/juvy-demo.html` now points to the bundled snapshot under `public_html/assets/juvy-site/`.
- `public_html/keepgoing-demo.html` now points to the bundled snapshot under `public_html/assets/keepgoing-site/`.
- The Juvy hero video asset moved from the tracked `.mov` file to a local `video-header.mp4` plus `video-header-poster.jpg`.

### Mobile and Stability Work

- `public_html/assets/qr-digital-pricelist/assets/frontend.js` disables the floating-logo animation layer on mobile widths to avoid iOS crashes in the Keef embedded preview.
- `public_html/reservation-schedule-demo.html` and `public_html/reservation-calendar-demo.html` gained touch drag-and-drop support, viewport locking, and more stable month selection behavior.
- `public_html/script.js` now pauses the network canvas while the project demo overlay is open and caps network density more aggressively on mobile.
- `public_html/script.js` also seeds network-node placement per session so the background feels stable across resizes.
- `public_html/style.css` and `public_html/script.js` include additional iOS Safari fixes for flip cards, project actions, and service-panel mobile detail behavior.

## Repository Structure Impact

New or newly used runtime paths in this stable snapshot include:

- `public_html/assets/icons/` for shared arrow-mask UI assets
- `public_html/assets/projects-mobile/` for lightweight project preview imagery
- `public_html/assets/juvy-site/` and `public_html/assets/keepgoing-site/` for self-hosted WordPress snapshots
- `public_html/debug-archive/` for optional mobile debugging assets loaded only when `?debug-mobile=1` or `localStorage.etherr-debug-mobile=1` is set
- `scripts/build-hosting-package.sh` for assembling a cPanel-ready package

## Intentional Notes

- Kota assets and supporting CSS and JS remain in the repository, but the current `projekti.html` no longer renders the Kota showcase.
- The debug archive is intentionally dormant in normal browsing and exists as a troubleshooting aid for the projects page on mobile devices.
- Generated archives and local tooling output should not be treated as part of the runtime snapshot unless explicitly staged for release.
