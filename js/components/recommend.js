/**
 * ShipperShop Component — Recommendations
 * "For You" sidebar widget + similar posts under post detail
 * Uses: SS.api, SS.utils
 */
window.SS = window.SS || {};

SS.Recommend = {

  // Render "For You" in sidebar
  renderForYou: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;

    SS.api.get('/recommend.php?action=for_you&limit=5').then(function(d) {
      var posts = (d.data && d.data.posts) || [];
      if (!posts.length) { el.innerHTML = ''; return; }

      var html = '<div class="card"><div class="card-header">Có thể bạn quan tâm</div>';
      for (var i = 0; i < posts.length; i++) {
        var p = posts[i];
        var img = '';
        try { var imgs = JSON.parse(p.images || '[]'); if (imgs.length) img = imgs[0]; } catch(e) {}

        html += '<a href="/post-detail.html?id=' + p.id + '" class="list-item" style="text-decoration:none;color:var(--text);padding:8px 16px">'
          + '<div class="flex-1" style="min-width:0">'
          + '<div class="text-sm truncate" style="font-weight:500">' + SS.utils.esc((p.content || '').substring(0, 60)) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(p.user_name || '') + ' · ' + SS.utils.fN(p.likes_count || 0) + ' thành công</div>'
          + '</div>'
          + (img ? '<img src="' + img + '" style="width:48px;height:48px;border-radius:6px;object-fit:cover;flex-shrink:0" loading="lazy">' : '')
          + '</a>';
      }
      html += '</div>';
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = ''; });
  },

  // Render similar posts (under post detail)
  renderSimilar: function(containerId, postId) {
    var el = document.getElementById(containerId);
    if (!el || !postId) return;

    SS.api.get('/recommend.php?action=similar&post_id=' + postId + '&limit=4').then(function(d) {
      var posts = d.data || [];
      if (!posts.length) { el.innerHTML = ''; return; }

      var html = '<div style="padding:16px"><div class="font-bold text-sm mb-3" style="color:var(--text-muted)">Bài viết liên quan</div>';
      for (var i = 0; i < posts.length; i++) {
        var p = posts[i];
        html += '<a href="/post-detail.html?id=' + p.id + '" class="list-item" style="text-decoration:none;color:var(--text);padding:8px 0">'
          + '<img class="avatar avatar-sm" src="' + (p.user_avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
          + '<div class="flex-1" style="min-width:0">'
          + '<div class="text-sm truncate">' + SS.utils.esc((p.content || '').substring(0, 70)) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(p.user_name || '') + ' · ' + SS.utils.ago(p.created_at) + '</div>'
          + '</div></a>';
      }
      html += '</div>';
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = ''; });
  },

  // Render suggested follows
  renderSuggestions: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/recommend.php?action=suggested_follows&limit=5').then(function(d) {
      var users = d.data || [];
      if (!users.length) { el.innerHTML = ''; return; }

      var html = '<div class="card"><div class="card-header">Gợi ý theo dõi</div>';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        html += '<div class="list-item" style="padding:8px 16px">'
          + '<a href="/user.html?id=' + u.id + '"><img class="avatar avatar-sm" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy"></a>'
          + '<div class="flex-1" style="min-width:0">'
          + '<a href="/user.html?id=' + u.id + '" class="text-sm font-medium truncate" style="text-decoration:none;color:var(--text)">' + SS.utils.esc(u.fullname) + (parseInt(u.is_verified) ? ' <i class="fa-solid fa-circle-check" style="color:#3b82f6;font-size:10px"></i>' : '') + '</a>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(u.shipping_company || '') + (u.mutual_follows > 0 ? ' · ' + u.mutual_follows + ' chung' : '') + '</div>'
          + '</div>'
          + '<button class="btn btn-sm btn-primary" onclick="SS.api.post(\'/social.php?action=follow\',{user_id:' + u.id + '}).then(function(){this.textContent=\'Đã theo dõi\';this.disabled=true}.bind(this))" style="flex-shrink:0">Theo dõi</button>'
          + '</div>';
      }
      html += '</div>';
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = ''; });
  }
};
