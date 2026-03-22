window.SS = window.SS || {};
SS.PostAnalyticsV2 = {
  show: function(postId) {
    SS.api.get('/post-analytics-v2.php?post_id=' + postId).then(function(d) {
      var data = d.data || {};
      if (!data.post) return;
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center;margin-bottom:12px">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.engagement || 0) + '</div><div class="text-xs text-muted">Engagement</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.engagement_rate || 0) + '%</div><div class="text-xs text-muted">Eng Rate</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.likes_per_hour || 0) + '</div><div class="text-xs text-muted">/gio</div></div></div>';
      var commenters = data.top_commenters || [];
      if (commenters.length) {
        html += '<div class="text-xs font-bold mb-1">Top commenters</div>';
        for (var i = 0; i < commenters.length; i++) {
          var c = commenters[i];
          html += '<div class="flex items-center gap-2 p-1"><img src="' + (c.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy"><span class="text-xs flex-1">' + SS.utils.esc(c.fullname) + '</span><span class="text-xs font-bold">' + c.count + '</span></div>';
        }
      }
      SS.ui.sheet({title: 'Analytics #' + postId, html: html});
    });
  }
};
