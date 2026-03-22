/**
 * ShipperShop Component — Revenue Forecast (Admin)
 */
window.SS = window.SS || {};

SS.RevenueForecast = {
  show: function() {
    SS.api.get('/revenue-forecast.php').then(function(d) {
      var data = d.data || {};
      var subs = data.subscriptions || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:12px;background:linear-gradient(135deg,var(--primary),#6d28d9);color:#fff;border-radius:10px"><div class="text-xs" style="opacity:.8">Thang nay</div><div class="font-bold text-lg">' + SS.utils.formatMoney(data.current_month || 0) + 'd</div></div>'
        + '<div class="card" style="padding:12px"><div class="text-xs text-muted">Du kien</div><div class="font-bold text-lg" style="color:var(--success)">' + SS.utils.formatMoney(data.projected_month || 0) + 'd</div></div></div>';
      html += '<div class="card mb-3" style="padding:10px;text-align:center"><span class="text-sm">Tang truong: <span class="font-bold" style="color:' + (data.growth_rate >= 0 ? 'var(--success)' : 'var(--danger)') + '">' + (data.growth_rate >= 0 ? '+' : '') + data.growth_rate + '%</span></span></div>';
      // Forecast
      var forecast = data.forecast || [];
      if (forecast.length) {
        html += '<div class="text-sm font-bold mb-2">Du bao 3 thang toi</div>';
        for (var i = 0; i < forecast.length; i++) {
          var f = forecast[i];
          html += '<div class="flex justify-between p-2" style="border-bottom:1px solid var(--border-light)"><span class="text-sm">' + f.month + '</span><span class="font-bold" style="color:var(--primary)">' + SS.utils.formatMoney(f.projected) + 'd</span><span class="text-xs text-muted">' + f.confidence + '% conf</span></div>';
        }
      }
      // Subs
      html += '<div class="card mt-3" style="padding:10px"><div class="text-sm font-bold">Subscription</div><div class="text-xs text-muted">' + subs.active + ' active · TB ' + SS.utils.formatMoney(subs.avg_price || 0) + 'd · ' + SS.utils.formatMoney(subs.monthly_revenue || 0) + 'd/thang</div></div>';
      SS.ui.sheet({title: '📈 Du bao doanh thu', html: html});
    });
  }
};
