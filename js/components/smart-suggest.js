/**
 * ShipperShop Component — Smart Follow Suggestions
 * Shows scored follow suggestions with reasons (mutual friends, same company, etc.)
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.SmartSuggest = {

  render: function(containerId, limit) {
    limit = limit || 5;
    var el = document.getElementById(containerId);
    if (!el || !SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/smart-suggest.php?limit=' + limit).then(function(d) {
      var users = d.data || [];
      if (!users.length) { el.innerHTML = ''; return; }

      var html = '<div class="sidebar-card"><div class="sidebar-title"><i class="fa-solid fa-user-plus" style="margin-right:4px"></i> Gợi ý theo dõi</div>';

      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        var reason = (u.reasons && u.reasons.length) ? u.reasons[0] : '';
        html += '<div style="display:flex;align-items:center;gap:8px;padding:8px 12px">'
          + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:36px;height:36px;border-radius:50%;object-fit:cover" loading="lazy">'
          + '<div style="flex:1;min-width:0">'
          + '<div class="text-sm font-medium" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + SS.utils.esc(u.fullname) + (u.is_verified ? ' <span style="color:var(--primary)">✓</span>' : '') + '</div>';
        if (reason) {
          html += '<div class="text-xs text-muted" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + SS.utils.esc(reason) + '</div>';
        }
        html += '</div><button class="btn btn-primary btn-sm" style="padding:4px 10px;font-size:11px" onclick="SS.SmartSuggest._follow(' + u.id + ',this)">Theo dõi</button></div>';
      }

      html += '</div>';
      el.innerHTML = html;
    }).catch(function() {});
  },

  _follow: function(userId, btn) {
    btn.disabled = true;
    btn.textContent = '...';
    SS.api.post('/social.php?action=follow', {user_id: userId}).then(function() {
      btn.textContent = 'Đã theo dõi';
      btn.className = 'btn btn-ghost btn-sm';
      btn.style.cssText = 'padding:4px 10px;font-size:11px';
    }).catch(function() {
      btn.disabled = false;
      btn.textContent = 'Theo dõi';
    });
  },

  // Show full page of suggestions
  showAll: function() {
    SS.api.get('/smart-suggest.php?limit=20').then(function(d) {
      var users = d.data || [];
      var html = '';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        html += '<div class="flex items-center gap-3 p-3" style="border-bottom:1px solid var(--border-light)">'
          + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-md" style="cursor:pointer" onclick="window.location.href=\'/user.html?id=' + u.id + '\'" loading="lazy">'
          + '<div class="flex-1">'
          + '<div class="font-medium">' + SS.utils.esc(u.fullname) + (u.is_verified ? ' ✓' : '') + '</div>'
          + '<div class="text-xs text-muted">' + (u.shipping_company || '') + (u.reasons && u.reasons.length ? ' · ' + u.reasons.join(' · ') : '') + '</div>'
          + '<div class="text-xs text-muted">' + u.total_posts + ' bài · ' + u.total_success + ' đơn</div></div>'
          + '<button class="btn btn-primary btn-sm" onclick="SS.SmartSuggest._follow(' + u.id + ',this)">Theo dõi</button></div>';
      }
      SS.ui.sheet({title: 'Gợi ý theo dõi (' + users.length + ')', html: html || '<div class="empty-state p-4">Không có gợi ý</div>'});
    });
  }
};
