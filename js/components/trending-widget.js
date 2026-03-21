/**
 * ShipperShop Component — Trending Widget
 * Shows hot posts and top users in sidebar
 * Uses: SS.api, SS.utils
 */
window.SS = window.SS || {};

SS.TrendingWidget = {

  render: function(containerId, opts) {
    opts = opts || {};
    var el = document.getElementById(containerId);
    if (!el) return;

    el.innerHTML = '<div class="card mb-3"><div class="card-header flex justify-between items-center">Nổi bật <select id="tw-period" class="form-select" style="width:auto;font-size:11px;padding:2px 8px;border:0;background:transparent;color:var(--primary)" onchange="SS.TrendingWidget._loadPosts(this.value)"><option value="day">Hôm nay</option><option value="week" selected>Tuần này</option><option value="month">Tháng này</option></select></div><div id="tw-posts" style="max-height:400px;overflow-y:auto"></div></div>'
      + '<div class="card"><div class="card-header">Top Shipper</div><div id="tw-users" style="max-height:300px;overflow-y:auto"></div></div>';

    SS.TrendingWidget._loadPosts('week');
    SS.TrendingWidget._loadUsers();
  },

  _loadPosts: function(period) {
    var el = document.getElementById('tw-posts');
    if (!el) return;
    el.innerHTML = '<div class="p-3 text-center"><div class="spin" style="width:16px;height:16px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/trending.php?action=hot&period=' + period + '&limit=5').then(function(d) {
      var posts = (d.data && d.data.posts) || [];
      if (!posts.length) { el.innerHTML = '<div class="p-3 text-center text-muted text-xs">Chưa có bài nổi bật</div>'; return; }

      var html = '';
      for (var i = 0; i < posts.length; i++) {
        var p = posts[i];
        var score = parseFloat(p.hot_score || 0).toFixed(1);
        html += '<a href="/post-detail.html?id=' + p.id + '" class="list-item" style="text-decoration:none;color:var(--text);padding:8px 16px">'
          + '<div style="width:24px;font-weight:800;font-size:14px;color:' + (i < 3 ? 'var(--primary)' : 'var(--text-muted)') + '">' + (i + 1) + '</div>'
          + '<div class="flex-1" style="min-width:0">'
          + '<div class="text-sm truncate">' + SS.utils.esc((p.content || '').substring(0, 60)) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(p.user_name || '') + ' · ' + SS.utils.fN(p.likes_count || 0) + ' thành công · ' + SS.utils.fN(p.comments_count || 0) + ' ghi chú</div>'
          + '</div></a>';
      }
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = '<div class="p-3 text-center text-muted text-xs">Lỗi</div>'; });
  },

  _loadUsers: function() {
    var el = document.getElementById('tw-users');
    if (!el) return;

    SS.api.get('/trending.php?action=top_users&period=week&limit=5').then(function(d) {
      var users = d.data || [];
      if (!users.length) { el.innerHTML = '<div class="p-3 text-center text-muted text-xs">Chưa có dữ liệu</div>'; return; }

      var html = '';
      var medals = ['🥇', '🥈', '🥉'];
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        var medal = i < 3 ? medals[i] : '#' + (i + 1);
        html += '<a href="/user.html?id=' + u.id + '" class="list-item" style="text-decoration:none;color:var(--text);padding:8px 16px">'
          + '<div style="width:24px;text-align:center;font-size:' + (i < 3 ? '16' : '12') + 'px">' + medal + '</div>'
          + '<img class="avatar avatar-sm" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
          + '<div class="flex-1" style="min-width:0">'
          + '<div class="text-sm font-medium truncate">' + SS.utils.esc(u.fullname) + (parseInt(u.is_verified) ? ' <i class="fa-solid fa-circle-check" style="color:#3b82f6;font-size:11px"></i>' : '') + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.fN(u.total_likes || 0) + ' thành công · ' + SS.utils.fN(u.post_count || 0) + ' bài</div>'
          + '</div></a>';
      }
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = ''; });
  }
};
