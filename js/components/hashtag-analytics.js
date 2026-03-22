/**
 * ShipperShop Component — Hashtag Analytics
 */
window.SS = window.SS || {};

SS.HashtagAnalytics = {
  show: function(days) {
    days = days || 30;
    SS.api.get('/hashtag-analytics.php?days=' + days).then(function(d) {
      var data = d.data || {};
      var hashtags = data.hashtags || [];
      var html = '<div class="flex gap-2 mb-3">';
      [7, 30, 90].forEach(function(dd) {
        html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.HashtagAnalytics.show(' + dd + ')" style="cursor:pointer">' + dd + 'd</div>';
      });
      html += '</div>';
      html += '<div class="text-xs text-muted mb-3">' + (data.total_unique || 0) + ' hashtag duy nhat / ' + (data.posts_analyzed || 0) + ' bai</div>';
      if (hashtags.length) {
        var maxP = hashtags[0].posts || 1;
        for (var i = 0; i < Math.min(hashtags.length, 15); i++) {
          var h = hashtags[i];
          var w = Math.max(10, Math.round(h.posts / maxP * 100));
          html += '<div class="mb-2" style="cursor:pointer" onclick="SS.HashtagAnalytics.detail(\'' + SS.utils.esc(h.tag).replace(/#/g, '') + '\')">'
            + '<div class="flex justify-between text-sm mb-1"><span style="color:var(--primary);font-weight:600">' + SS.utils.esc(h.tag) + '</span><span class="text-xs">' + h.posts + ' bai · TB ' + h.avg_engagement + ' eng</span></div>'
            + '<div style="height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + w + '%;height:100%;background:var(--primary);border-radius:4px"></div></div></div>';
        }
      }
      SS.ui.sheet({title: '#️⃣ Hashtag Analytics', html: html});
    });
  },
  detail: function(tag) {
    SS.ui.closeSheet();
    SS.api.get('/hashtag-analytics.php?action=detail&tag=' + encodeURIComponent(tag)).then(function(d) {
      var data = d.data || {};
      var posts = data.posts || [];
      var html = '<div class="text-center mb-3"><div class="font-bold text-lg" style="color:var(--primary)">' + SS.utils.esc(data.tag || '') + '</div><div class="text-xs text-muted">' + (data.post_count || 0) + ' bai · ' + (data.total_engagement || 0) + ' tuong tac</div></div>';
      for (var i = 0; i < posts.length; i++) {
        var p = posts[i];
        html += '<div class="card mb-2" style="padding:8px"><div class="text-sm">' + SS.utils.esc((p.content || '').substring(0, 80)) + '</div><div class="text-xs text-muted">' + SS.utils.esc(p.fullname) + ' · ❤️' + p.likes_count + ' 💬' + p.comments_count + '</div></div>';
      }
      SS.ui.sheet({title: '#' + SS.utils.esc(tag), html: html});
    });
  }
};
