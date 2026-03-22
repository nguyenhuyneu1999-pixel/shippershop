/**
 * ShipperShop Component — User Connections
 * Network visualization: followers, following, mutuals
 */
window.SS = window.SS || {};

SS.UserConnections = {
  show: function(userId) {
    SS.api.get('/user-connections.php?user_id=' + userId).then(function(d) {
      var data = d.data || {};

      var html = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.followers || 0) + '</div><div class="text-xs text-muted">Follower</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.following || 0) + '</div><div class="text-xs text-muted">Following</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--success)">' + (data.mutuals || 0) + '</div><div class="text-xs text-muted">Ban chung</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.groups || 0) + '</div><div class="text-xs text-muted">Nhom</div></div></div>';

      // Top followers
      var followers = data.top_followers || [];
      if (followers.length) {
        html += '<div class="text-sm font-bold mb-2">Nguoi theo doi</div>';
        for (var i = 0; i < followers.length; i++) {
          var f = followers[i];
          html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light);cursor:pointer" onclick="window.location.href=\'/user.html?id=' + f.id + '\'">'
            + '<img src="' + (f.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
            + '<div class="flex-1 text-sm">' + SS.utils.esc(f.fullname) + '</div>'
            + '<span class="text-xs text-muted">' + SS.utils.esc(f.shipping_company || '') + '</span></div>';
        }
      }

      SS.ui.sheet({title: 'Mang luoi ket noi', html: html});
    });
  }
};
