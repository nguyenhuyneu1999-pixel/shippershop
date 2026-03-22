window.SS = window.SS || {};
SS.DeliveryAnalytics = {
  show: function(days) {
    days = days || 30;
    SS.api.get('/delivery-analytics.php?days=' + days).then(function(d) {
      var data = d.data || {};
      var html = '<div class="flex gap-2 mb-3">';
      [7, 30, 90].forEach(function(dd) { html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.DeliveryAnalytics.show(' + dd + ')" style="cursor:pointer">' + dd + 'd</div>'; });
      html += '</div>';
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.total_posts || 0) + '</div><div class="text-xs text-muted">Don</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--danger)">❤️ ' + (data.total_likes || 0) + '</div><div class="text-xs text-muted">Likes</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.engagement_rate || 0) + '</div><div class="text-xs text-muted">Eng/don</div></div></div>';
      // Streak + peak
      var streak = data.streak || {};
      html += '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:8px;flex:1"><div class="font-bold">🔥 ' + (streak.current || 0) + '</div><div class="text-xs text-muted">Streak</div></div>'
        + '<div class="card" style="padding:8px;flex:1"><div class="font-bold">🏆 ' + (streak.longest || 0) + '</div><div class="text-xs text-muted">Max</div></div>'
        + (data.peak_hour !== null ? '<div class="card" style="padding:8px;flex:1"><div class="font-bold">🕐 ' + data.peak_hour + 'h</div><div class="text-xs text-muted">Peak</div></div>' : '') + '</div>';
      // Daily chart
      var daily = data.daily || [];
      if (daily.length > 1) {
        var maxP = Math.max.apply(null, daily.map(function(d) { return parseInt(d.posts) || 0; })) || 1;
        html += '<div class="text-sm font-bold mb-1">Xu huong</div><div style="display:flex;align-items:flex-end;gap:2px;height:45px">';
        for (var i = 0; i < daily.length; i++) {
          var h = Math.max(4, Math.round((parseInt(daily[i].posts) || 0) / maxP * 40));
          html += '<div style="flex:1;height:' + h + 'px;background:var(--primary);border-radius:3px 3px 0 0" title="' + daily[i].day + ': ' + daily[i].posts + '"></div>';
        }
        html += '</div>';
      }
      // Provinces
      var prov = data.by_province || [];
      if (prov.length) {
        html += '<div class="text-sm font-bold mb-1 mt-3">Khu vuc</div>';
        for (var j = 0; j < prov.length; j++) html += '<div class="flex justify-between text-xs p-1" style="border-bottom:1px solid var(--border-light)"><span>📍 ' + SS.utils.esc(prov[j].province) + '</span><span class="font-bold">' + prov[j].posts + '</span></div>';
      }
      SS.ui.sheet({title: '📊 Analytics (' + days + 'd)', html: html});
    });
  }
};
