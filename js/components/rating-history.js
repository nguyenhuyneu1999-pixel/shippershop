/**
 * ShipperShop Component — Rating History
 */
window.SS = window.SS || {};

SS.RatingHistory = {
  show: function(userId) {
    SS.api.get('/rating-history.php' + (userId ? '?user_id=' + userId : '')).then(function(d) {
      var data = d.data || {};
      var trend = data.trend || [];
      var catAvgs = data.category_averages || {};
      var dirIcons = {up: '📈', down: '📉', stable: '➡️'};

      var html = '<div class="flex gap-3 mb-3 text-center"><div class="card" style="padding:10px;flex:1"><div class="font-bold text-lg">' + (data.total_reviews || 0) + '</div><div class="text-xs text-muted">Danh gia</div></div>'
        + '<div class="card" style="padding:10px;flex:1"><div class="font-bold text-lg">' + (dirIcons[data.trend_direction] || '➡️') + '</div><div class="text-xs text-muted">' + (data.trend_direction || 'stable') + '</div></div>'
        + '<div class="card" style="padding:10px;flex:1"><div class="font-bold text-sm" style="color:var(--success)">Best: ' + SS.utils.esc(data.best_category || '-') + '</div><div class="text-xs text-muted">Yeu: ' + SS.utils.esc(data.worst_category || '-') + '</div></div></div>';

      // Category bars
      var cats = Object.keys(catAvgs);
      if (cats.length) {
        html += '<div class="text-sm font-bold mb-2">Diem theo hang muc</div>';
        for (var i = 0; i < cats.length; i++) {
          var avg = catAvgs[cats[i]];
          html += '<div class="flex items-center gap-2 mb-1"><span class="text-xs" style="width:80px">' + SS.utils.esc(cats[i]) + '</span>'
            + '<div style="flex:1;height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + (avg / 5 * 100) + '%;height:100%;background:#fbbf24;border-radius:4px"></div></div>'
            + '<span class="text-xs font-bold" style="width:28px">' + avg + '</span></div>';
        }
      }

      // Monthly trend
      if (trend.length > 1) {
        var maxS = Math.max.apply(null, trend.map(function(t) { return t.avg_score || 0; })) || 5;
        html += '<div class="text-sm font-bold mb-2 mt-3">Xu huong thang</div><div style="display:flex;align-items:flex-end;gap:4px;height:50px">';
        for (var j = 0; j < trend.length; j++) {
          var h = Math.max(6, Math.round((trend[j].avg_score || 0) / maxS * 46));
          html += '<div style="flex:1;text-align:center"><div style="height:' + h + 'px;background:#fbbf24;border-radius:3px 3px 0 0"></div><div class="text-xs text-muted mt-1">' + trend[j].month.substring(5) + '</div></div>';
        }
        html += '</div>';
      }
      SS.ui.sheet({title: '⭐ Lich su danh gia', html: html});
    });
  }
};
