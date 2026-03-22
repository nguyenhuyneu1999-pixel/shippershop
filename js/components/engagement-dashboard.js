/**
 * ShipperShop Component — Engagement Dashboard (Admin)
 */
window.SS = window.SS || {};

SS.EngagementDashboard = {
  show: function() {
    SS.api.get('/engagement-dashboard.php').then(function(d) {
      var data = d.data || {};
      var today = data.today || {};

      // DAU/WAU/MAU
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:12px;background:linear-gradient(135deg,var(--primary),#6d28d9);color:#fff;border-radius:10px"><div style="font-size:24px;font-weight:800">' + (data.dau || 0) + '</div><div class="text-xs" style="opacity:.8">DAU</div></div>'
        + '<div class="card" style="padding:12px"><div class="font-bold text-lg">' + (data.wau || 0) + '</div><div class="text-xs text-muted">WAU</div></div>'
        + '<div class="card" style="padding:12px"><div class="font-bold text-lg">' + (data.mau || 0) + '</div><div class="text-xs text-muted">MAU</div></div></div>';

      // Stickiness + avg posts
      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--success)">' + (data.stickiness || 0) + '%</div><div class="text-xs text-muted">Stickiness (DAU/MAU)</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold">' + (data.avg_posts_per_user || 0) + '</div><div class="text-xs text-muted">Bai/user/thang</div></div></div>';

      // Today actions
      html += '<div class="text-sm font-bold mb-2">Hom nay</div><div class="flex gap-2 mb-3">';
      html += '<div class="chip">📝 ' + (today.posts || 0) + ' bai</div><div class="chip">💬 ' + (today.comments || 0) + ' cmt</div><div class="chip">❤️ ' + (today.likes || 0) + ' likes</div></div>';

      // DAU trend
      var trend = data.dau_trend || [];
      if (trend.length) {
        var maxD = Math.max.apply(null, trend.map(function(t) { return t.dau || 0; })) || 1;
        html += '<div class="text-sm font-bold mb-2">DAU 7 ngay</div><div style="display:flex;align-items:flex-end;gap:3px;height:50px">';
        for (var i = 0; i < trend.length; i++) {
          var h = Math.max(4, Math.round((trend[i].dau || 0) / maxD * 46));
          html += '<div style="flex:1;height:' + h + 'px;background:var(--primary);border-radius:3px 3px 0 0" title="' + trend[i].date + ': ' + trend[i].dau + '"></div>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Engagement Dashboard', html: html});
    });
  }
};
