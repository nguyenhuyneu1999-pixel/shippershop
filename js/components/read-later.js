/**
 * ShipperShop Component — Read Later
 * Save posts for later reading
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.ReadLater = {

  toggle: function(postId, btn) {
    SS.api.post('/read-later.php', {post_id: postId}).then(function(d) {
      var saved = d.data && d.data.saved;
      SS.ui.toast(d.message || '', saved ? 'success' : 'info', 2000);
      if (btn) {
        btn.innerHTML = saved ? '<i class="fa-solid fa-bookmark"></i>' : '<i class="fa-regular fa-bookmark"></i>';
      }
    });
  },

  showList: function() {
    SS.api.get('/read-later.php').then(function(d) {
      var posts = (d.data || {}).posts || [];
      if (!posts.length) {
        SS.ui.sheet({title: 'Doc sau', html: '<div class="empty-state p-4"><div class="empty-icon">📖</div><div class="empty-text">Chua co bai nao</div></div>'});
        return;
      }
      var html = '';
      for (var i = 0; i < posts.length; i++) {
        var p = posts[i];
        html += '<div class="card mb-2" style="padding:10px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'">'
          + '<div class="flex items-center gap-2 mb-1"><img src="' + (p.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy"><span class="text-xs">' + SS.utils.esc(p.fullname) + '</span></div>'
          + '<div class="text-sm">' + SS.utils.esc((p.content || '').substring(0, 100)) + '</div>'
          + '<div class="text-xs text-muted mt-1">' + SS.utils.ago(p.created_at) + '</div></div>';
      }
      SS.ui.sheet({title: 'Doc sau (' + posts.length + ')', html: html});
    });
  }
};
