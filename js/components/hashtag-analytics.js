/**
 * ShipperShop Component — Hashtag Analytics
 */
window.SS = window.SS || {};

SS.HashtagAnalytics = {
  show: function(days) {
    days = days || 30;
    SS.api.get('/hashtag-analytics.php?days=' + days).then(function(d) {
      var data = d.data || {};
      var tags = data.hashtags || [];
      var html = '<div class="flex gap-2 mb-3">';
      [7, 30, 90].forEach(function(dd) {
        html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.HashtagAnalytics.show(' + dd + ')" style="cursor:pointer">' + dd + 'd</div>';
      });
      html += '</div><div class="text-xs text-muted mb-3">' + (data.total_unique || 0) + ' hashtag | ' + (data.posts_scanned || 0) + ' bai</div>';
      if (!tags.length) { html += '<div class="text-muted text-center p-3">Chua co hashtag</div>'; }
      var maxC = tags[0] ? tags[0].count : 1;
      for (var i = 0; i < tags.length; i++) {
        var t = tags[i];
        var w = Math.max(10, Math.round(t.count / maxC * 100));
        html += '<div class="mb-2" style="cursor:pointer" onclick="SS.HashtagAnalytics.detail(\'' + SS.utils.esc(t.tag).replace(/'/g, '') + '\')">'
          + '<div class="flex justify-between text-sm mb-1"><span style="color:var(--primary);font-weight:600">' + SS.utils.esc(t.tag) + '</span><span class="font-bold">' + t.count + '</span></div>'
          + '<div style="height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + w + '%;height:100%;background:var(--primary);border-radius:4px"></div></div></div>';
      }
      SS.ui.sheet({title: '# Hashtag Analytics', html: html});
    });
  },
  detail: function(tag) {
    SS.ui.closeSheet();
    SS.api.get('/hashtag-analytics.php?action=detail&tag=' + encodeURIComponent(tag)).then(function(d) {
      var data = d.data || {};
      var html = '<div class="text-center mb-3"><div class="font-bold text-lg" style="color:var(--primary)">' + SS.utils.esc(data.tag || '') + '</div>'
        + '<div class="text-sm">' + (data.total_posts || 0) + ' bai · ' + (data.avg_engagement || 0) + ' TB tuong tac</div></div>';
      var posts = data.posts || [];
      for (var i = 0; i < Math.min(posts.length, 5); i++) {
        var p = posts[i];
        html += '<div class="card mb-1" style="padding:8px"><div class="text-xs">' + SS.utils.esc((p.content || '').substring(0, 80)) + '</div>'
          + '<div class="text-xs text-muted">❤️' + (p.likes_count || 0) + ' · ' + SS.utils.ago(p.created_at) + '</div></div>';
      }
      SS.ui.sheet({title: SS.utils.esc(data.tag || ''), html: html});
    });
  }
};
