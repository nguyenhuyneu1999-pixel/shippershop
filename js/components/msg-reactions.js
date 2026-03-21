/**
 * ShipperShop Component — Message Reactions
 * React to messages with emoji (like Messenger/iMessage)
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.MsgReactions = {
  _emojis: {like:'👍',love:'❤️',haha:'😂',wow:'😮',sad:'😢',angry:'😡'},

  // Show reaction picker on a message
  show: function(messageId, anchorEl) {
    var rect = anchorEl.getBoundingClientRect();
    var popup = document.createElement('div');
    popup.id = 'ss-msg-react-picker';
    popup.style.cssText = 'position:fixed;z-index:3000;background:var(--card);border-radius:24px;box-shadow:0 4px 16px rgba(0,0,0,.15);padding:4px 6px;display:flex;gap:1px;left:' + Math.max(8, rect.left) + 'px;bottom:' + (window.innerHeight - rect.top + 4) + 'px';

    var keys = Object.keys(SS.MsgReactions._emojis);
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      popup.innerHTML += '<button style="font-size:22px;padding:3px 5px;border:none;background:none;cursor:pointer;border-radius:50%;transition:transform .1s" onmouseenter="this.style.transform=\'scale(1.3)\'" onmouseleave="this.style.transform=\'scale(1)\'" onclick="SS.MsgReactions.react(' + messageId + ',\'' + k + '\')">' + SS.MsgReactions._emojis[k] + '</button>';
    }

    // Remove old picker
    var old = document.getElementById('ss-msg-react-picker');
    if (old) old.remove();
    document.body.appendChild(popup);

    // Auto-close
    setTimeout(function() {
      document.addEventListener('click', function handler(e) {
        if (!popup.contains(e.target)) { popup.remove(); document.removeEventListener('click', handler); }
      });
    }, 0);
  },

  // Send reaction
  react: function(messageId, reaction) {
    var picker = document.getElementById('ss-msg-react-picker');
    if (picker) picker.remove();
    SS.api.post('/msg-reactions.php', {message_id: messageId, reaction: reaction}).then(function(d) {
      // Update UI
      var el = document.getElementById('msg-react-' + messageId);
      if (el && d.data && d.data.reacted) {
        el.innerHTML = SS.MsgReactions._emojis[reaction] || '';
        el.style.display = 'inline';
      } else if (el) {
        el.innerHTML = '';
        el.style.display = 'none';
      }
    }).catch(function() {});
  },

  // Render reaction display under a message
  renderReactions: function(messageId) {
    return '<span id="msg-react-' + messageId + '" style="display:none;font-size:14px;cursor:pointer" onclick="SS.MsgReactions.show(' + messageId + ',this)"></span>';
  }
};
