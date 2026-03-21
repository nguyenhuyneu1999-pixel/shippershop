/**
 * ShipperShop Component — Post Analytics
 * Per-post stats modal + creator dashboard
 * Uses: SS.api, SS.ui, SS.Charts
 */
window.SS = window.SS || {};

SS.PostAnalytics = {

  // Show stats for single post
  showPost: function(postId) {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/post-analytics.php?action=post&post_id=' + postId).then(function(d) {
      var s = d.data || {};
      var emojis = {like:'👍',love:'❤️',fire:'🔥',wow:'😮',sad:'😢',angry:'😠'};

      var reactionHtml = '';
      var reactions = s.reactions || {};
      for (var key in reactions) {
        if (reactions[key] > 0) reactionHtml += '<span style="margin-right:6px">' + (emojis[key] || '') + ' ' + reactions[key] + '</span>';
      }

      var html = '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:16px">'
        + '<div class="card"><div class="card-body text-center"><div style="font-size:24px;font-weight:800;color:var(--primary)">' + SS.utils.fN(s.views || 0) + '</div><div class="text-xs text-muted">Lượt xem</div></div></div>'
        + '<div class="card"><div class="card-body text-center"><div style="font-size:24px;font-weight:800;color:var(--success)">' + (s.engagement_rate || 0) + '%</div><div class="text-xs text-muted">Tương tác</div></div></div>'
        + '<div class="card"><div class="card-body text-center"><div style="font-size:24px;font-weight:800;color:var(--info)">' + SS.utils.fN(s.likes || 0) + '</div><div class="text-xs text-muted">Thành công</div></div></div>'
        + '<div class="card"><div class="card-body text-center"><div style="font-size:24px;font-weight:800;color:var(--warning)">' + SS.utils.fN(s.saves || 0) + '</div><div class="text-xs text-muted">Lưu</div></div></div>'
        + '</div>'
        + '<div class="list-item"><div class="flex-1">Ghi chú</div><div class="font-bold">' + SS.utils.fN(s.comments || 0) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Chuyển tiếp</div><div class="font-bold">' + SS.utils.fN(s.shares || 0) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Xem duy nhất</div><div class="font-bold">' + SS.utils.fN(s.unique_views || 0) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Tuổi bài</div><div class="font-bold">' + Math.round(s.age_hours || 0) + 'h</div></div>'
        + (reactionHtml ? '<div class="mt-3 p-3" style="background:var(--bg);border-radius:8px"><div class="text-xs text-muted mb-1">Reactions</div>' + reactionHtml + '</div>' : '');

      SS.ui.sheet({title: 'Phân tích bài viết #' + postId, html: html});
    }).catch(function() { SS.ui.toast('Lỗi tải', 'error'); });
  },

  // Creator dashboard
  openDashboard: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/post-analytics.php?action=overview&days=30').then(function(d) {
      var s = d.data || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px">'
        + '<div class="text-center"><div style="font-size:20px;font-weight:800;color:var(--primary)">' + SS.utils.fN(s.total_posts || 0) + '</div><div class="text-xs text-muted">Bài viết</div></div>'
        + '<div class="text-center"><div style="font-size:20px;font-weight:800;color:var(--success)">' + SS.utils.fN(s.total_likes || 0) + '</div><div class="text-xs text-muted">Thành công</div></div>'
        + '<div class="text-center"><div style="font-size:20px;font-weight:800;color:var(--info)">' + SS.utils.fN(s.total_views || 0) + '</div><div class="text-xs text-muted">Lượt xem</div></div>'
        + '</div>'
        + '<div class="list-item"><div class="flex-1">TB thành công/bài</div><div class="font-bold">' + (s.avg_likes || 0) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">TB ghi chú/bài</div><div class="font-bold">' + (s.avg_comments || 0) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Tỷ lệ tương tác</div><div class="font-bold">' + (s.overall_engagement || 0) + '%</div></div>'
        + (s.best_posting_hour !== null ? '<div class="list-item"><div class="flex-1">Giờ đăng tốt nhất</div><div class="font-bold">' + s.best_posting_hour + ':00</div></div>' : '')
        + (s.top_posts && s.top_posts.length ? '<div class="divider"></div><div class="text-sm font-bold mb-2">Top bài viết</div>' : '');

      if (s.top_posts) {
        for (var i = 0; i < s.top_posts.length; i++) {
          var p = s.top_posts[i];
          html += '<a href="/post-detail.html?id=' + p.id + '" class="list-item" style="text-decoration:none;color:var(--text)">'
            + '<div style="width:20px;font-weight:700;color:var(--primary)">' + (i + 1) + '</div>'
            + '<div class="flex-1 truncate text-sm">' + SS.utils.esc((p.content || '').substring(0, 50)) + '</div>'
            + '<div class="text-xs text-muted">' + SS.utils.fN(p.likes_count) + '❤️ ' + SS.utils.fN(p.comments_count) + '💬</div></a>';
        }
      }

      SS.ui.sheet({title: 'Dashboard tác giả (30 ngày)', html: html});
    }).catch(function() { SS.ui.toast('Lỗi', 'error'); });
  }
};
