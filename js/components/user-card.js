/**
 * ShipperShop Component — User Card
 * Renders user cards for people, suggestions, followers lists
 */
window.SS = window.SS || {};

SS.UserCard = {

  render: function(u, opts) {
    opts = opts || {};
    var uid = SS.store ? SS.store.userId() : 0;
    var isSelf = uid && parseInt(u.id) === uid;
    var shipColor = SS.PostCard && SS.PostCard._shipColors ? (SS.PostCard._shipColors[u.shipping_company] || 'var(--text-muted)') : 'var(--text-muted)';

    var followBtn = '';
    if (!isSelf && !opts.hideFollow) {
      var following = u.i_follow || u.is_following;
      followBtn = '<button class="btn ' + (following ? 'btn-ghost' : 'btn-primary') + ' btn-sm" onclick="SS.UserCard.toggleFollow(' + u.id + ',this)">'
        + (following ? 'Đang theo dõi' : 'Theo dõi') + '</button>';
    }

    return '<div class="list-item" style="padding:12px 16px">'
      + '<a href="/user.html?id=' + u.id + '"><img class="avatar" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy"></a>'
      + '<div class="flex-1" style="min-width:0">'
      + '<a href="/user.html?id=' + u.id + '" class="list-title" style="text-decoration:none;color:var(--text)">' + SS.utils.esc(u.fullname || '') + '</a>'
      + '<div class="list-subtitle">'
      + (u.shipping_company ? '<span style="color:' + shipColor + ';font-weight:600">' + SS.utils.esc(u.shipping_company) + '</span> · ' : '')
      + (u.total_success ? SS.utils.fN(u.total_success) + ' thành công' : (u.followers_count ? SS.utils.fN(u.followers_count) + ' người theo dõi' : ''))
      + (u.is_online ? ' · <span style="color:var(--success)">Đang online</span>' : '')
      + '</div></div>'
      + '<div class="list-action">' + followBtn + '</div>'
      + '</div>';
  },

  renderList: function(users, opts) {
    if (!users || !users.length) return '<div class="empty-state"><div class="empty-text">Không có ai</div></div>';
    var html = '';
    for (var i = 0; i < users.length; i++) {
      html += SS.UserCard.render(users[i], opts);
    }
    return html;
  },

  renderCompact: function(u) {
    return '<a href="/user.html?id=' + u.id + '" class="flex items-center gap-2" style="text-decoration:none;padding:6px 0">'
      + '<img class="avatar avatar-sm" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
      + '<span class="text-sm font-medium" style="color:var(--text)">' + SS.utils.esc(u.fullname || '') + '</span>'
      + (u.is_online ? '<span style="width:8px;height:8px;border-radius:50%;background:var(--success);flex-shrink:0"></span>' : '')
      + '</a>';
  },

  toggleFollow: function(userId, btn) {
    if (!SS.store || !SS.store.isLoggedIn()) {
      window.location.href = '/login.html';
      return;
    }
    btn.disabled = true;
    SS.api.post('/social.php?action=follow', {user_id: userId}).then(function(d) {
      var following = d.data && d.data.following;
      btn.className = 'btn ' + (following ? 'btn-ghost' : 'btn-primary') + ' btn-sm';
      btn.textContent = following ? 'Đang theo dõi' : 'Theo dõi';
      btn.disabled = false;
    }).catch(function() {
      btn.disabled = false;
    });
  },

  skeleton: function(count) {
    count = count || 5;
    var html = '';
    for (var i = 0; i < count; i++) {
      html += '<div class="list-item"><div class="skeleton skeleton-avatar"></div><div class="flex-1"><div class="skeleton skeleton-text" style="width:50%"></div><div class="skeleton skeleton-text" style="width:30%"></div></div></div>';
    }
    return html;
  }
};
