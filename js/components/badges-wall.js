/**
 * ShipperShop Component — Badges Wall
 * Public badges catalog + leaderboard + user collection
 */
window.SS = window.SS || {};

SS.BadgesWall = {
  show: function() {
    SS.api.get('/badges-wall.php?action=catalog').then(function(d) {
      var badges = (d.data || {}).badges || [];
      var rarityColors = {common:'#9ca3af',uncommon:'#22c55e',rare:'#3b82f6',epic:'#a855f7',legendary:'#f59e0b'};
      var rarityNames = {common:'Thuong',uncommon:'Khong thuong',rare:'Hiem',epic:'Sieu hiem',legendary:'Huyen thoai'};

      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">';
      for (var i = 0; i < badges.length; i++) {
        var b = badges[i];
        var color = rarityColors[b.rarity] || '#999';
        html += '<div class="card" style="padding:10px;text-align:center;border-top:3px solid ' + color + '">'
          + '<div style="font-size:28px">' + b.icon + '</div>'
          + '<div class="font-bold text-sm mt-1">' + SS.utils.esc(b.name) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(b.desc) + '</div>'
          + '<div class="text-xs mt-1" style="color:' + color + '">' + (rarityNames[b.rarity] || '') + '</div></div>';
      }
      html += '</div>';

      html += '<div class="text-center mt-3"><button class="btn btn-primary btn-sm" onclick="SS.BadgesWall.leaderboard()"><i class="fa-solid fa-trophy"></i> Bang xep hang</button></div>';
      SS.ui.sheet({title: 'Bo suu tap huy hieu (' + badges.length + ')', html: html});
    });
  },

  leaderboard: function() {
    SS.ui.closeSheet();
    SS.api.get('/badges-wall.php?action=leaderboard').then(function(d) {
      var users = d.data || [];
      var html = '';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        var medal = i === 0 ? '🥇' : (i === 1 ? '🥈' : (i === 2 ? '🥉' : '#' + (i + 1)));
        html += '<div class="flex items-center gap-3 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<span style="width:28px;text-align:center;font-weight:700">' + medal + '</span>'
          + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-sm" loading="lazy">'
          + '<div class="flex-1"><div class="font-medium text-sm">' + SS.utils.esc(u.fullname) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(u.shipping_company || '') + '</div></div>'
          + '<div class="font-bold" style="color:var(--primary)">' + u.badge_count + ' 🏅</div></div>';
      }
      if (!users.length) html = '<div class="empty-state p-4"><div class="empty-text">Chua co du lieu</div></div>';
      SS.ui.sheet({title: 'Bang xep hang huy hieu', html: html});
    });
  },

  // User's badge collection
  showUser: function(userId) {
    SS.api.get('/badges-wall.php?action=user&user_id=' + userId).then(function(d) {
      var data = d.data || {};
      var badges = data.badges || [];
      var html = '<div class="text-center mb-3"><div style="font-size:28px;font-weight:800;color:var(--primary)">' + (data.earned_count || 0) + '/' + (data.total || 0) + '</div></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px">';
      for (var i = 0; i < badges.length; i++) {
        var b = badges[i];
        html += '<div style="text-align:center;padding:8px;opacity:' + (b.earned ? '1' : '0.3') + '"><div style="font-size:24px">' + b.icon + '</div><div class="text-xs">' + SS.utils.esc(b.name) + '</div></div>';
      }
      html += '</div>';
      SS.ui.sheet({title: 'Huy hieu', html: html});
    });
  }
};
