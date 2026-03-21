/**
 * ShipperShop — Frontend Error Tracker
 * Captures unhandled JS errors and promise rejections, sends to /api/v2/analytics.php
 * Load this BEFORE other scripts for maximum coverage
 */
(function() {
  var MAX_ERRORS = 5; // per page load
  var errorCount = 0;
  var sentErrors = {};

  function sendError(data) {
    if (errorCount >= MAX_ERRORS) return;
    var key = data.message + data.source + data.line;
    if (sentErrors[key]) return;
    sentErrors[key] = true;
    errorCount++;

    data.page = window.location.pathname;
    data.ua = navigator.userAgent;
    data.ts = new Date().toISOString();
    data.userId = null;
    try { var u = JSON.parse(localStorage.getItem('user') || '{}'); data.userId = u.id || null; } catch(e) {}

    // Send via beacon (non-blocking)
    if (navigator.sendBeacon) {
      navigator.sendBeacon('/api/v2/analytics.php?action=error', JSON.stringify(data));
    } else {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '/api/v2/analytics.php?action=error', true);
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.send(JSON.stringify(data));
    }
  }

  // Global error handler
  window.addEventListener('error', function(e) {
    sendError({
      type: 'error',
      message: e.message || 'Unknown error',
      source: e.filename || '',
      line: e.lineno || 0,
      col: e.colno || 0,
      stack: e.error && e.error.stack ? e.error.stack.substring(0, 500) : ''
    });
  });

  // Unhandled promise rejection
  window.addEventListener('unhandledrejection', function(e) {
    sendError({
      type: 'promise',
      message: e.reason ? (e.reason.message || String(e.reason)).substring(0, 200) : 'Unhandled rejection',
      source: '',
      line: 0,
      stack: e.reason && e.reason.stack ? e.reason.stack.substring(0, 500) : ''
    });
  });

  // Performance observer for long tasks
  if (window.PerformanceObserver) {
    try {
      var longTaskObserver = new PerformanceObserver(function(list) {
        var entries = list.getEntries();
        for (var i = 0; i < entries.length; i++) {
          if (entries[i].duration > 200) {
            sendError({
              type: 'long_task',
              message: 'Long task: ' + Math.round(entries[i].duration) + 'ms',
              source: entries[i].name || '',
              line: 0
            });
          }
        }
      });
      longTaskObserver.observe({entryTypes: ['longtask']});
    } catch(e) {}
  }
})();
