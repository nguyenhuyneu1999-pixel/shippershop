/**
 * ShipperShop Component — Typing Indicator
 * Sends typing signal every 3s while typing, shows "typing..." bubble
 * Uses: SS.api
 */
window.SS = window.SS || {};

SS.TypingIndicator = {
  _timers: {},
  _pollTimers: {},

  // Call when user is typing in a conversation
  send: function(conversationId) {
    if (!conversationId || !SS.store || !SS.store.isLoggedIn()) return;
    var key = 'typing_' + conversationId;
    // Debounce: only send every 3s
    if (SS.TypingIndicator._timers[key]) return;
    SS.TypingIndicator._timers[key] = true;
    SS.api.post('/messages.php?action=typing', {conversation_id: conversationId}).catch(function() {});
    setTimeout(function() { delete SS.TypingIndicator._timers[key]; }, 3000);
  },

  // Start polling for typing status in a conversation
  startPolling: function(conversationId, containerId) {
    if (!conversationId) return;
    var el = document.getElementById(containerId);
    if (!el) return;
    el.style.cssText = 'padding:4px 16px;font-size:12px;color:var(--primary);font-style:italic;min-height:20px';

    var poll = function() {
      SS.api.get('/messages.php?action=typing_status&conversation_id=' + conversationId).then(function(d) {
        var typing = (d.data && d.data.typing) || [];
        if (typing.length) {
          var names = typing.map(function(u) { return u.fullname.split(' ').pop(); }).join(', ');
          el.innerHTML = '<span style="display:inline-flex;align-items:center;gap:4px">' + SS.utils.esc(names) + ' đang nhập<span class="typing-dots"><span>.</span><span>.</span><span>.</span></span></span>';
        } else {
          el.innerHTML = '';
        }
      }).catch(function() {});
    };

    poll();
    SS.TypingIndicator._pollTimers[conversationId] = setInterval(poll, 4000);
  },

  stopPolling: function(conversationId) {
    if (SS.TypingIndicator._pollTimers[conversationId]) {
      clearInterval(SS.TypingIndicator._pollTimers[conversationId]);
      delete SS.TypingIndicator._pollTimers[conversationId];
    }
  }
};
