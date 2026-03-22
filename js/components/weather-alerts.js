/**
 * ShipperShop Component — Weather Alerts
 */
window.SS = window.SS || {};

SS.WeatherAlerts = {
  show: function(province) {
    var url = '/weather-alerts.php' + (province ? '?province=' + encodeURIComponent(province) : '');
    SS.api.get(url).then(function(d) {
      var alerts = (d.data || {}).alerts || [];
      var html = '';
      if (!alerts.length) { html = '<div class="text-center text-muted p-3">Khong co canh bao</div>'; }
      for (var i = 0; i < alerts.length; i++) {
        var a = alerts[i];
        var sevColors = {low: 'var(--success)', medium: 'var(--warning)', high: 'var(--danger)', critical: '#dc2626'};
        html += '<div class="card mb-2" style="padding:12px;border-left:4px solid ' + (sevColors[a.severity] || '#999') + '">'
          + '<div class="flex justify-between"><span class="font-bold text-sm">' + a.icon + ' ' + SS.utils.esc(a.province) + '</span><span class="text-xs">' + a.temp + '°C</span></div>'
          + '<div class="text-sm">' + SS.utils.esc(a.condition) + ' · 💧' + a.humidity + '% · 💨' + a.wind + 'km/h</div>'
          + '<div class="text-xs mt-1" style="color:' + (sevColors[a.severity] || '') + '">' + SS.utils.esc(a.alert) + '</div></div>';
      }
      SS.ui.sheet({title: '🌤️ Thoi tiet giao hang', html: html});
    });
  },
  showSafety: function() {
    SS.api.get('/weather-alerts.php?action=safety').then(function(d) {
      var scores = d.data || [];
      var html = '';
      for (var i = 0; i < scores.length; i++) {
        var s = scores[i];
        var color = s.score >= 75 ? 'var(--success)' : (s.score >= 50 ? 'var(--warning)' : 'var(--danger)');
        html += '<div class="flex justify-between items-center p-2" style="border-bottom:1px solid var(--border-light)"><span class="text-sm">' + s.icon + ' ' + SS.utils.esc(s.province) + '</span>'
          + '<div class="text-right"><span class="font-bold" style="color:' + color + '">' + s.score + '/100</span><div class="text-xs text-muted">' + SS.utils.esc(s.condition) + '</div></div></div>';
      }
      SS.ui.sheet({title: 'An toan giao hang', html: html});
    });
  }
};
