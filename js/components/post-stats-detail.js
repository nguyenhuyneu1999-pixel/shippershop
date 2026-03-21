/**
 * ShipperShop Component — Post Stats Detail
 * Detailed analytics for a single post (for post owner)
 */
window.SS = window.SS || {};

SS.PostStatsDetail = {
  show: function(postId) {
    SS.api.get('/post-stats-detail.php?post_id=' + postId).then(function(d) {
      var data = d.data || {};

      // Basic stats
      var html = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px;text-align:center">';
      var items = [
        {icon: '❤️', value: data.likes || 0, label: 'Thich'},
        {icon: '💬', value: data.comments || 0, label: 'Ghi chu'},
        {icon: '👁️', value: data.views || 0, label: 'Luot xem'},
        {icon: '🔗', value: data.shares || 0, label: 'Chia se'},
      ];
      for (var i = 0; i < items.length; i++) {
        html += '<div class="card" style="padding:8px"><div>' + items[i].icon + '</div>'
          + '<div class="font-bold">' + items[i].value + '</div>'
          + '<div class="text-xs text-muted">' + items[i].label + '</div></div>';
      }
      html += '</div>';

      // Engagement rate (owner only)
      if (data.engagement_rate !== undefined) {
        html += '<div class="card mb-3" style="padding:10px;border-left:3px solid var(--primary)">'
          + '<div class="flex justify-between"><span class="text-sm">Ty le tuong tac</span><span class="font-bold" style="color:var(--primary)">' + data.engagement_rate + '%</span></div></div>';
      }

      // Read time
      if (data.read_time_seconds) {
        html += '<div class="text-xs text-muted mb-2">⏱️ Thoi gian doc: ~' + Math.round(data.read_time_seconds / 60) + ' phut</div>';
      }

      // Top commenters
      var commenters = data.top_commenters || [];
      if (commenters.length) {
        html += '<div class="text-sm font-bold mb-2">Nguoi binh luan nhieu nhat</div>';
        for (var j = 0; j < commenters.length; j++) {
          var c = commenters[j];
          html += '<div class="flex items-center gap-2 p-1"><img src="' + (c.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy"><span class="text-sm flex-1">' + SS.utils.esc(c.fullname) + '</span><span class="text-xs font-bold">' + c.comment_count + '</span></div>';
        }
      }

      // Share platforms
      if (data.share_platforms) {
        var platforms = data.share_platforms;
        var pKeys = Object.keys(platforms);
        if (pKeys.length) {
          html += '<div class="text-sm font-bold mt-3 mb-2">Chia se theo nen tang</div><div class="flex gap-2 flex-wrap">';
          var pIcons = {copy: '📋', facebook: '📘', zalo: '💬', messenger: '💙', twitter: '🐦'};
          for (var k = 0; k < pKeys.length; k++) {
            html += '<div class="chip">' + (pIcons[pKeys[k]] || '🔗') + ' ' + pKeys[k] + ': ' + platforms[pKeys[k]] + '</div>';
          }
          html += '</div>';
        }
      }

      SS.ui.sheet({title: 'Thong ke bai viet #' + postId, html: html});
    });
  }
};
