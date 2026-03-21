/**
 * ShipperShop Page — Messages Helper
 * Bridges existing messages.html with v2 API for new features
 * Uses: SS.api, SS.ui, SS.EmojiPicker
 */
window.SS = window.SS || {};

SS.Messages = {

  // Unread count for nav badge
  loadUnreadCount: function() {
    SS.api.get('/messages.php?action=pending_count').then(function(d) {
      var count = d.count || d.data || 0;
      var badge = document.getElementById('msg-unread-badge');
      if (badge) {
        badge.textContent = count > 0 ? (count > 99 ? '99+' : count) : '';
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
      }
    }).catch(function() {});
  },

  // Search messages in current conversation
  searchMessages: function(query, conversationId) {
    if (!query || query.length < 2) return Promise.resolve([]);
    return SS.api.get('/messages.php?action=search_messages&q=' + encodeURIComponent(query) + '&conversation_id=' + conversationId).then(function(d) {
      return d.data || [];
    });
  },

  // Get media gallery for conversation
  loadMedia: function(conversationId, containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '<div class="flex justify-center p-4"><div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%"></div></div>';

    SS.api.get('/messages.php?action=media&conversation_id=' + conversationId + '&type=image').then(function(d) {
      var images = d.data || [];
      if (!images.length) {
        el.innerHTML = '<div class="text-center text-muted text-sm p-4">Chưa có ảnh</div>';
        return;
      }
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:4px;padding:8px">';
      for (var i = 0; i < images.length; i++) {
        var url = images[i].content || images[i].url || '';
        if (url) {
          html += '<div style="aspect-ratio:1;overflow:hidden;border-radius:8px;cursor:pointer" onclick="SS.ImageViewer&&SS.ImageViewer.open(\'' + url + '\')"><img src="' + url + '" style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>';
        }
      }
      html += '</div>';
      el.innerHTML = html;
    }).catch(function() {
      el.innerHTML = '<div class="text-center text-muted text-sm p-4">Lỗi</div>';
    });
  },

  // Format last message time for conversation list
  formatMessageTime: function(dateStr) {
    if (!dateStr) return '';
    var now = new Date();
    var d = new Date(dateStr);
    var diff = Math.floor((now - d) / 1000);
    if (diff < 60) return 'Vừa xong';
    if (diff < 3600) return Math.floor(diff / 60) + 'p';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    if (diff < 604800) {
      var days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
      return days[d.getDay()];
    }
    return String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0');
  },

  // Typing indicator
  _typingTimer: null,

  showTyping: function(conversationId) {
    // Visual only — no server call (real-time via Firebase if needed)
    var el = document.getElementById('typing-indicator');
    if (el) {
      el.style.display = 'block';
      clearTimeout(SS.Messages._typingTimer);
      SS.Messages._typingTimer = setTimeout(function() {
        el.style.display = 'none';
      }, 3000);
    }
  }
};
