/**
 * ShipperShop Component — Personal Dashboard
 */
window.SS = window.SS || {};

SS.UserDashboard = {
  show: function() {
    SS.api.get('/user-dashboard.php').then(function(d) {
      var data = d.data || {};
      var u = data.user || {};
      var today = data.today || {};
      var html = '<div class="text-center mb-3"><img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-lg" loading="lazy"><div class="font-bold mt-1">' + SS.utils.esc(u.fullname || '') + '</div><div class="text-xs text-muted">Rank #' + (data.rank || '?') + ' · Lv.' + (data.level || 1) + ' · 🔥' + (data.streak || 0) + '</div></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;text-align:center;font-size:11px">';
      var stats = [{v: today.posts, l: 'Hom nay', c: 'var(--primary)'}, {v: today.likes_received, l: 'Likes', c: 'var(--success)'}, {v: data.week_posts, l: 'Tuan', c: ''}, {v: data.unread_messages, l: 'Tin moi', c: 'var(--warning)'}];
      for (var i = 0; i < stats.length; i++) html += '<div class="card" style="padding:8px"><div class="font-bold"' + (stats[i].c ? ' style="color:' + stats[i].c + '"' : '') + '>' + (stats[i].v || 0) + '</div><div class="text-muted">' + stats[i].l + '</div></div>';
      html += '</div>';
      html += '<div class="flex gap-2 mt-3 justify-center"><button class="btn btn-ghost btn-sm" onclick="SS.DailyReport.show()">📊 Bao cao</button><button class="btn btn-ghost btn-sm" onclick="SS.WeeklyChallenge.show()">🏆 Thach thuc</button><button class="btn btn-ghost btn-sm" onclick="SS.UserGoals.show()">🎯 Muc tieu</button></div>';
      SS.ui.sheet({title: 'Dashboard', html: html});
    });
  }
};
