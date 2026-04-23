/**
 * Mobile Error Logger for Debugging Crashes
 * This script logs all errors and performance issues to help identify crash causes
 */

(function() {
  'use strict';

  const logs = [];
  const MAX_LOGS = 100;
  const isMobile = window.innerWidth <= 768 || /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

  // Create visible error display
  function createErrorDisplay() {
    const display = document.createElement('div');
    display.id = 'error-logger-display';
    display.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: rgba(255, 0, 0, 0.9);
      color: white;
      padding: 10px;
      font-family: monospace;
      font-size: 11px;
      z-index: 999999;
      max-height: 150px;
      overflow-y: auto;
      display: none;
    `;
    document.body.appendChild(display);
    return display;
  }

  // Log function
  function log(type, message, data) {
    const timestamp = new Date().toISOString();
    const entry = {
      timestamp,
      type,
      message,
      data: data || {},
      memory: performance.memory ? {
        used: Math.round(performance.memory.usedJSHeapSize / 1048576) + 'MB',
        total: Math.round(performance.memory.totalJSHeapSize / 1048576) + 'MB',
        limit: Math.round(performance.memory.jsHeapSizeLimit / 1048576) + 'MB'
      } : 'N/A'
    };

    logs.push(entry);
    if (logs.length > MAX_LOGS) {
      logs.shift();
    }

    // Store in localStorage
    try {
      localStorage.setItem('etherr-error-logs', JSON.stringify(logs));
    } catch (e) {
      console.warn('Could not save logs to localStorage');
    }

    // Console log
    console.log(`[${type}] ${message}`, data);

    // Update display
    updateDisplay();
  }

  function updateDisplay() {
    const display = document.getElementById('error-logger-display');
    if (!display) return;

    const lastLogs = logs.slice(-5);
    display.innerHTML = lastLogs.map(entry => 
      `<div style="border-bottom: 1px solid rgba(255,255,255,0.3); padding: 5px 0;">
        <strong>${entry.type}</strong>: ${entry.message}<br>
        <small>Memory: ${entry.memory.used || 'N/A'} | ${entry.timestamp.split('T')[1].split('.')[0]}</small>
      </div>`
    ).join('');
    display.style.display = 'block';
  }

  // Initialize
  if (isMobile) {
    log('INIT', 'Error logger started on mobile device', {
      userAgent: navigator.userAgent,
      viewport: `${window.innerWidth}x${window.innerHeight}`,
      deviceMemory: navigator.deviceMemory || 'unknown'
    });

    // Create display after DOM loads
    if (document.body) {
      createErrorDisplay();
    } else {
      document.addEventListener('DOMContentLoaded', createErrorDisplay);
    }
  }

  // Catch all errors
  window.addEventListener('error', function(event) {
    log('ERROR', event.message, {
      filename: event.filename,
      lineno: event.lineno,
      colno: event.colno,
      stack: event.error ? event.error.stack : 'N/A'
    });
  });

  // Catch unhandled promise rejections
  window.addEventListener('unhandledrejection', function(event) {
    log('PROMISE_REJECTION', event.reason, {
      promise: event.promise
    });
  });

  // Monitor scroll events
  let scrollCount = 0;
  window.addEventListener('scroll', function() {
    scrollCount++;
    if (scrollCount % 10 === 0) {
      log('SCROLL', `Scroll event #${scrollCount}`, {
        scrollY: window.scrollY,
        scrollHeight: document.documentElement.scrollHeight
      });
    }
  }, { passive: true });

  // Monitor iframe loads
  document.addEventListener('DOMContentLoaded', function() {
    log('DOM_LOADED', 'DOM Content Loaded');

    const iframes = document.querySelectorAll('iframe');
    log('IFRAMES', `Found ${iframes.length} iframes`, {
      count: iframes.length,
      sources: Array.from(iframes).map(f => f.src || 'no-src')
    });

    iframes.forEach((iframe, index) => {
      iframe.addEventListener('load', function() {
        log('IFRAME_LOAD', `Iframe #${index} loaded`, {
          src: iframe.src,
          index: index
        });
      });

      iframe.addEventListener('error', function() {
        log('IFRAME_ERROR', `Iframe #${index} failed`, {
          src: iframe.src,
          index: index
        });
      });
    });
  });

  // Monitor visibility changes
  document.addEventListener('visibilitychange', function() {
    log('VISIBILITY', document.hidden ? 'Page hidden' : 'Page visible');
  });

  // Monitor memory warnings (if available)
  if ('memory' in performance) {
    setInterval(function() {
      const memory = performance.memory;
      const usedPercent = (memory.usedJSHeapSize / memory.jsHeapSizeLimit) * 100;
      
      if (usedPercent > 80) {
        log('MEMORY_WARNING', `High memory usage: ${usedPercent.toFixed(1)}%`, {
          used: Math.round(memory.usedJSHeapSize / 1048576) + 'MB',
          limit: Math.round(memory.jsHeapSizeLimit / 1048576) + 'MB'
        });
      }
    }, 2000);
  }

  // Export logs function
  window.getErrorLogs = function() {
    return logs;
  };

  window.exportErrorLogs = function() {
    const logText = logs.map(entry => 
      `[${entry.timestamp}] ${entry.type}: ${entry.message}\n` +
      `  Memory: ${JSON.stringify(entry.memory)}\n` +
      `  Data: ${JSON.stringify(entry.data)}\n`
    ).join('\n');
    
    console.log('=== ERROR LOGS ===\n' + logText);
    
    // Try to copy to clipboard
    if (navigator.clipboard) {
      navigator.clipboard.writeText(logText).then(() => {
        alert('Logs copied to clipboard!');
      }).catch(() => {
        alert('Logs printed to console. Please copy from there.');
      });
    } else {
      alert('Logs printed to console. Please copy from there.');
    }
    
    return logText;
  };

  // Add export button
  if (isMobile) {
    setTimeout(function() {
      const button = document.createElement('button');
      button.textContent = 'Export Logs';
      button.style.cssText = `
        position: fixed;
        bottom: 10px;
        right: 10px;
        z-index: 999999;
        background: #ff0000;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        font-weight: bold;
        font-size: 12px;
      `;
      button.onclick = window.exportErrorLogs;
      document.body.appendChild(button);
    }, 1000);
  }

  log('LOGGER', 'Error logger initialized successfully');
})();
