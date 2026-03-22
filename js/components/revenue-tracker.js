window.SS = window.SS || {};
SS.RevenueTracker = {
  show: function() {
    SS.api.get('/revenue-tracker.php').then(function(d) {
      var data = d.data || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:12px;background:linear-gradient(135deg,var(--primary)10,transparent)"><div class="font-bold text-lg" style="color:var(--primary)">' + SS.utils.formatMoney(data.total || 0) + 'd</div><div class="text-xs text-muted">Tong doanh thu</div></div>'
        + '<div class="card" style="padding:12px"><div class="font-bold text-lg" style="color:var(--success)">' + SS.utils.formatMoney(data.month || 0) + 'd</div><div class="text-xs text-muted">Thang nay</div></div>'
        + '<div class="card" style="padding:12px"><div class="font-bold">' + SS.utils.formatMoney(data.week || 0) + 'd</div><div class="text-xs text-muted">Tuan nay</div></div>'
        + '<div class="card" style="padding:12px"><div class="font-bold">⭐ ' + (data.subscribers || 0) + '</div><div class="text-xs text-muted">Subscribers</div></div></div>';
      // Monthly trend
      var monthly = data.monthly_trend || [];
      if (monthly.length > 1) {
        var maxR = Math.max.apply(null, monthly.map(function(m) { return parseInt(m.revenue) || 0; })) || 1;
        html += '<div class="text-sm font-bold mb-1">Doanh thu hang thang</div><div style="display:flex;align-items:flex-end;gap:4px;height:50px">';
        for (var i = 0; i < monthly.length; i++) {
          var h = Math.max(6, Math.round((parseInt(monthly[i].revenue) || 0) / maxR * 44));
          html += '<div style="flex:1;text-align:center"><div style="height:' + h + 'px;background:var(--success);border-radius:3px 3px 0 0"></div><div class="text-xs text-muted mt-1">' + monthly[i].month.substring(5) + '</div></div>';
        }
        html += '</div>';
      }
      // By plan
      var plans = data.by_plan || [];
      if (plans.length) {
        html += '<div class="text-sm font-bold mb-2 mt-3">Theo goi</div>';
        for (var j = 0; j < plans.length; j++) html += '<div class="flex justify-between text-sm p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(plans[j].name) + '</span><span class="font-bold">' + plans[j].subs + ' subs</span></div>';
      }
      SS.ui.sheet({title: '💰 Revenue Tracker', html: html});
    });
  }
};
