/**
 * ShipperShop Component — Message Forward
 * Forward a message to another conversation or user
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.MsgForward = {

  open: function(messageId) {
    if (!SS.store || !SS.store.isLoggedIn()) {
      SS.ui.toast('Đăng nhập để chuyển tiếp', 'warning'); return;
    }

    SS.api.get('/msg-forward.php').then(function(d) {
      var convs = d.data || [];
      var html = '<div class="text-sm text-muted mb-3">Chọn cuộc trò chuyện để chuyển tiếp</div>';

      if (!convs.length) {
        html += '<div class="empty-state p-3"><div class="empty-text">Không có cuộc trò chuyện nào</div></div>';
      }

      for (var i = 0; i < convs.length; i++) {
        var c = convs[i];
        html += '<div class="list-item" style="cursor:pointer" onclick="SS.MsgForward._forward(' + messageId + ',' + c.id + ')">'
          + '<img src="' + (c.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-sm" loading="lazy">'
          + '<div class="flex-1"><div class="font-medium text-sm">' + SS.utils.esc(c.fullname) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.ago(c.updated_at) + '</div></div>'
          + '<i class="fa-solid fa-share text-muted" style="font-size:12px"></i></div>';
      }

      SS.ui.sheet({title: 'Chuyển tiếp tin nhắn', html: html});
    });
  },

  _forward: function(messageId, convId) {
    SS.ui.closeSheet();
    SS.api.post('/msg-forward.php', {message_id: messageId, conversation_id: convId}).then(function(d) {
      SS.ui.toast(d.message || 'Đã chuyển tiếp!', 'success');
    }).catch(function(e) {
      SS.ui.toast(e.message || 'Lỗi', 'error');
    });
  }
};
