/**
 * ShipperShop Component — Grouped Notifications
 * Collapsed notifications: "A, B và 3 người khác đã thành công bài viết"
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.NotifGrouped = {

  open: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/notif-grouped.php?limit=20').then(function(d) {
      var data = d.data || {};
      var notifs = data.notifications || [];
      var unread = data.unread || 0;

      if (!notifs.length) {
        SS.ui.sheet({title: 'Thông báo', html: '<div class="empty-state p-4"><div class="empty-icon">🔔</div><div class="empty-text">Chưa có thông báo</div></div>'});
        return;
      }

      var typeIcons = {reaction:'❤️',like:'👍',comment:'💬',follow:'👤',mention:'@',message:'✉️',report:'🚩',system:'📢'};
      var html = '';

      for (var i = 0; i < notifs.length; i++) {
        var n = notifs[i];
        var icon = typeIcons[n.type] || '🔔';
        var avatars = n.actors || [];
        var isNew = !n.is_read;

        // Avatar stack (max 3)
        var avHtml = '<div style="display:flex;margin-left:-4px">';
        for (var a = 0; a < Math.min(avatars.length, 3); a++) {
          avHtml += '<img src="' + (avatars[a].avatar || '/assets/img/defaults/avatar.svg') + '" style="width:28px;height:28px;border-radius:50%;border:2px solid var(--card);margin-left:' + (a > 0 ? '-8' : '0') + 'px;object-fit:cover" loading="lazy">';
        }
        if (n.count > 3) avHtml += '<div style="width:28px;height:28px;border-radius:50%;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;margin-left:-8px;border:2px solid var(--card)">+' + (n.count - 3) + '</div>';
        avHtml += '</div>';

        var link = '#';
        if (n.data && n.data.post_id) link = '/post-detail.html?id=' + n.data.post_id;
        else if (n.data && n.data.user_id) link = '/user.html?id=' + n.data.user_id;

        html += '<a href="' + link + '" class="list-item" style="text-decoration:none;color:var(--text);padding:10px 16px;background:' + (isNew ? 'var(--primary)08' : 'transparent') + '">'
          + '<span style="font-size:20px;width:24px;text-align:center">' + icon + '</span>'
          + avHtml
          + '<div class="flex-1" style="min-width:0;margin-left:8px">'
          + '<div class="text-sm" style="line-height:1.4">' + SS.utils.esc(n.grouped_message || n.message || '') + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.ago(n.latest) + (n.count > 1 ? ' · ' + n.count + ' thông báo' : '') + '</div>'
          + '</div>'
          + (isNew ? '<div style="width:8px;height:8px;border-radius:50%;background:var(--primary);flex-shrink:0"></div>' : '')
          + '</a>';
      }

      SS.ui.sheet({title: 'Thông báo' + (unread > 0 ? ' (' + unread + ' mới)' : ''), html: html});

      // Mark all read
      SS.api.post('/notifications.php?action=mark_read', {}).catch(function() {});
    });
  }
};
