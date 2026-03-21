/**
 * ShipperShop Component — Trending Topics
 * Discover what's trending: hot posts, rising users, active areas
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.TrendingTopics = {

  show: function(hours) {
    hours = hours || 24;
    SS.api.get('/trending-topics.php?hours=' + hours).then(function(d) {
      var data = d.data || {};

      var html = '<div class="flex gap-2 mb-3" style="overflow-x:auto">';
      [24,72,168].forEach(function(h) {
        var active = h === hours ? 'chip-active' : '';
        html += '<div class="chip ' + active + '" onclick="SS.TrendingTopics.show(' + h + ')" style="white-space:nowrap;cursor:pointer">' + (h === 24 ? '24h' : (h === 72 ? '3 ngay' : '7 ngay')) + '</div>';
      });
      html += '</div>';

      // Hot posts
      var hot = data.hot_posts || [];
      if (hot.length) {
        html += '<div class="text-sm font-bold mb-2">🔥 Bai viet noi bat</div>';
        for (var i = 0; i < Math.min(hot.length, 5); i++) {
          var p = hot[i];
          html += '<div class="card mb-2" style="padding:10px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'">'
            + '<div class="flex items-center gap-2 mb-1"><img src="' + (p.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy"><span class="text-xs font-medium">' + SS.utils.esc(p.fullname) + '</span></div>'
            + '<div class="text-sm">' + SS.utils.esc((p.content || '').substring(0, 80)) + '</div>'
            + '<div class="text-xs text-muted mt-1">❤️ ' + (p.likes_count || 0) + ' 💬 ' + (p.comments_count || 0) + '</div></div>';
        }
      }

      // Rising users
      var rising = data.rising_users || [];
      if (rising.length) {
        html += '<div class="text-sm font-bold mb-2 mt-3">🌟 Nguoi noi bat</div><div class="flex gap-3" style="overflow-x:auto;padding-bottom:4px">';
        for (var j = 0; j < rising.length; j++) {
          var u = rising[j];
          html += '<div style="text-align:center;min-width:72px;cursor:pointer" onclick="window.location.href=\'/user.html?id=' + u.id + '\'">'
            + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--primary)" loading="lazy">'
            + '<div class="text-xs font-medium" style="margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + SS.utils.esc(u.fullname) + '</div>'
            + '<div class="text-xs" style="color:var(--success)">+' + u.new_followers + '</div></div>';
        }
        html += '</div>';
      }

      // Active provinces
      var provinces = data.active_provinces || [];
      if (provinces.length) {
        html += '<div class="text-sm font-bold mb-2 mt-3">📍 Khu vuc soi dong</div><div class="flex gap-2 flex-wrap">';
        for (var k = 0; k < provinces.length; k++) {
          html += '<div class="chip">' + SS.utils.esc(provinces[k].province) + ' <span class="font-bold">' + provinces[k].posts + '</span></div>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Xu huong (' + hours + 'h)', html: html});
    });
  },

  // Sidebar widget (compact)
  renderWidget: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    SS.api.get('/trending-topics.php?hours=24').then(function(d) {
      var hot = (d.data || {}).hot_posts || [];
      if (!hot.length) return;
      var html = '<div class="sidebar-card"><div class="sidebar-title">🔥 Xu huong hom nay</div>';
      for (var i = 0; i < Math.min(hot.length, 3); i++) {
        var p = hot[i];
        html += '<div class="sidebar-item" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'" style="flex-direction:column;align-items:flex-start;gap:2px">'
          + '<div class="text-xs" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;width:100%">' + SS.utils.esc((p.content || '').substring(0, 50)) + '</div>'
          + '<div class="text-xs text-muted">❤️ ' + (p.likes_count || 0) + ' 💬 ' + (p.comments_count || 0) + '</div></div>';
      }
      html += '<div class="sidebar-item" onclick="SS.TrendingTopics.show(24)" style="color:var(--primary);font-size:12px;justify-content:center;cursor:pointer">Xem tat ca →</div></div>';
      el.innerHTML = html;
    }).catch(function() {});
  }
};
