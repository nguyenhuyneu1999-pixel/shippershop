/**
 * ShipperShop Page — Content Stats (Author Dashboard)
 * Shows daily posting stats, engagement trends, best content
 * Uses: SS.api, SS.ui, SS.Charts
 */
window.SS = window.SS || {};

SS.ContentStats = {

  init: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !SS.store || !SS.store.isLoggedIn()) return;

    el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/post-analytics.php?action=overview&days=30').then(function(d) {
      var s = d.data || {};

      var html = '<div class="card mb-3"><div class="card-header flex justify-between items-center">Tổng quan 30 ngày'
        + '<select class="form-select" style="width:auto;font-size:11px;padding:2px 8px;border:0" onchange="SS.ContentStats._reload(this.value)"><option value="7">7 ngày</option><option value="30" selected>30 ngày</option><option value="90">90 ngày</option></select>'
        + '</div><div class="card-body">'
        + '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">'
        + SS.ContentStats._stat('Bài viết', s.total_posts, 'var(--primary)')
        + SS.ContentStats._stat('Thành công', s.total_likes, 'var(--success)')
        + SS.ContentStats._stat('Ghi chú', s.total_comments, 'var(--info)')
        + SS.ContentStats._stat('Chuyển tiếp', s.total_shares, 'var(--warning)')
        + '</div>'
        + '<div class="divider"></div>'
        + '<div class="flex justify-between text-sm">'
        + '<div>TB thành công/bài: <strong>' + (s.avg_likes || 0) + '</strong></div>'
        + '<div>Tương tác: <strong>' + (s.overall_engagement || 0) + '%</strong></div>'
        + '</div>'
        + (s.best_posting_hour !== null ? '<div class="text-sm text-muted mt-2">Giờ đăng tốt nhất: <strong>' + s.best_posting_hour + ':00</strong></div>' : '')
        + '</div></div>';

      // Daily chart
      if (s.daily && s.daily.length > 1) {
        html += '<div class="card mb-3"><div class="card-header">Hoạt động hàng ngày</div><div class="card-body"><div id="cs-daily-chart" style="height:150px"></div></div></div>';
      }

      // Top posts
      if (s.top_posts && s.top_posts.length) {
        html += '<div class="card mb-3"><div class="card-header">Top bài viết</div>';
        for (var i = 0; i < s.top_posts.length; i++) {
          var p = s.top_posts[i];
          html += '<a href="/post-detail.html?id=' + p.id + '" class="list-item" style="text-decoration:none;color:var(--text);padding:10px 16px">'
            + '<div style="width:24px;font-weight:800;color:' + (i < 3 ? 'var(--primary)' : 'var(--text-muted)') + '">' + (i + 1) + '</div>'
            + '<div class="flex-1 truncate text-sm">' + SS.utils.esc((p.content || '').substring(0, 60)) + '</div>'
            + '<div class="text-xs text-muted text-right" style="min-width:60px">' + SS.utils.fN(p.likes_count) + '❤️ ' + SS.utils.fN(p.comments_count) + '💬</div></a>';
        }
        html += '</div>';
      }

      el.innerHTML = html;

      // Render chart
      if (s.daily && s.daily.length > 1 && SS.Charts) {
        var chartEl = document.getElementById('cs-daily-chart');
        if (chartEl) {
          var labels = s.daily.map(function(d) { return d.day.substring(5); });
          var values = s.daily.map(function(d) { return parseInt(d.posts || 0); });
          var likes = s.daily.map(function(d) { return parseInt(d.likes || 0); });
          SS.Charts.sparkline(chartEl, values, {color: 'var(--primary)', height: 150});
        }
      }
    }).catch(function() {
      el.innerHTML = '<div class="p-4 text-center text-danger">Lỗi tải dữ liệu</div>';
    });
  },

  _stat: function(label, value, color) {
    return '<div class="text-center"><div style="font-size:22px;font-weight:800;color:' + color + '">' + SS.utils.fN(value || 0) + '</div><div class="text-xs text-muted">' + label + '</div></div>';
  },

  _reload: function(days) {
    var el = document.querySelector('[id]'); // Will need proper container reference
    SS.api.get('/post-analytics.php?action=overview&days=' + days).then(function(d) {
      // Re-render with new data (simplified)
      SS.ContentStats.init('content-stats-container');
    });
  }
};
