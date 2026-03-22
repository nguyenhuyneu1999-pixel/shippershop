/**
 * ShipperShop Component — Reactions Analytics
 * Platform-wide reaction trends and stats
 */
window.SS = window.SS || {};

SS.ReactionsAnalytics = {
  show: function(days) {
    days = days || 30;
    SS.api.get('/reactions-analytics.php?days=' + days).then(function(d) {
      var data = d.data || {};
      var html = '<div class="flex gap-2 mb-3">';
      [7, 30, 90].forEach(function(dd) {
        html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.ReactionsAnalytics.show(' + dd + ')" style="cursor:pointer">' + dd + 'd</div>';
      });
      html += '</div>';

      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:12px"><div style="font-size:24px">❤️</div><div class="font-bold text-lg" style="color:var(--primary)">' + SS.utils.fN(data.total_likes || 0) + '</div><div class="text-xs text-muted">Tong likes</div></div>'
        + '<div class="card" style="padding:12px"><div style="font-size:24px">📊</div><div class="font-bold text-lg">' + (data.avg_per_post || 0) + '</div><div class="text-xs text-muted">TB/bai</div></div></div>';

      // Top posts
      var top = data.top_posts || [];
      if (top.length) {
        html += '<div class="text-sm font-bold mb-2">Bai duoc thich nhieu nhat</div>';
        for (var i = 0; i < Math.min(top.length, 3); i++) {
          var p = top[i];
          html += '<div class="card mb-2" style="padding:8px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'">'
            + '<div class="flex items-center gap-2"><img src="' + (p.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy"><span class="text-xs">' + SS.utils.esc(p.fullname) + '</span></div>'
            + '<div class="text-sm mt-1">' + SS.utils.esc((p.content || '').substring(0, 60)) + '</div>'
            + '<div class="text-xs font-bold mt-1" style="color:var(--primary)">❤️ ' + p.likes_count + '</div></div>';
        }
      }

      // Top likers
      var likers = data.top_likers || [];
      if (likers.length) {
        html += '<div class="text-sm font-bold mt-3 mb-2">Nguoi thich nhieu nhat</div>';
        for (var j = 0; j < Math.min(likers.length, 5); j++) {
          var l = likers[j];
          html += '<div class="flex items-center gap-2 p-1"><img src="' + (l.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy"><span class="text-sm flex-1">' + SS.utils.esc(l.fullname) + '</span><span class="text-xs font-bold" style="color:var(--primary)">❤️ ' + l.like_count + '</span></div>';
        }
      }

      SS.ui.sheet({title: 'Phan tich tuong tac', html: html});
    });
  }
};
