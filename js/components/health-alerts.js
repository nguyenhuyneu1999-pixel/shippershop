/**
 * ShipperShop Component — Health Alerts
 * Shows system status indicator + alerts panel for admins
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.HealthAlerts = {

  // Check and show indicator
  init: function(containerId) {
    var el = containerId ? document.getElementById(containerId) : null;
    SS.api.get('/health-alerts.php').then(function(d) {
      var data = d.data || {};
      var status = data.status || 'healthy';
      var alerts = data.alerts || [];

      // Update indicator
      if (el) {
        var colors = {healthy: '#22c55e', warning: '#f59e0b', critical: '#ef4444'};
        var labels = {healthy: 'Hệ thống bình thường', warning: 'Có cảnh báo', critical: 'Có sự cố'};
        el.innerHTML = '<div style="display:flex;align-items:center;gap:6px;cursor:pointer" onclick="SS.HealthAlerts.showPanel()">'
          + '<div style="width:8px;height:8px;border-radius:50%;background:' + (colors[status] || '#999') + (status !== 'healthy' ? ';animation:pulse 1.5s infinite' : '') + '"></div>'
          + '<span class="text-xs">' + (labels[status] || status) + '</span></div>';
      }

      // Show toast for critical
      if (status === 'critical' && SS.ui) {
        SS.ui.toast('⚠️ Hệ thống có sự cố!', 'error', 5000);
      }
    }).catch(function() {});
  },

  showPanel: function() {
    SS.api.get('/health-alerts.php').then(function(d) {
      var data = d.data || {};
      var alerts = data.alerts || [];
      var status = data.status || 'healthy';

      var statusColors = {healthy: 'var(--success)', warning: 'var(--warning)', critical: 'var(--danger)'};
      var html = '<div class="text-center mb-3">'
        + '<div style="width:48px;height:48px;border-radius:50%;background:' + (statusColors[status] || '#999') + '20;display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px">'
        + '<div style="width:16px;height:16px;border-radius:50%;background:' + (statusColors[status] || '#999') + '"></div></div>'
        + '<div class="font-bold">' + (status === 'healthy' ? 'Tất cả bình thường' : alerts.length + ' cảnh báo') + '</div>'
        + '<div class="text-xs text-muted">' + (data.checked_at || '') + '</div></div>';

      if (alerts.length) {
        for (var i = 0; i < alerts.length; i++) {
          var a = alerts[i];
          var icon = a.type === 'danger' ? '🔴' : '🟡';
          html += '<div class="card mb-2" style="padding:10px;border-left:3px solid ' + (a.type === 'danger' ? 'var(--danger)' : 'var(--warning)') + '">'
            + '<div class="flex items-center gap-2"><span>' + icon + '</span><span class="text-sm font-medium">' + SS.utils.esc(a.message || '') + '</span></div>'
            + '<div class="text-xs text-muted mt-1">' + SS.utils.esc(a.metric || '') + ': ' + (a.value || '') + '</div></div>';
        }
      } else {
        html += '<div class="text-center text-muted p-3">✅ Không có cảnh báo nào</div>';
      }

      SS.ui.sheet({title: 'Trạng thái hệ thống', html: html});
    });
  }
};
