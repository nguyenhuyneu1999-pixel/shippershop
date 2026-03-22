window.SS = window.SS || {};
SS.NotifAnalytics = {
  show: function() {
    SS.api.get('/notif-analytics.php').then(function(d) {
      var data = d.data || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:16px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (data.total_sent || 0) + '</div><div class="text-muted">Sent</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--success)">' + (data.open_rate || 0) + '%</div><div class="text-muted">Open</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (data.push_subs || 0) + '</div><div class="text-muted">Push</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (data.avg_per_user || 0) + '</div><div class="text-muted">TB/user</div></div></div>';
      // By type
      var types = data.by_type || [];
      if (types.length) {
        var maxT = parseInt(types[0].c) || 1;
        html += '<div class="text-sm font-bold mb-2">Theo loai</div>';
        for (var i = 0; i < types.length; i++) {
          var t = types[i];
          var w = Math.max(8, Math.round(parseInt(t.c) / maxT * 100));
          html += '<div class="mb-1"><div class="flex justify-between text-xs"><span>' + SS.utils.esc(t.type) + '</span><span class="font-bold">' + t.c + '</span></div>'
            + '<div style="height:6px;background:var(--border-light);border-radius:3px"><div style="width:' + w + '%;height:100%;background:var(--primary);border-radius:3px"></div></div></div>';
        }
      }
      // Daily
      var daily = data.daily || [];
      if (daily.length > 1) {
        var maxD = Math.max.apply(null, daily.map(function(d) { return parseInt(d.sent) || 0; })) || 1;
        html += '<div class="text-sm font-bold mb-1 mt-3">7 ngay</div><div style="display:flex;align-items:flex-end;gap:3px;height:40px">';
        for (var j = 0; j < daily.length; j++) {
          var h = Math.max(4, Math.round((parseInt(daily[j].sent) || 0) / maxD * 36));
          html += '<div style="flex:1;height:' + h + 'px;background:var(--primary);border-radius:3px 3px 0 0"></div>';
        }
        html += '</div>';
      }
      SS.ui.sheet({title: '🔔 Notification Analytics', html: html});
    });
  }
};
