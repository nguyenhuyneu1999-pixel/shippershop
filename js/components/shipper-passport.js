window.SS = window.SS || {};
SS.ShipperPassport = {
  show: function(userId) {
    SS.api.get('/shipper-passport.php' + (userId ? '?user_id=' + userId : '')).then(function(d) {
      var data = d.data || {};
      var user = data.user || {};
      var stats = data.stats || {};
      var rep = data.reputation || {};
      var streak = data.streak || {};
      var xp = data.xp || {};
      // Passport card
      var html = '<div class="card mb-3" style="padding:16px;background:linear-gradient(135deg,#7c3aed15,#7c3aed05);border:2px solid var(--primary);border-radius:12px">'
        + '<div class="flex items-center gap-3 mb-2"><img src="' + (user.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-lg" style="border:3px solid var(--primary)" loading="lazy">'
        + '<div><div class="font-bold text-lg">' + SS.utils.esc(user.fullname || '') + '</div><div class="text-xs text-muted">' + SS.utils.esc(user.company || '') + ' · ' + (user.member_days || 0) + ' ngay</div>'
        + '<div class="text-xs" style="color:var(--primary)">' + (rep.icon || '') + ' ' + SS.utils.esc(rep.tier || '') + ' · ' + (rep.score || 0) + ' pts</div></div></div>'
        + '<div class="text-xs text-muted text-right">🪪 ' + SS.utils.esc(data.passport_id || '') + '</div></div>';
      // Stats grid
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:12px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (stats.posts || 0) + '</div><div class="text-muted">Bai</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--danger)">' + (stats.likes || 0) + '</div><div class="text-muted">Likes</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (stats.followers || 0) + '</div><div class="text-muted">Followers</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">🔥' + (streak.current || 0) + '</div><div class="text-muted">Streak</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">Lv.' + (xp.level || 1) + '</div><div class="text-muted">' + (xp.xp || 0) + ' XP</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (stats.engagement_rate || 0) + '</div><div class="text-muted">Eng/bai</div></div></div>';
      // Badges
      var badges = data.badges || [];
      if (badges.length) {
        html += '<div class="text-sm font-bold mb-1">🏅 Huy hieu</div><div class="flex gap-2 flex-wrap mb-2">';
        for (var i = 0; i < badges.length; i++) html += '<span class="chip" style="font-size:10px">🏅 #' + badges[i].badge_id + '</span>';
        html += '</div>';
      }
      // Subscription
      var sub = data.subscription;
      if (sub) html += '<div class="text-xs" style="color:var(--primary)">⭐ ' + SS.utils.esc(sub.plan || '') + ' (het han: ' + SS.utils.esc(sub.expires || '') + ')</div>';
      SS.ui.sheet({title: '🪪 Shipper Passport', html: html});
    });
  }
};
