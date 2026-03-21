/**
 * ShipperShop Component — Message Polling
 * Polls for new messages, updates unread badge, plays sound
 * 5s on messages page, 30s elsewhere
 */
window.SS = window.SS || {};

SS.MsgPoll = {
  _timer: null,
  _lastTime: null,
  _convId: 0,
  _onMessage: null,

  init: function(opts) {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    opts = opts || {};
    SS.MsgPoll._convId = opts.conversationId || 0;
    SS.MsgPoll._onMessage = opts.onMessage || null;
    SS.MsgPoll._lastTime = new Date().toISOString();

    var interval = opts.fast ? 5000 : 30000;
    SS.MsgPoll.poll();
    SS.MsgPoll._timer = setInterval(SS.MsgPoll.poll, interval);

    // Pause when tab hidden
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        clearInterval(SS.MsgPoll._timer);
      } else {
        SS.MsgPoll.poll();
        SS.MsgPoll._timer = setInterval(SS.MsgPoll.poll, interval);
      }
    });
  },

  poll: function() {
    if (!SS.store || !SS.store.isLoggedIn() || document.hidden) return;

    var url = '/msg-poll.php?since=' + encodeURIComponent(SS.MsgPoll._lastTime);
    if (SS.MsgPoll._convId) url += '&conversation_id=' + SS.MsgPoll._convId;

    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      SS.MsgPoll._lastTime = data.server_time || new Date().toISOString();

      // Update message badges
      var totalUnread = data.unread_total || 0;
      var badges = document.querySelectorAll('.ss-msg-badge');
      for (var i = 0; i < badges.length; i++) {
        if (totalUnread > 0) {
          badges[i].textContent = totalUnread > 99 ? '99+' : totalUnread;
          badges[i].style.display = 'flex';
        } else {
          badges[i].style.display = 'none';
        }
      }

      // New messages callback
      var msgs = data.messages || [];
      if (msgs.length > 0 && SS.MsgPoll._onMessage) {
        for (var j = 0; j < msgs.length; j++) {
          SS.MsgPoll._onMessage(msgs[j]);
        }
      }

      // Toast for new messages when not on messages page
      if (msgs.length > 0 && !SS.MsgPoll._convId && SS.ui) {
        var last = msgs[msgs.length - 1];
        SS.ui.toast((last.sender_name || 'Tin nhắn mới') + ': ' + (last.content || '').substring(0, 50), 'info', 4000);
      }

      // Typing indicators
      var typers = data.typing || [];
      var typingEl = document.getElementById('ss-typing-indicator');
      if (typingEl) {
        if (typers.length > 0) {
          var names = typers.map(function(t) { return t.fullname; }).join(', ');
          typingEl.textContent = names + ' đang nhập...';
          typingEl.style.display = 'block';
        } else {
          typingEl.style.display = 'none';
        }
      }
    }).catch(function() {});
  },

  stop: function() {
    if (SS.MsgPoll._timer) {
      clearInterval(SS.MsgPoll._timer);
      SS.MsgPoll._timer = null;
    }
  },

  setConversation: function(convId) {
    SS.MsgPoll._convId = convId;
    SS.MsgPoll.poll();
  }
};
