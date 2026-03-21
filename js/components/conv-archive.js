/**
 * ShipperShop Component — Conversation Archive
 * Archive/unarchive conversations, browse archived
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.ConvArchive = {

  // Toggle archive on a conversation
  toggle: function(convId, onDone) {
    SS.api.post('/conv-archive.php', {conversation_id: convId}).then(function(d) {
      var archived = d.data && d.data.archived;
      SS.ui.toast(d.message || (archived ? 'Đã lưu trữ' : 'Đã bỏ lưu trữ'), 'success', 2000);
      if (onDone) onDone(archived);
    }).catch(function() {
      SS.ui.toast('Lỗi', 'error');
    });
  },

  // Show archived conversations
  showArchived: function() {
    SS.api.get('/conv-archive.php').then(function(d) {
      var data = d.data || {};
      var convs = data.conversations || [];
      if (!convs.length) {
        SS.ui.sheet({title: 'Lưu trữ', html: '<div class="empty-state p-4"><div class="empty-icon">📁</div><div class="empty-text">Không có cuộc trò chuyện lưu trữ</div></div>'});
        return;
      }
      var html = '';
      for (var i = 0; i < convs.length; i++) {
        var c = convs[i];
        html += '<div class="flex items-center gap-3 p-3" style="border-bottom:1px solid var(--border-light)">'
          + '<img src="' + (c.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-sm" loading="lazy">'
          + '<div class="flex-1"><div class="font-medium text-sm">' + SS.utils.esc(c.fullname || '') + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.ago(c.updated_at) + '</div></div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.ConvArchive.toggle(' + c.id + ',function(){document.getElementById(\'arch-' + c.id + '\').remove()})" id="arch-btn-' + c.id + '">Bỏ lưu trữ</button></div>';
      }
      SS.ui.sheet({title: 'Lưu trữ (' + convs.length + ')', html: html});
    });
  },

  // Check if conversation is archived (for filtering)
  isArchived: function(convId, callback) {
    SS.api.get('/conv-archive.php').then(function(d) {
      var ids = (d.data || {}).archived_ids || [];
      callback(ids.indexOf(convId) !== -1);
    }).catch(function() { callback(false); });
  }
};
