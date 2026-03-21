/**
 * ShipperShop Component — Author Stats
 * Shows posting frequency, avg likes, top post on user profile
 * Uses: SS.api, SS.utils
 */
window.SS = window.SS || {};

SS.AuthorStats = {

  render: function(containerId, userId) {
    var el = document.getElementById(containerId);
    if (!el) return;

    SS.api.get('/activity-feed.php?action=author_stats&user_id=' + userId).then(function(d) {
      var s = d.data || {};
      if (!s.total_posts) { el.innerHTML = ''; return; }

      el.innerHTML = '<div class="card mt-3"><div class="card-header">Thống kê tác giả</div><div class="card-body">'
        + '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;text-align:center;margin-bottom:12px">'
        + '<div><div style="font-size:20px;font-weight:800;color:var(--primary)">' + SS.utils.fN(s.total_posts) + '</div><div class="text-xs text-muted">Bài viết</div></div>'
        + '<div><div style="font-size:20px;font-weight:800;color:var(--success)">' + SS.utils.fN(s.total_likes) + '</div><div class="text-xs text-muted">Thành công</div></div>'
        + '<div><div style="font-size:20px;font-weight:800;color:var(--info)">' + SS.utils.fN(s.total_comments) + '</div><div class="text-xs text-muted">Ghi chú</div></div>'
        + '</div>'
        + '<div class="list-item" style="padding:8px 0"><div class="flex-1 text-sm">TB thành công/bài</div><div class="font-bold">' + s.avg_likes_per_post + '</div></div>'
        + '<div class="list-item" style="padding:8px 0"><div class="flex-1 text-sm">Tần suất đăng</div><div class="font-bold text-sm">' + SS.utils.esc(s.posting_frequency || '') + '</div></div>'
        + (s.top_post ? '<div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border)"><div class="text-xs text-muted mb-1">Bài viết nổi bật nhất</div><a href="/post-detail.html?id=' + s.top_post.id + '" class="text-sm" style="color:var(--primary);text-decoration:none">' + SS.utils.esc((s.top_post.content || '').substring(0, 80)) + '...</a><div class="text-xs text-muted mt-1">' + SS.utils.fN(s.top_post.likes_count) + ' thành công · ' + SS.utils.fN(s.top_post.comments_count) + ' ghi chú</div></div>' : '')
        + '</div></div>';
    }).catch(function() { el.innerHTML = ''; });
  }
};
