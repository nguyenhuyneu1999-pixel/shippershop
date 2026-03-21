/**
 * ShipperShop Component — Error Boundary
 * Catches unhandled errors, reports to server, shows user-friendly toast
 * Also handles unhandled promise rejections
 */
window.SS = window.SS || {};

SS.ErrorBoundary = {
  _reported: {},
  _maxReports: 10,

  init: function() {
    // Global error handler
    window.onerror = function(msg, url, line, col, error) {
      SS.ErrorBoundary._handle({
        type: 'error',
        message: msg,
        source: url,
        line: line,
        col: col,
        stack: error ? error.stack : ''
      });
      return false; // Don't suppress
    };

    // Unhandled promise rejections
    window.addEventListener('unhandledrejection', function(e) {
      var reason = e.reason;
      SS.ErrorBoundary._handle({
        type: 'promise',
        message: reason ? (reason.message || String(reason)) : 'Unknown rejection',
        stack: reason ? reason.stack : ''
      });
    });

    // Resource load errors (images, scripts)
    window.addEventListener('error', function(e) {
      if (e.target && e.target.tagName && e.target.tagName !== 'SCRIPT') return;
      if (e.target && e.target.src) {
        SS.ErrorBoundary._handle({
          type: 'resource',
          message: 'Failed to load: ' + e.target.src,
          source: e.target.src
        });
      }
    }, true);
  },

  _handle: function(err) {
    // Deduplicate
    var key = (err.message || '') + (err.line || '');
    if (SS.ErrorBoundary._reported[key]) return;
    if (Object.keys(SS.ErrorBoundary._reported).length >= SS.ErrorBoundary._maxReports) return;
    SS.ErrorBoundary._reported[key] = true;

    // Log to console
    console.error('[SS Error]', err.type, err.message, err.source ? ('at ' + err.source + ':' + err.line) : '');

    // Report to server (fire-and-forget)
    try {
      var payload = {
        type: err.type,
        message: (err.message || '').substring(0, 500),
        source: (err.source || '').substring(0, 200),
        line: err.line,
        page: window.location.pathname,
        ua: navigator.userAgent.substring(0, 200),
        timestamp: new Date().toISOString()
      };
      var tk = localStorage.getItem('token');
      navigator.sendBeacon('/api/v2/analytics.php', JSON.stringify({
        page: '_js_error',
        referrer: JSON.stringify(payload)
      }));
    } catch (ex) {}

    // User-friendly toast for critical errors only
    if (err.type === 'error' && SS.ui && err.message && err.message.indexOf('Script error') === -1) {
      // Only show for non-trivial errors
      if (err.message.indexOf('fetch') > -1 || err.message.indexOf('network') > -1) {
        SS.ui.toast('Lỗi kết nối. Vui lòng thử lại.', 'error', 3000);
      }
    }
  },

  // Manual error report
  report: function(context, error) {
    SS.ErrorBoundary._handle({
      type: 'manual',
      message: context + ': ' + (error ? error.message : 'Unknown'),
      stack: error ? error.stack : ''
    });
  }
};

// Auto-init immediately (before other scripts)
SS.ErrorBoundary.init();
