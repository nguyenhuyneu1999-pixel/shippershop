/**
 * ShipperShop Component — API Performance Monitor
 * Tracks API response times, logs slow requests, shows dev badge
 */
window.SS = window.SS || {};

SS.PerfMonitor = {
  _log: [],
  _maxLog: 100,

  // Record API call timing
  record: function(url, method, duration, status) {
    var entry = {
      url: url.replace(/https?:\/\/[^\/]+/, '').split('?')[0],
      method: method || 'GET',
      ms: Math.round(duration),
      status: status || 200,
      time: Date.now()
    };

    SS.PerfMonitor._log.push(entry);
    if (SS.PerfMonitor._log.length > SS.PerfMonitor._maxLog) {
      SS.PerfMonitor._log.shift();
    }

    // Warn on slow requests (> 2s)
    if (entry.ms > 2000 && SS.ui) {
      SS.ui.toast('API chậm: ' + entry.url + ' (' + entry.ms + 'ms)', 'warning', 3000);
    }
  },

  // Get stats
  getStats: function() {
    var log = SS.PerfMonitor._log;
    if (!log.length) return {count: 0, avg: 0, max: 0, slow: 0};

    var total = 0;
    var maxMs = 0;
    var slow = 0;
    var errors = 0;

    for (var i = 0; i < log.length; i++) {
      total += log[i].ms;
      if (log[i].ms > maxMs) maxMs = log[i].ms;
      if (log[i].ms > 1000) slow++;
      if (log[i].status >= 400) errors++;
    }

    return {
      count: log.length,
      avg: Math.round(total / log.length),
      max: maxMs,
      slow: slow,
      errors: errors,
      p95: SS.PerfMonitor._percentile(95)
    };
  },

  _percentile: function(pct) {
    var sorted = SS.PerfMonitor._log.map(function(l) { return l.ms; }).sort(function(a, b) { return a - b; });
    if (!sorted.length) return 0;
    var idx = Math.ceil(sorted.length * pct / 100) - 1;
    return sorted[Math.min(idx, sorted.length - 1)];
  },

  // Show performance dashboard (dev tool)
  showDashboard: function() {
    var stats = SS.PerfMonitor.getStats();
    var log = SS.PerfMonitor._log.slice(-20).reverse();

    var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px">'
      + '<div class="text-center"><div style="font-size:20px;font-weight:800;color:var(--primary)">' + stats.avg + 'ms</div><div class="text-xs text-muted">Trung bình</div></div>'
      + '<div class="text-center"><div style="font-size:20px;font-weight:800;color:' + (stats.p95 > 1000 ? 'var(--danger)' : 'var(--success)') + '">' + stats.p95 + 'ms</div><div class="text-xs text-muted">P95</div></div>'
      + '<div class="text-center"><div style="font-size:20px;font-weight:800;color:' + (stats.errors > 0 ? 'var(--danger)' : 'var(--success)') + '">' + stats.errors + '</div><div class="text-xs text-muted">Errors</div></div>'
      + '</div>';

    html += '<div class="text-sm font-bold mb-2">Gần đây (' + stats.count + ' requests)</div>';
    for (var i = 0; i < log.length; i++) {
      var l = log[i];
      var color = l.ms > 1000 ? 'var(--danger)' : (l.ms > 500 ? 'var(--warning)' : 'var(--success)');
      html += '<div style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:12px;font-family:monospace">'
        + '<span style="color:' + color + ';font-weight:700;min-width:50px">' + l.ms + 'ms</span>'
        + '<span class="text-muted" style="min-width:35px">' + l.method + '</span>'
        + '<span class="flex-1 truncate">' + SS.utils.esc(l.url) + '</span>'
        + '<span style="color:' + (l.status >= 400 ? 'var(--danger)' : 'var(--text-muted)') + '">' + l.status + '</span>'
        + '</div>';
    }

    if (SS.ui) SS.ui.sheet({title: 'API Performance (' + stats.count + ' calls)', html: html});
  }
};
