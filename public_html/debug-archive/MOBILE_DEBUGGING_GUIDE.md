# 🔍 Mobile Debugging Guide - Find the Crash Cause

## Files Created for Debugging

### 1. `error-logger.js` - Error Tracking Script
- Logs all JavaScript errors
- Tracks memory usage
- Monitors scroll events
- Tracks iframe loads
- Shows errors in red banner at top of page
- Has "Export Logs" button to copy all logs

### 2. `projekti-safe.html` - Safe Mode Testing Page
- Minimal version of projekti page
- Load iframes ONE AT A TIME to test which causes crash
- Special focus on reservation section (2 iframes)
- Built-in export logs button

### 3. `projekti.html` - Updated with Error Logger
- Your main page now has error logging enabled
- Will track what happens before crash

## Testing Instructions

### Option A: Test with Safe Mode (RECOMMENDED)

1. **Upload files to hosting:**
   - `error-logger.js`
   - `projekti-safe.html`

2. **Open on iPhone:**
   - Safari: `https://etherr.hr/projekti-safe.html`
   - Chrome: `https://etherr.hr/projekti-safe.html`

3. **Follow the test steps:**
   - You'll see a red banner "SAFE MODE"
   - Scroll slowly through the page
   - Click "Load Keef Demo" - does it crash?
   - Click "Load Keep Going Demo" - does it crash?
   - Scroll to "Test 2: Reservation Section"
   - Click "Load Schedule Demo" - does it crash?
   - Click "Load Calendar Demo" - does it crash?
   - Click "Load BOTH at once" - does it crash? ← This is the suspected cause

4. **Export logs:**
   - Click the red "Export Error Logs" button
   - Logs will be copied to clipboard
   - Paste into Notes app or email
   - Send to me

### Option B: Test with Main Page

1. **Upload files to hosting:**
   - `error-logger.js`
   - `projekti.html` (updated version)

2. **Open on iPhone:**
   - Safari: `https://etherr.hr/projekti.html`

3. **What you'll see:**
   - Red banner at top showing errors (if any)
   - Red "Export Logs" button in bottom-right corner

4. **When it crashes:**
   - If you can, click "Export Logs" before crash
   - If it crashes immediately, reload and check localStorage:
     - Open Safari Developer Tools (if possible)
     - Or use this bookmarklet after reload:
       ```javascript
       javascript:alert(localStorage.getItem('etherr-error-logs'))
       ```

## What to Look For in Logs

The logs will show:

1. **INIT** - When page starts loading
2. **DOM_LOADED** - When HTML is ready
3. **IFRAMES** - How many iframes found and their sources
4. **IFRAME_LOAD** - When each iframe loads successfully
5. **IFRAME_ERROR** - When iframe fails to load
6. **SCROLL** - Scroll position (every 10 scrolls)
7. **MEMORY_WARNING** - If memory usage goes above 80%
8. **ERROR** - Any JavaScript errors
9. **PROMISE_REJECTION** - Any async errors

## Expected Results

### If Reservation Iframes Cause Crash:
```
[SCROLL] Scroll event #30 - scrollY: 2400
[IFRAME_LOAD] Iframe #3 loaded - reservation-schedule-demo.html
[MEMORY_WARNING] High memory usage: 85%
[IFRAME_LOAD] Iframe #4 loaded - reservation-calendar-demo.html
[MEMORY_WARNING] High memory usage: 95%
[CRASH - no more logs]
```

### If Network Animation Causes Crash:
```
[INIT] Error logger started
[DOM_LOADED] DOM Content Loaded
[ERROR] Canvas context error
[MEMORY_WARNING] High memory usage: 90%
[CRASH - no more logs]
```

### If Something Else Causes Crash:
```
[ERROR] Specific error message
[Stack trace information]
```

## How to Send Me the Logs

### Method 1: Copy from Safe Mode
1. Click "Export Error Logs" button
2. Logs copied to clipboard
3. Paste into email or message
4. Send to me

### Method 2: From Console (if accessible)
1. Open Safari/Chrome DevTools on Mac
2. Connect iPhone via USB
3. Open Web Inspector
4. Type: `window.exportErrorLogs()`
5. Copy output

### Method 3: From localStorage
1. After crash, reload page
2. Open console
3. Type: `localStorage.getItem('etherr-error-logs')`
4. Copy output

## Quick Diagnosis Guide

| Symptom | Likely Cause |
|---------|--------------|
| Crashes immediately on load | Network animation or initial script |
| Crashes when scrolling to reservation section | Reservation iframes (2 at once) |
| Crashes randomly while scrolling | Memory leak in scroll handler |
| Crashes on specific project section | That project's iframe |
| Works in Safe Mode but not main page | Network animation or complex CSS |

## Next Steps After Getting Logs

Send me the logs and tell me:
1. Which browser (Safari/Chrome)
2. At what point it crashed (immediately/after scroll/specific section)
3. Did Safe Mode work better than main page?
4. Which specific button/action caused crash in Safe Mode?

I'll analyze the logs and create a targeted fix based on the exact error.
