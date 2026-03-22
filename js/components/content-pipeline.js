/**
 * ShipperShop Component — Content Pipeline (Admin)
 */
window.SS = window.SS || {};

SS.ContentPipeline = {
  show: function() {
    SS.api.get('/content-pipeline.php').then(function(d) {
      var data = d.data || {};
      var stages = data.stages || [];
      var live = data.live || {};

      // Pipeline stages
      var html = '<div style="display:flex;gap:4px;margin-bottom:16px">';
      for (var i = 0; i < stages.length; i++) {
        var s = stages[i];
        html += '<div style="flex:1;text-align:center;padding:10px;border-radius:8px;background:' + s.color + '15;border:1px solid ' + s.color + '30"><div style="font-size:18px">' + s.icon + '</div><div class="font-bold" style="color:' + s.color + '">' + s.count + '</div><div class="text-xs">' + s.stage + '</div></div>';
        if (i < stages.length - 1) html += '<div style="display:flex;align-items:center;color:var(--text-muted)">→</div>';
      }
      html += '</div>';

      // Live stats
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center;margin-bottom:16px">'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--success)">' + (live.active || 0) + '</div><div class="text-xs text-muted">Active</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--danger)">' + (live.reported || 0) + '</div><div class="text-xs text-muted">Reported</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (data.avg_daily || 0) + '</div><div class="text-xs text-muted">TB/ngay</div></div></div>';

      // Daily trend mini chart
      var daily = data.daily_trend || [];
      if (daily.length) {
        var maxC = Math.max.apply(null, daily.map(function(d) { return parseInt(d.c) || 0; })) || 1;
        html += '<div class="text-sm font-bold mb-1">7 ngay</div><div style="display:flex;align-items:flex-end;gap:3px;height:40px">';
        for (var j = 0; j < daily.length; j++) {
          var h = Math.max(4, Math.round(parseInt(daily[j].c) / maxC * 36));
          html += '<div style="flex:1;height:' + h + 'px;background:var(--primary);border-radius:3px 3px 0 0" title="' + daily[j].day + ': ' + daily[j].c + '"></div>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Content Pipeline', html: html});
    });
  }
};
