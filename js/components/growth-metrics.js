/**
 * ShipperShop Component — Growth Metrics (Admin)
 */
window.SS = window.SS || {};

SS.GrowthMetrics = {
  show: function() {
    SS.api.get('/growth-metrics.php').then(function(d) {
      var data = d.data || {};
      var u = data.users || {};
      var p = data.posts || {};
      var e = data.engagement || {};

      var rows = [
        {label: 'Users', icon: '👥', thisW: u.this_week, lastW: u.last_week, wow: u.wow},
        {label: 'Posts', icon: '📝', thisW: p.this_week, lastW: p.last_week, wow: p.wow},
        {label: 'Likes', icon: '❤️', thisW: e.this_week, lastW: e.last_week, wow: e.wow},
      ];

      var html = '';
      for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var wowColor = (r.wow || 0) >= 0 ? 'var(--success)' : 'var(--danger)';
        html += '<div class="card mb-2" style="padding:12px"><div class="flex justify-between items-center">'
          + '<div><div class="text-sm font-bold">' + r.icon + ' ' + r.label + '</div><div class="text-xs text-muted">Tuan nay: ' + (r.thisW || 0) + ' · Tuan truoc: ' + (r.lastW || 0) + '</div></div>'
          + '<div class="font-bold text-lg" style="color:' + wowColor + '">' + ((r.wow || 0) >= 0 ? '+' : '') + (r.wow || 0) + '%</div></div></div>';
      }

      // DAU weekly
      var dau = data.dau_weekly || [];
      if (dau.length) {
        var maxD = Math.max.apply(null, dau.map(function(d) { return d.dau_avg || 0; })) || 1;
        html += '<div class="text-sm font-bold mb-2 mt-3">DAU 4 tuan</div><div style="display:flex;align-items:flex-end;gap:6px;height:50px">';
        for (var j = 0; j < dau.length; j++) {
          var h = Math.max(4, Math.round((dau[j].dau_avg || 0) / maxD * 46));
          html += '<div style="flex:1;text-align:center"><div style="height:' + h + 'px;background:var(--primary);border-radius:4px 4px 0 0"></div><div class="text-xs text-muted mt-1">' + dau[j].week + '</div></div>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: '📈 Tang truong', html: html});
    });
  }
};
