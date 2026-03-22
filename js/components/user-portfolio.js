/**
 * ShipperShop Component — User Portfolio
 * Public portfolio: stats, best posts, achievements
 */
window.SS = window.SS || {};

SS.UserPortfolio = {
  show: function(userId) {
    SS.api.get('/user-portfolio.php?user_id=' + userId).then(function(d) {
      var data = d.data || {};
      var u = data.user || {};
      var s = data.stats || {};

      // Header
      var html = '<div class="text-center mb-3">'
        + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid var(--primary)" loading="lazy">'
        + '<div class="font-bold text-lg mt-2">' + SS.utils.esc(u.fullname || '') + (u.verified ? ' <span style="color:var(--primary)">✓</span>' : '') + '</div>'
        + '<div class="text-sm text-muted">' + SS.utils.esc(u.company || '') + ' · ' + (u.days_active || 0) + ' ngay</div>'
        + (u.bio ? '<div class="text-sm mt-1">' + SS.utils.esc(u.bio) + '</div>' : '') + '</div>';

      // Stats grid
      html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px;margin-bottom:16px;text-align:center;font-size:12px">';
      var stats = [
        {v: s.posts, l: 'Bai'},
        {v: s.deliveries, l: 'Don giao'},
        {v: s.followers, l: 'Follower'},
        {v: s.likes, l: 'Likes'},
        {v: s.xp, l: 'XP'},
        {v: 'Lv.' + (s.level || 1), l: 'Level'},
        {v: s.badges, l: 'Huy hieu'},
        {v: s.groups, l: 'Nhom'},
      ];
      for (var i = 0; i < stats.length; i++) {
        html += '<div style="padding:6px"><div class="font-bold" style="color:var(--primary)">' + (typeof stats[i].v === 'number' ? SS.utils.fN(stats[i].v) : stats[i].v) + '</div><div class="text-muted">' + stats[i].l + '</div></div>';
      }
      html += '</div>';

      // Best posts
      var best = data.best_posts || [];
      if (best.length) {
        html += '<div class="text-sm font-bold mb-2">Bai viet noi bat</div>';
        for (var j = 0; j < best.length; j++) {
          var p = best[j];
          html += '<div class="card mb-2" style="padding:8px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'">'
            + '<div class="text-sm">' + SS.utils.esc((p.content || '').substring(0, 80)) + '</div>'
            + '<div class="text-xs text-muted mt-1">❤️ ' + (p.likes_count || 0) + ' 💬 ' + (p.comments_count || 0) + '</div></div>';
        }
      }

      // Recent 30d
      var r = data.recent_30d || {};
      html += '<div class="text-xs text-muted text-center mt-2">30 ngay gan day: ' + (r.posts || 0) + ' bai, ' + (r.comments || 0) + ' binh luan</div>';

      SS.ui.sheet({title: 'Portfolio', html: html});
    });
  }
};
