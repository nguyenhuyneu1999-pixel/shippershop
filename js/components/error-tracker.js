/**
 * ShipperShop Component — Error Tracker (Admin)
 */
window.SS = window.SS || {};

SS.ErrorTracker = {
  show: function() {
    SS.api.get('/error-tracker.php').then(function(d) {
      var data = d.data || {};
      var html = '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:10px;flex:1"><div class="font-bold text-lg" style="color:' + ((data.total_24h || 0) > 10 ? 'var(--danger)' : 'var(--success)') + '">' + (data.total_24h || 0) + '</div><div class="text-xs text-muted">Loi 24h</div></div>'
        + '<div class="card" style="padding:10px;flex:1"><div class="font-bold text-lg">' + (data.total_all || 0) + '</div><div class="text-xs text-muted">Tong loi</div></div></div>';
      // Client errors
      var ce = data.client_errors || [];
      if (ce.length) {
        html += '<div class="text-sm font-bold mb-2">Client errors</div>';
        for (var i = Math.max(0, ce.length - 5); i < ce.length; i++) {
          html += '<div class="card mb-1" style="padding:8px;border-left:3px solid var(--danger)"><div class="text-xs">' + SS.utils.esc((ce[i].message || '').substring(0, 80)) + '</div>'
            + '<div class="text-xs text-muted">' + SS.utils.esc(ce[i].url || '') + ' · ' + SS.utils.ago(ce[i].reported_at) + '</div></div>';
        }
      }
      // API errors
      var ae = data.api_errors || [];
      if (ae.length) {
        html += '<div class="text-sm font-bold mb-2 mt-3">API errors</div>';
        for (var j = 0; j < Math.min(ae.length, 5); j++) {
          html += '<div class="text-xs p-1" style="border-bottom:1px solid var(--border-light)">' + SS.utils.esc(ae[j].action) + ' · ' + SS.utils.ago(ae[j].created_at) + '</div>';
        }
      }
      SS.ui.sheet({title: '🐛 Error Tracker', html: html});
    });
  },
  // Auto-report client errors
  init: function() {
    window.addEventListener('error', function(e) {
      SS.api.post('/error-tracker.php', {message: e.message || '', url: e.filename || window.location.href, stack: (e.error && e.error.stack) ? e.error.stack.substring(0, 500) : ''}).catch(function() {});
    });
  }
};
