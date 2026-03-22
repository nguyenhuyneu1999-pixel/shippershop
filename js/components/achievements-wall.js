/**
 * ShipperShop Component — Achievements Wall
 * XP leaderboard, streaks, user XP breakdown with level system
 */
window.SS = window.SS || {};

SS.AchievementsWall = {
  show: function() {
    SS.api.get('/achievements-wall.php?action=leaderboard').then(function(d) {
      var users = d.data || [];
      var html = '<div class="flex gap-2 mb-3"><div class="chip chip-active" onclick="SS.AchievementsWall.show()">XP</div><div class="chip" onclick="SS.AchievementsWall.showStreaks()">Streaks</div><div class="chip" onclick="SS.AchievementsWall.showRecent()">Gan day</div></div>';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        var medal = i === 0 ? '🥇' : (i === 1 ? '🥈' : (i === 2 ? '🥉' : '#' + (i + 1)));
        var level = Math.max(1, Math.floor(u.total_xp / 100) + 1);
        html += '<div class="flex items-center gap-3 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<span style="width:28px;text-align:center;font-weight:700">' + medal + '</span>'
          + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-sm" loading="lazy">'
          + '<div class="flex-1"><div class="font-medium text-sm">' + SS.utils.esc(u.fullname) + '</div>'
          + '<div class="text-xs text-muted">Lv.' + level + ' · ' + SS.utils.esc(u.shipping_company || '') + '</div></div>'
          + '<div class="font-bold" style="color:var(--primary)">' + SS.utils.fN(u.total_xp) + ' XP</div></div>';
      }
      if (!users.length) html += '<div class="empty-state p-4"><div class="empty-text">Chua co du lieu</div></div>';
      SS.ui.sheet({title: 'Bang xep hang XP', html: html});
    });
  },

  showStreaks: function() {
    SS.ui.closeSheet();
    SS.api.get('/achievements-wall.php?action=streaks').then(function(d) {
      var users = d.data || [];
      var html = '<div class="flex gap-2 mb-3"><div class="chip" onclick="SS.AchievementsWall.show()">XP</div><div class="chip chip-active">Streaks</div><div class="chip" onclick="SS.AchievementsWall.showRecent()">Gan day</div></div>';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        html += '<div class="flex items-center gap-3 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-sm" loading="lazy">'
          + '<div class="flex-1"><div class="font-medium text-sm">' + SS.utils.esc(u.fullname) + '</div></div>'
          + '<div class="text-right"><div class="font-bold" style="color:var(--warning)">🔥 ' + u.current_streak + '</div>'
          + '<div class="text-xs text-muted">Max: ' + u.longest_streak + '</div></div></div>';
      }
      SS.ui.sheet({title: 'Streak', html: html});
    });
  },

  showRecent: function() {
    SS.ui.closeSheet();
    SS.api.get('/achievements-wall.php?action=recent').then(function(d) {
      var items = d.data || [];
      var html = '<div class="flex gap-2 mb-3"><div class="chip" onclick="SS.AchievementsWall.show()">XP</div><div class="chip" onclick="SS.AchievementsWall.showStreaks()">Streaks</div><div class="chip chip-active">Gan day</div></div>';
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<img src="' + (it.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><span class="text-sm">' + SS.utils.esc(it.fullname) + '</span> <span class="text-xs text-muted">' + SS.utils.esc(it.reason) + '</span></div>'
          + '<div class="font-bold text-sm" style="color:var(--success)">+' + it.xp + '</div></div>';
      }
      SS.ui.sheet({title: 'XP gan day', html: html});
    });
  },

  showUser: function(userId) {
    SS.api.get('/achievements-wall.php?action=user&user_id=' + userId).then(function(d) {
      var data = d.data || {};
      var pct = data.xp_in_level || 0;
      var html = '<div class="text-center mb-3"><div style="font-size:36px;font-weight:800;color:var(--primary)">Lv.' + (data.level || 1) + '</div>'
        + '<div class="text-sm">' + SS.utils.fN(data.total_xp || 0) + ' XP</div>'
        + '<div style="height:8px;background:var(--border-light);border-radius:4px;margin:8px auto;max-width:200px"><div style="width:' + pct + '%;height:100%;background:var(--primary);border-radius:4px"></div></div>'
        + '<div class="text-xs text-muted">' + (data.xp_to_next || 0) + ' XP den level tiep</div></div>';

      // Streak
      var s = data.streak || {};
      html += '<div class="flex gap-3 justify-center mb-3"><div class="card" style="padding:8px 16px;text-align:center"><div>🔥</div><div class="font-bold">' + (s.current || 0) + '</div><div class="text-xs text-muted">Streak</div></div>'
        + '<div class="card" style="padding:8px 16px;text-align:center"><div>🏆</div><div class="font-bold">' + (s.longest || 0) + '</div><div class="text-xs text-muted">Ky luc</div></div></div>';

      // Breakdown
      var bd = data.breakdown || [];
      if (bd.length) {
        html += '<div class="text-sm font-bold mb-2">Chi tiet XP</div>';
        for (var i = 0; i < bd.length; i++) {
          html += '<div class="flex justify-between text-sm p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(bd[i].reason) + ' <span class="text-xs text-muted">x' + bd[i].times + '</span></span><span class="font-bold">+' + bd[i].xp + '</span></div>';
        }
      }
      SS.ui.sheet({title: 'Thanh tuu', html: html});
    });
  }
};
