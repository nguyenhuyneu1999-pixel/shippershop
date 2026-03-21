/**
 * ShipperShop Component — Follow Suggestions (Smart)
 * Shows personalized follow suggestions with reason labels
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.FollowSuggest = {

  render: function(containerId, opts) {
    opts = opts || {};
    var el = document.getElementById(containerId);
    if (!el || !SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/follow-suggest.php?limit=' + (opts.limit || 6)).then(function(d) {
      var users = d.data || [];
      if (!users.length) { el.innerHTML = ''; return; }

      var html = '<div class="card"><div class="card-header">Gợi ý theo dõi</div>';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        var verified = parseInt(u.is_verified) ? ' <i class="fa-solid fa-circle-check" style="color:#3b82f6;font-size:10px"></i>' : '';
        var sourceColors = {friends_of_friends:'var(--primary)',same_company:'var(--success)',active_user:'var(--warning)',new_user:'var(--info)'};
        var dotColor = sourceColors[u.source] || 'var(--text-muted)';

        html += '<div class="list-item" id="fs-' + u.id + '" style="padding:10px 16px">'
          + '<a href="/user.html?id=' + u.id + '" data-user-id="' + u.id + '"><img class="avatar avatar-sm" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy"></a>'
          + '<div class="flex-1" style="min-width:0">'
          + '<a href="/user.html?id=' + u.id + '" class="text-sm font-medium truncate" style="display:block;text-decoration:none;color:var(--text)">' + SS.utils.esc(u.fullname) + verified + '</a>'
          + '<div class="text-xs" style="color:' + dotColor + '">● ' + SS.utils.esc(u.reason || '') + '</div>'
          + '</div>'
          + '<div class="flex gap-1" style="flex-shrink:0">'
          + '<button class="btn btn-primary btn-xs" onclick="SS.FollowSuggest._follow(' + u.id + ')">Theo dõi</button>'
          + '<button class="btn btn-ghost btn-xs" onclick="SS.FollowSuggest._dismiss(' + u.id + ')" title="Ẩn" style="padding:4px 6px">✕</button>'
          + '</div></div>';
      }
      html += '</div>';
      el.innerHTML = html;

      // Attach profile card hover
      if (SS.ProfileCard) SS.ProfileCard.attachAll(el);
    }).catch(function() { el.innerHTML = ''; });
  },

  _follow: function(userId) {
    SS.api.post('/social.php?action=follow', {user_id: userId}).then(function() {
      var row = document.getElementById('fs-' + userId);
      if (row) {
        var btn = row.querySelector('.btn-primary');
        if (btn) { btn.textContent = 'Đã theo dõi'; btn.className = 'btn btn-outline btn-xs'; btn.disabled = true; }
      }
      if (SS.NotifSound) SS.NotifSound.play('success');
    });
  },

  _dismiss: function(userId) {
    SS.api.post('/follow-suggest.php?action=dismiss', {user_id: userId});
    var row = document.getElementById('fs-' + userId);
    if (row) { row.style.opacity = '0'; row.style.transition = 'opacity .3s'; setTimeout(function() { row.remove(); }, 300); }
  }
};
