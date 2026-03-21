/**
 * ShipperShop Component — Report Dialog
 * Report posts/users with reason selection
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.ReportDialog = {

  open: function(postId) {
    if (!SS.store || !SS.store.isLoggedIn()) {
      SS.ui.toast('Đăng nhập để báo cáo', 'warning');
      return;
    }

    // Load reasons
    SS.api.get('/moderation.php?action=reasons').then(function(d) {
      var reasons = d.data || [];
      var html = '<div class="text-sm text-muted mb-3">Chọn lý do báo cáo bài viết #' + postId + '</div>';

      for (var i = 0; i < reasons.length; i++) {
        var r = reasons[i];
        html += '<label class="list-item" style="cursor:pointer">'
          + '<input type="radio" name="rpt-reason" value="' + r.id + '" style="accent-color:var(--primary)">'
          + '<span class="text-sm">' + SS.utils.esc(r.label) + '</span></label>';
      }

      html += '<div class="form-group mt-3"><label class="form-label">Chi tiết (tùy chọn)</label>'
        + '<textarea id="rpt-detail" class="form-textarea" rows="2" placeholder="Mô tả thêm..."></textarea></div>';

      SS.ui.modal({
        title: 'Báo cáo bài viết',
        html: html,
        confirmText: 'Gửi báo cáo',
        danger: true,
        onConfirm: function() {
          var selected = document.querySelector('input[name="rpt-reason"]:checked');
          if (!selected) { SS.ui.toast('Chọn lý do', 'warning'); return; }
          var detail = document.getElementById('rpt-detail').value.trim();

          SS.api.post('/moderation.php?action=report', {
            post_id: postId,
            reason: selected.value,
            detail: detail
          }).then(function(d) {
            SS.ui.toast(d.message || 'Đã báo cáo!', 'success');
          });
        }
      });
    }).catch(function() {
      SS.ui.toast('Lỗi tải form', 'error');
    });
  }
};
