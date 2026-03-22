/**
 * ShipperShop Component — Conversation Bookmarks
 */
window.SS = window.SS || {};

SS.ConvBookmarks = {
  show: function() {
    SS.api.get('/conv-bookmarks.php').then(function(d) {
      var bookmarks = (d.data || {}).bookmarks || [];
      if (!bookmarks.length) {
        SS.ui.sheet({title: 'Bookmark', html: '<div class="empty-state p-4"><div class="empty-icon">🔖</div><div class="empty-text">Chua co bookmark</div></div>'});
        return;
      }
      var html = '';
      for (var i = 0; i < bookmarks.length; i++) {
        var b = bookmarks[i];
        html += '<div class="flex gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<div class="flex-1"><div class="text-sm">' + SS.utils.esc((b.content || '').substring(0, 80)) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(b.sender || '') + ' · ' + SS.utils.ago(b.msg_date) + (b.note ? ' · ' + SS.utils.esc(b.note) : '') + '</div></div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.ConvBookmarks.remove(' + b.id + ')"><i class="fa-solid fa-xmark text-muted" style="font-size:10px"></i></button></div>';
      }
      SS.ui.sheet({title: '🔖 Bookmark (' + bookmarks.length + ')', html: html});
    });
  },
  add: function(messageId, conversationId) {
    SS.api.post('/conv-bookmarks.php', {message_id: messageId, conversation_id: conversationId}).then(function(d) {
      SS.ui.toast(d.message || 'Da luu!', 'success');
    });
  },
  remove: function(bmId) {
    SS.api.post('/conv-bookmarks.php?action=remove', {bookmark_id: bmId}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.ConvBookmarks.show(); });
  }
};
