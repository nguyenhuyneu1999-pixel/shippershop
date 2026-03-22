/**
 * ShipperShop Component — Platform Alerts (Admin)
 */
window.SS = window.SS || {};

SS.PlatformAlerts = {
  show: function() {
    SS.api.get('/platform-alerts.php').then(function(d) {
      var alerts = (d.data || {}).alerts || [];
      var html = '';
      for (var i = 0; i < alerts.length; i++) {
        var a = alerts[i];
        var colors = {success: 'var(--success)', warning: 'var(--warning)', danger: 'var(--danger)'};
        var icons = {success: '✅', warning: '⚠️', danger: '🚨'};
        html += '<div class="card mb-2" style="padding:12px;border-left:4px solid ' + (colors[a.type] || '#999') + '">'
          + '<div class="font-bold text-sm">' + (icons[a.type] || '') + ' ' + SS.utils.esc(a.title) + '</div>'
          + '<div class="text-xs text-muted mt-1">' + SS.utils.esc(a.desc) + '</div>'
          + '<div class="text-xs mt-1"><span class="chip" style="font-size:10px">' + SS.utils.esc(a.severity || '') + '</span></div></div>';
      }
      html += '<div class="text-xs text-muted text-center mt-2">Kiem tra: ' + SS.utils.ago((d.data || {}).checked_at) + '</div>';
      SS.ui.sheet({title: '🔔 Canh bao he thong (' + alerts.length + ')', html: html});
    });
  }
};
