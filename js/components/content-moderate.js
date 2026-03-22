/**
 * ShipperShop Component — Content Moderation
 */
window.SS = window.SS || {};

SS.ContentModerate = {
  check: function(text, containerId) {
    if (!text || text.length < 5) return;
    SS.api.post('/content-moderate.php', {text: text}).then(function(d) {
      var data = d.data || {};
      var el = document.getElementById(containerId);
      if (!el) return;
      if (data.safe) {
        el.innerHTML = '<span class="text-xs" style="color:var(--success)">✅ An toan (' + data.score + '/100)</span>';
      } else {
        el.innerHTML = '<span class="text-xs" style="color:var(--danger)">⚠️ ' + data.issue_count + ' van de (' + data.score + '/100)</span>';
      }
    }).catch(function() {});
  },
  showDetail: function(text) {
    SS.api.post('/content-moderate.php', {text: text}).then(function(d) {
      var data = d.data || {};
      var color = data.safe ? 'var(--success)' : 'var(--danger)';
      var html = '<div class="text-center mb-3"><div style="font-size:36px">' + (data.safe ? '✅' : '⚠️') + '</div><div class="font-bold" style="color:' + color + '">' + (data.safe ? 'Noi dung an toan' : 'Phat hien van de') + '</div><div class="text-sm text-muted">Diem: ' + (data.score || 0) + '/100</div></div>';
      var issues = data.issues || [];
      if (issues.length) {
        html += '<div class="text-sm font-bold mb-2">Chi tiet</div>';
        for (var i = 0; i < issues.length; i++) {
          var is = issues[i];
          var sevColors = {low: 'var(--text-muted)', medium: 'var(--warning)', high: 'var(--danger)', critical: '#dc2626'};
          html += '<div class="card mb-1" style="padding:8px;border-left:3px solid ' + (sevColors[is.severity] || '') + '"><div class="text-sm">' + SS.utils.esc(is.type) + ': ' + SS.utils.esc(is.keyword || is.detail || '') + '</div><div class="text-xs text-muted">' + SS.utils.esc(is.severity) + '</div></div>';
        }
      }
      SS.ui.sheet({title: 'Kiem duyet noi dung', html: html});
    });
  }
};
