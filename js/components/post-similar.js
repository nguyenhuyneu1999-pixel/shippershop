/**
 * ShipperShop Component — Similar Posts
 */
window.SS = window.SS || {};

SS.PostSimilar = {
  show: function(postId) {
    SS.api.get('/post-similar.php?post_id=' + postId).then(function(d) {
      var similar = (d.data || {}).similar || [];
      if (!similar.length) {
        SS.ui.sheet({title: 'Bai viet tuong tu', html: '<div class="empty-state p-3"><div class="empty-text">Khong tim thay bai tuong tu</div></div>'});
        return;
      }
      var html = '';
      for (var i = 0; i < similar.length; i++) {
        var p = similar[i];
        html += '<div class="card mb-2" style="padding:10px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'">'
          + '<div class="flex items-center gap-2 mb-1"><img src="' + (p.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy"><span class="text-xs font-medium">' + SS.utils.esc(p.fullname) + '</span></div>'
          + '<div class="text-sm">' + SS.utils.esc((p.content || '').substring(0, 100)) + '</div>'
          + '<div class="text-xs text-muted mt-1">❤️ ' + (p.likes_count || 0) + ' 💬 ' + (p.comments_count || 0) + ' · ' + SS.utils.ago(p.created_at) + '</div></div>';
      }
      SS.ui.sheet({title: 'Bai tuong tu (' + similar.length + ')', html: html});
    });
  }
};
