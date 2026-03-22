window.SS = window.SS || {};
SS.RetentionDashboard = {
  show: function() {
    SS.api.get('/retention-dashboard.php').then(function(d) {
      var data = d.data || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.dau || 0) + '</div><div class="text-xs text-muted">DAU</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.wau || 0) + '</div><div class="text-xs text-muted">WAU</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.mau || 0) + '</div><div class="text-xs text-muted">MAU</div></div></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--success)">' + (data.stickiness || 0) + '%</div><div class="text-xs text-muted">Stickiness</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--danger)">' + (data.churn_rate || 0) + '%</div><div class="text-xs text-muted">Churn</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (data.returning_week || 0) + '</div><div class="text-xs text-muted">Returning</div></div></div>';
      // DAU trend
      var trend = data.dau_trend || [];
      if (trend.length > 1) {
        var maxD = Math.max.apply(null, trend.map(function(t) { return t.dau || 0; })) || 1;
        html += '<div class="text-sm font-bold mb-1">DAU 7 ngay</div><div style="display:flex;align-items:flex-end;gap:3px;height:45px">';
        for (var i = 0; i < trend.length; i++) {
          var h = Math.max(4, Math.round(trend[i].dau / maxD * 40));
          html += '<div style="flex:1;text-align:center"><div style="height:' + h + 'px;background:var(--primary);border-radius:3px 3px 0 0"></div><div class="text-xs text-muted mt-1">' + trend[i].day.substring(8) + '</div></div>';
        }
        html += '</div>';
      }
      SS.ui.sheet({title: '📊 Retention', html: html});
    });
  }
};
