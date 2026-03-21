/**
 * ShipperShop Component — Post Edit
 * Edit post content modal + edit history
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.PostEdit = {

  open: function(postId) {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    // Load current post
    SS.api.get('/posts.php?id=' + postId).then(function(d) {
      var post = d.data;
      if (!post) { SS.ui.toast('Bài viết không tồn tại', 'error'); return; }
      if (parseInt(post.user_id) !== SS.store.userId()) { SS.ui.toast('Bạn không có quyền sửa', 'error'); return; }

      SS.ui.modal({
        title: 'Sửa bài viết',
        html: '<textarea id="pe-content" class="form-textarea" rows="6" style="font-size:15px;line-height:1.6">' + SS.utils.esc(post.content || '') + '</textarea>'
          + '<div class="text-xs text-muted mt-2">Bài đã sửa sẽ hiển thị "(đã chỉnh sửa)"</div>',
        confirmText: 'Lưu thay đổi',
        onConfirm: function() {
          var content = document.getElementById('pe-content').value.trim();
          if (!content || content.length < 3) { SS.ui.toast('Nội dung tối thiểu 3 ký tự', 'warning'); return; }
          SS.api.post('/posts.php?action=edit', {post_id: postId, content: content}).then(function() {
            SS.ui.toast('Đã sửa bài viết!', 'success');
            // Update content in DOM if visible
            var cards = document.querySelectorAll('[data-post-id="' + postId + '"] .post-content, #pd-post .post-content');
            for (var i = 0; i < cards.length; i++) {
              cards[i].textContent = content;
            }
          });
        }
      });
    });
  },

  // Show edit history
  showHistory: function(postId) {
    SS.api.get('/posts.php?action=edit_history&post_id=' + postId).then(function(d) {
      var edits = d.data || [];
      if (!edits.length) { SS.ui.toast('Chưa có lịch sử chỉnh sửa', 'info'); return; }
      var html = '';
      for (var i = 0; i < edits.length; i++) {
        var e = edits[i];
        html += '<div class="list-item" style="align-items:flex-start">'
          + '<div style="width:24px;height:24px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;color:var(--primary)">' + (i + 1) + '</div>'
          + '<div class="flex-1"><div class="text-xs text-muted">' + SS.utils.formatDateTime(e.created_at) + '</div>'
          + '<div class="text-sm mt-1" style="white-space:pre-wrap;line-height:1.5">' + SS.utils.esc(e.new_content || '') + '</div></div></div>';
      }
      SS.ui.sheet({title: 'Lịch sử chỉnh sửa', html: html});
    }).catch(function() { SS.ui.toast('Lỗi tải', 'error'); });
  }
};
