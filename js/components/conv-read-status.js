/**
 * ShipperShop Component — Conversation Read Status
 * Show unread count badge + mark as read
 */
window.SS = window.SS || {};

SS.ConvReadStatus = {
  getUnread: function(conversationId, callback) {
    SS.api.get('/conv-read-status.php?conversation_id=' + conversationId).then(function(d) {
      if (callback) callback((d.data || {}).unread || 0);
    }).catch(function() { if (callback) callback(0); });
  },

  markRead: function(conversationId, upToId) {
    var payload = {conversation_id: conversationId};
    if (upToId) payload.up_to_message_id = upToId;
    SS.api.post('/conv-read-status.php', payload).catch(function() {});
  },

  // Render unread badge
  renderBadge: function(conversationId, containerId) {
    SS.ConvReadStatus.getUnread(conversationId, function(count) {
      var el = document.getElementById(containerId);
      if (!el) return;
      if (count > 0) {
        el.innerHTML = '<span class="badge badge-danger" style="font-size:10px;min-width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;padding:0 4px">' + (count > 99 ? '99+' : count) + '</span>';
      } else {
        el.innerHTML = '';
      }
    });
  }
};
