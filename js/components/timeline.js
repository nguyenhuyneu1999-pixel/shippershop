/**
 * ShipperShop Component — User Timeline
 * Activity timeline for user profiles
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.Timeline = {

  render: function(userId, containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '<div class="p-3 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/timeline.php?user_id=' + userId + '&limit=15').then(function(d) {
      var events = (d.data || {}).events || [];
      if (!events.length) {
        el.innerHTML = '<div class="empty-state p-4"><div class="empty-icon">📋</div><div class="empty-text">Chưa có hoạt động</div></div>';
        return;
      }

      var html = '<div style="position:relative;padding-left:32px">';
      // Timeline line
      html += '<div style="position:absolute;left:11px;top:8px;bottom:8px;width:2px;background:var(--border-light)"></div>';

      for (var i = 0; i < events.length; i++) {
        var e = events[i];
        var link = '';
        if (e.type === 'post') link = '/post-detail.html?id=' + e.id;
        else if (e.type === 'comment') link = '/post-detail.html?id=' + e.post_id;
        else if (e.type === 'group_join') link = '/group.html?id=' + e.group_id;

        html += '<div style="position:relative;margin-bottom:16px' + (link ? ';cursor:pointer' : '') + '"' + (link ? ' onclick="window.location.href=\'' + link + '\'"' : '') + '>'
          + '<div style="position:absolute;left:-28px;width:20px;height:20px;border-radius:50%;background:var(--card);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:10px">' + e.icon + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.ago(e.time) + ' · ' + SS.utils.esc(e.label) + '</div>'
          + '<div class="text-sm mt-1">' + SS.utils.esc(e.text || '') + '</div>';

        if (e.likes !== undefined) {
          html += '<div class="text-xs text-muted mt-1">❤️ ' + (e.likes || 0) + ' · 💬 ' + (e.comments || 0) + '</div>';
        }
        html += '</div>';
      }
      html += '</div>';

      if ((d.data || {}).has_more) {
        html += '<div class="text-center mt-2"><button class="btn btn-ghost btn-sm" onclick="SS.Timeline.showAll(' + userId + ')">Xem thêm</button></div>';
      }

      el.innerHTML = html;
    }).catch(function() { el.innerHTML = ''; });
  },

  showAll: function(userId) {
    window.location.href = '/activity-log.html?user_id=' + userId;
  }
};
