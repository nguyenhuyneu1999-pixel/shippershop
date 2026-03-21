/**
 * ShipperShop Component — User Segments (Admin)
 * View user segments by activity, company, subscription
 */
window.SS = window.SS || {};

SS.UserSegments = {
  show: function() {
    SS.api.get('/user-segments.php').then(function(d) {
      var data = d.data || {};
      var act = data.activity || {};

      // Activity funnel
      var html = '<div class="text-sm font-bold mb-2">Phan loai hoat dong</div>'
        + '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px">'
        + '<div class="card" style="padding:10px;text-align:center"><div class="font-bold text-lg" style="color:var(--primary)">' + (act.total || 0) + '</div><div class="text-xs text-muted">Tong</div></div>'
        + '<div class="card" style="padding:10px;text-align:center"><div class="font-bold text-lg" style="color:var(--success)">' + (act.active_7d || 0) + '</div><div class="text-xs text-muted">Active 7d</div></div>'
        + '<div class="card" style="padding:10px;text-align:center"><div class="font-bold text-lg">' + (act.active_30d || 0) + '</div><div class="text-xs text-muted">Active 30d</div></div>'
        + '<div class="card" style="padding:10px;text-align:center"><div class="font-bold text-lg" style="color:var(--danger)">' + (act.dormant || 0) + '</div><div class="text-xs text-muted">Dormant 90d+</div></div></div>';

      // By company
      var companies = data.by_company || [];
      if (companies.length) {
        html += '<div class="text-sm font-bold mb-2">Theo hang van chuyen</div>';
        for (var i = 0; i < Math.min(companies.length, 8); i++) {
          var c = companies[i];
          var w = Math.round(c.users / (companies[0].users || 1) * 100);
          html += '<div class="flex items-center gap-2 mb-1"><span class="text-xs" style="width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + SS.utils.esc(c.shipping_company) + '</span>'
            + '<div style="flex:1;height:14px;background:var(--border-light);border-radius:4px;overflow:hidden"><div style="width:' + w + '%;height:100%;background:var(--primary);border-radius:4px"></div></div>'
            + '<span class="text-xs font-bold" style="width:30px;text-align:right">' + c.users + '</span></div>';
        }
      }

      // Power users
      var power = data.power_users || [];
      if (power.length) {
        html += '<div class="text-sm font-bold mt-3 mb-2">Power Users</div>';
        for (var j = 0; j < Math.min(power.length, 5); j++) {
          var p = power[j];
          html += '<div class="flex items-center gap-2 p-1"><span class="text-xs font-bold" style="width:20px">#' + (j + 1) + '</span>'
            + '<img src="' + (p.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
            + '<span class="text-sm flex-1">' + SS.utils.esc(p.fullname) + '</span>'
            + '<span class="text-xs text-muted">' + p.total_posts + ' bai</span></div>';
        }
      }

      SS.ui.sheet({title: 'Phan khuc nguoi dung', html: html});
    });
  }
};
