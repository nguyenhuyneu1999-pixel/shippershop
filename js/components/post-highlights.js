/**
 * ShipperShop Component — Post Highlights Reel
 */
window.SS = window.SS || {};

SS.PostHighlights = {
  show: function(userId) {
    SS.api.get('/post-highlights.php?user_id=' + userId).then(function(d) {
      var data = d.data || {};
      var highlights = data.highlights || [];
      if (!highlights.length) {
        SS.ui.sheet({title: 'Highlight', html: '<div class="empty-state p-4"><div class="empty-icon">⭐</div><div class="empty-text">Chua co bai highlight</div></div>'});
        return;
      }
      var html = '';
      for (var i = 0; i < highlights.length; i++) {
        var p = highlights[i];
        html += '<div class="card mb-2" style="padding:12px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'">'
          + '<div class="text-sm">' + SS.utils.esc((p.content || '').substring(0, 120)) + '</div>'
          + '<div class="text-xs text-muted mt-1">❤️ ' + (p.likes_count || 0) + ' 💬 ' + (p.comments_count || 0) + ' · ' + SS.utils.ago(p.created_at) + '</div></div>';
      }
      SS.ui.sheet({title: '⭐ Highlight (' + highlights.length + '/10)', html: html});
    });
  },

  toggle: function(postId) {
    SS.api.post('/post-highlights.php', {post_id: postId}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success');
    });
  }
};
