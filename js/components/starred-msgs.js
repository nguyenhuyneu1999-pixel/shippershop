/**
 * ShipperShop Component — Starred Messages
 * Star important messages for quick access
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.StarredMsgs = {

  toggle: function(messageId, conversationId, btn) {
    SS.api.post('/starred-msgs.php', {message_id: messageId, conversation_id: conversationId}).then(function(d) {
      var starred = d.data && d.data.starred;
      if (btn) {
        btn.style.color = starred ? 'var(--warning)' : 'var(--text-muted)';
        btn.innerHTML = starred ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
      }
      SS.ui.toast(d.message || '', 'success', 1500);
    });
  },

  showList: function(conversationId) {
    var url = '/starred-msgs.php' + (conversationId ? '?conversation_id=' + conversationId : '');
    SS.api.get(url).then(function(d) {
      var msgs = (d.data || {}).messages || [];
      if (!msgs.length) {
        SS.ui.sheet({title: 'Tin nhan quan trong', html: '<div class="empty-state p-4"><div class="empty-icon">⭐</div><div class="empty-text">Chua co tin nhan nao</div></div>'});
        return;
      }
      var html = '';
      for (var i = 0; i < msgs.length; i++) {
        var m = msgs[i];
        html += '<div class="p-3" style="border-bottom:1px solid var(--border-light)">'
          + '<div class="flex justify-between items-center mb-1"><span class="text-xs font-medium">' + SS.utils.esc(m.fullname || '') + '</span><span class="text-xs text-muted">' + SS.utils.ago(m.created_at) + '</span></div>'
          + '<div class="text-sm">' + SS.utils.esc(m.content || '') + '</div></div>';
      }
      SS.ui.sheet({title: 'Tin nhan quan trong (' + msgs.length + ')', html: html});
    });
  },

  // Render star button for a message
  renderBtn: function(messageId, conversationId) {
    return '<button class="btn btn-ghost btn-sm" style="color:var(--text-muted);padding:2px 4px" onclick="SS.StarredMsgs.toggle(' + messageId + ',' + conversationId + ',this)"><i class="fa-regular fa-star"></i></button>';
  }
};
