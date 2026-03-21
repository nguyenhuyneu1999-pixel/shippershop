/**
 * ShipperShop Component — Performance Reporter
 * Collects Web Vitals (LCP, FID, CLS) and page load timing
 * Sends metrics to /api/v2/perf.php via beacon
 */
window.SS = window.SS || {};

SS.PerfReporter = {
  _metrics: {},
  _sent: false,

  init: function() {
    // Page load timing
    if (window.performance && performance.timing) {
      window.addEventListener('load', function() {
        setTimeout(function() {
          var t = performance.timing;
          SS.PerfReporter._metrics.dns = t.domainLookupEnd - t.domainLookupStart;
          SS.PerfReporter._metrics.tcp = t.connectEnd - t.connectStart;
          SS.PerfReporter._metrics.ttfb = t.responseStart - t.requestStart;
          SS.PerfReporter._metrics.dom_ready = t.domContentLoadedEventEnd - t.navigationStart;
          SS.PerfReporter._metrics.load = t.loadEventEnd - t.navigationStart;
          SS.PerfReporter._metrics.dom_nodes = document.querySelectorAll('*').length;
          SS.PerfReporter._send();
        }, 100);
      });
    }

    // Largest Contentful Paint
    if (window.PerformanceObserver) {
      try {
        new PerformanceObserver(function(list) {
          var entries = list.getEntries();
          if (entries.length) {
            SS.PerfReporter._metrics.lcp = Math.round(entries[entries.length - 1].startTime);
          }
        }).observe({type: 'largest-contentful-paint', buffered: true});
      } catch (e) {}

      // First Input Delay
      try {
        new PerformanceObserver(function(list) {
          var entries = list.getEntries();
          if (entries.length) {
            SS.PerfReporter._metrics.fid = Math.round(entries[0].processingStart - entries[0].startTime);
          }
        }).observe({type: 'first-input', buffered: true});
      } catch (e) {}

      // Cumulative Layout Shift
      try {
        var clsValue = 0;
        new PerformanceObserver(function(list) {
          for (var i = 0; i < list.getEntries().length; i++) {
            if (!list.getEntries()[i].hadRecentInput) {
              clsValue += list.getEntries()[i].value;
            }
          }
          SS.PerfReporter._metrics.cls = Math.round(clsValue * 1000) / 1000;
        }).observe({type: 'layout-shift', buffered: true});
      } catch (e) {}
    }

    // Send on page hide
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) SS.PerfReporter._send();
    });
  },

  _send: function() {
    if (SS.PerfReporter._sent) return;
    if (!Object.keys(SS.PerfReporter._metrics).length) return;
    SS.PerfReporter._sent = true;

    var page = window.location.pathname.replace('/', '').replace('.html', '') || 'index';
    var payload = JSON.stringify({page: page, metrics: SS.PerfReporter._metrics});

    if (navigator.sendBeacon) {
      navigator.sendBeacon('/api/v2/perf.php', payload);
    }
  },

  // Get current metrics (for debug)
  getMetrics: function() {
    return SS.PerfReporter._metrics;
  }
};

// Auto-init
SS.PerfReporter.init();
