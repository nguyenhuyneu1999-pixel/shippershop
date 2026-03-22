/**
 * ShipperShop Component — Engagement Compare
 */
window.SS = window.SS || {};

SS.EngagementCompare = {
  comparePosts: function(id1, id2) {
    SS.api.get('/engagement-compare.php?post1=' + id1 + '&post2=' + id2).then(function(d) {
      var data = d.data || {};
      var p1 = data.post1 || {};
      var p2 = data.post2 || {};
      var w = data.winner;
      var html = '<div style="display:grid;grid-template-columns:1fr auto 1fr;gap:12px;text-align:center">';
      // Left
      html += '<div style="' + (w === 1 ? 'border:2px solid var(--success);border-radius:10px;padding:8px' : 'padding:8px') + '">' + (w === 1 ? '<div class="text-xs" style="color:var(--success)">🏆 Winner</div>' : '') + '<div class="font-bold text-sm">#' + p1.id + '</div><div class="text-xs">' + SS.utils.esc(p1.fullname || '') + '</div><div class="font-bold text-lg" style="color:var(--primary)">' + (data.engagement1 || 0) + '</div><div class="text-xs text-muted">❤️' + (p1.likes_count || 0) + ' 💬' + (p1.comments_count || 0) + '</div></div>';
      // VS
      html += '<div style="display:flex;align-items:center;font-size:18px;font-weight:800;color:var(--text-muted)">VS</div>';
      // Right
      html += '<div style="' + (w === 2 ? 'border:2px solid var(--success);border-radius:10px;padding:8px' : 'padding:8px') + '">' + (w === 2 ? '<div class="text-xs" style="color:var(--success)">🏆 Winner</div>' : '') + '<div class="font-bold text-sm">#' + p2.id + '</div><div class="text-xs">' + SS.utils.esc(p2.fullname || '') + '</div><div class="font-bold text-lg" style="color:var(--primary)">' + (data.engagement2 || 0) + '</div><div class="text-xs text-muted">❤️' + (p2.likes_count || 0) + ' 💬' + (p2.comments_count || 0) + '</div></div>';
      html += '</div>';
      if (w === 0) html += '<div class="text-center text-sm mt-2" style="color:var(--warning)">⚖️ Hoa!</div>';
      SS.ui.sheet({title: 'So sanh bai viet', html: html});
    });
  },
  compareUsers: function(uid1, uid2) {
    SS.api.get('/engagement-compare.php?action=users&user1=' + uid1 + '&user2=' + uid2).then(function(d) {
      var data = d.data || {};
      var u1 = data.user1 || {};
      var u2 = data.user2 || {};
      var uu1 = u1.user || {};
      var uu2 = u2.user || {};
      var w = data.winner;
      var html = '<div style="display:grid;grid-template-columns:1fr auto 1fr;gap:8px;text-align:center">';
      html += '<div><img src="' + (uu1.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-md" loading="lazy"><div class="font-bold text-sm mt-1">' + SS.utils.esc(uu1.fullname || '') + '</div>' + (w === 1 ? '<div class="text-xs" style="color:var(--success)">🏆</div>' : '') + '</div>';
      html += '<div style="display:flex;align-items:center;font-weight:800;color:var(--text-muted)">VS</div>';
      html += '<div><img src="' + (uu2.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-md" loading="lazy"><div class="font-bold text-sm mt-1">' + SS.utils.esc(uu2.fullname || '') + '</div>' + (w === 2 ? '<div class="text-xs" style="color:var(--success)">🏆</div>' : '') + '</div></div>';
      var metrics = ['posts_30d', 'likes_30d', 'followers'];
      var labels = {posts_30d: 'Bai/30d', likes_30d: 'Likes/30d', followers: 'Followers'};
      for (var i = 0; i < metrics.length; i++) {
        var k = metrics[i];
        html += '<div class="flex justify-between p-1 text-sm" style="border-bottom:1px solid var(--border-light)"><span class="font-bold">' + (u1[k] || 0) + '</span><span class="text-muted">' + labels[k] + '</span><span class="font-bold">' + (u2[k] || 0) + '</span></div>';
      }
      SS.ui.sheet({title: 'So sanh Shipper', html: html});
    });
  }
};
