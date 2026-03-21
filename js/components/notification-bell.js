/**
 * ShipperShop Component — Notification Bell
 * Renders bell icon with badge count, dropdown panel, polling
 */
window.SS = window.SS || {};

SS.NotifBell = {
  _count: 0,
  _timer: null,
  _open: false,

  init: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.style.position = 'relative';
    el.innerHTML = '<button class="btn btn-icon btn-ghost" id="ss-bell-btn" onclick="SS.NotifBell.toggle()">'
      + '<i class="fa-solid fa-bell" style="font-size:18px"></i>'
      + '<span id="ss-bell-badge" class="tab-badge" style="position:absolute;top:2px;right:2px;display:none">0</span>'
      + '</button>'
      + '<div id="ss-bell-dropdown" class="dropdown" style="right:0;top:42px;width:340px;max-height:400px;overflow-y:auto;display:none">'
      + '<div style="padding:12px 14px;font-weight:600;display:flex;justify-content:space-between;align-items:center">Thông báo <span class="text-primary text-sm cursor-pointer" onclick="SS.NotifBell.markAllRead()">Đọc tất cả</span></div>'
      + '<div id="ss-bell-list"></div>'
      + '</div>';

    SS.NotifBell.loadCount();
    SS.NotifBell.startPolling(30000);
  },

  loadCount: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    SS.api.get('/notifications.php?action=count').then(function(d) {
      SS.NotifBell._count = d.count || 0;
      SS.NotifBell._updateBadge();
    }).catch(function() {});
  },

  _updateBadge: function() {
    var badge = document.getElementById('ss-bell-badge');
    if (!badge) return;
    if (SS.NotifBell._count > 0) {
      badge.style.display = 'inline-flex';
      badge.textContent = SS.NotifBell._count > 99 ? '99+' : SS.NotifBell._count;
    } else {
      badge.style.display = 'none';
    }
  },

  toggle: function() {
    var dd = document.getElementById('ss-bell-dropdown');
    if (!dd) return;
    SS.NotifBell._open = !SS.NotifBell._open;
    dd.style.display = SS.NotifBell._open ? 'block' : 'none';
    if (SS.NotifBell._open) SS.NotifBell.loadList();

    // Close on outside click
    if (SS.NotifBell._open) {
      setTimeout(function() {
        var handler = function(e) {
          if (!dd.contains(e.target) && e.target.id !== 'ss-bell-btn') {
            dd.style.display = 'none';
            SS.NotifBell._open = false;
            document.removeEventListener('click', handler);
          }
        };
        document.addEventListener('click', handler);
      }, 0);
    }
  },

  loadList: function() {
    var list = document.getElementById('ss-bell-list');
    if (!list) return;
    list.innerHTML = '<div class="flex justify-center p-4"><div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%"></div></div>';

    SS.api.get('/notifications.php?action=list&limit=15').then(function(d) {
      var notifs = (d.data && d.data.notifications) || [];
      if (!notifs.length) {
        list.innerHTML = '<div class="p-4 text-center text-muted text-sm">Chưa có thông báo</div>';
        return;
      }
      var html = '';
      for (var i = 0; i < notifs.length; i++) {
        var n = notifs[i];
        var link = (n.data && typeof n.data === 'object' && n.data.link) ? n.data.link : '#';
        var unread = !n.is_read ? 'background:var(--primary-50);' : '';
        var icon = SS.NotifBell._typeIcon(n.type);
        html += '<a href="' + link + '" class="dropdown-item" style="' + unread + 'padding:10px 14px" onclick="SS.NotifBell.markRead(' + n.id + ')">'
          + '<span style="font-size:16px;width:28px;text-align:center;flex-shrink:0">' + icon + '</span>'
          + '<div style="flex:1;min-width:0">'
          + '<div style="font-size:13px;font-weight:' + (n.is_read ? '400' : '500') + '">' + SS.utils.esc(n.title || '') + '</div>'
          + '<div style="font-size:12px;color:var(--text-muted)">' + SS.utils.esc(n.message || '') + '</div>'
          + '<div style="font-size:11px;color:var(--text-muted);margin-top:2px">' + SS.utils.ago(n.created_at) + '</div>'
          + '</div>'
          + (n.is_read ? '' : '<div style="width:8px;height:8px;background:var(--primary);border-radius:50%;flex-shrink:0"></div>')
          + '</a>';
      }
      list.innerHTML = html;
    }).catch(function() {
      list.innerHTML = '<div class="p-4 text-center text-muted text-sm">Không thể tải</div>';
    });
  },

  _typeIcon: function(type) {
    var map = {
      'like': '<i class="fa-solid fa-check-circle" style="color:var(--primary)"></i>',
      'comment': '<i class="fa-regular fa-comment" style="color:var(--info)"></i>',
      'follow': '<i class="fa-solid fa-user-plus" style="color:var(--success)"></i>',
      'message': '<i class="fa-solid fa-envelope" style="color:var(--warning)"></i>',
      'group_invite': '<i class="fa-solid fa-users" style="color:var(--info)"></i>',
      'system': '<i class="fa-solid fa-bell" style="color:var(--text-muted)"></i>'
    };
    return map[type] || map['system'];
  },

  markRead: function(id) {
    SS.api.post('/notifications.php?action=mark_read', {notification_id: id}).catch(function() {});
    SS.NotifBell._count = Math.max(0, SS.NotifBell._count - 1);
    SS.NotifBell._updateBadge();
  },

  markAllRead: function() {
    SS.api.post('/notifications.php?action=mark_all_read', {}).then(function() {
      SS.NotifBell._count = 0;
      SS.NotifBell._updateBadge();
      SS.NotifBell.loadList();
      SS.ui.toast('Đã đọc tất cả', 'success');
    });
  },

  startPolling: function(interval) {
    if (SS.NotifBell._timer) clearInterval(SS.NotifBell._timer);
    SS.NotifBell._timer = setInterval(function() {
      SS.NotifBell.loadCount();
    }, interval || 30000);
  },

  stopPolling: function() {
    if (SS.NotifBell._timer) {
      clearInterval(SS.NotifBell._timer);
      SS.NotifBell._timer = null;
    }
  }
};
