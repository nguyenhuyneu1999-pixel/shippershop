window.SS = window.SS || {};
SS.ShipperProfileV2 = {
  show: function(userId) {
    SS.api.get('/shipper-profile-v2.php?user_id=' + userId).then(function(d) {
      var data = d.data || {};
      if (!data) { SS.ui.toast('User not found', 'error'); return; }
      var u = data.user || {};
      var s = data.stats || {};
      var html = '<div class="text-center mb-3"><img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-lg" loading="lazy"><div class="font-bold mt-1">' + SS.utils.esc(u.fullname || '') + '</div><div class="text-xs text-muted">' + SS.utils.esc(u.shipping_company || '') + ' · Lv.' + (s.level || 1) + ' · ' + (s.xp || 0) + ' XP</div></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px;text-align:center;font-size:11px;margin-bottom:12px">';
      var stats = [{v: s.followers, l: 'Followers'}, {v: s.posts_30d, l: '30d'}, {v: s.streak, l: 'Streak'}, {v: s.active_days_month, l: 'Active'}];
      for (var i = 0; i < stats.length; i++) html += '<div class="card" style="padding:6px"><div class="font-bold">' + (stats[i].v || 0) + '</div><div class="text-muted">' + stats[i].l + '</div></div>';
      html += '</div>';
      var areas = data.areas || [];
      if (areas.length) {
        html += '<div class="text-xs font-bold mb-1">Khu vuc</div><div class="flex gap-1 flex-wrap">';
        for (var a = 0; a < areas.length; a++) html += '<span class="chip" style="font-size:10px">📍' + SS.utils.esc(areas[a].province) + ' (' + areas[a].c + ')</span>';
        html += '</div>';
      }
      SS.ui.sheet({title: 'Shipper Profile', html: html});
    });
  }
};
