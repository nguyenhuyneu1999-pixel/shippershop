/**
 * ShipperShop Component — Smart Reply
 */
window.SS = window.SS || {};

SS.SmartReply = {
  getSuggestions: function(message, callback) {
    var url = '/smart-reply.php' + (message ? '?message=' + encodeURIComponent(message) : '');
    SS.api.get(url).then(function(d) {
      var replies = (d.data || {}).replies || [];
      if (callback) callback(replies);
    });
  },
  renderChips: function(containerId, message, onSelect) {
    SS.SmartReply.getSuggestions(message, function(replies) {
      var el = document.getElementById(containerId);
      if (!el || !replies.length) return;
      var html = '<div class="flex gap-2 flex-wrap" style="padding:6px 0">';
      for (var i = 0; i < replies.length; i++) {
        html += '<button class="chip" style="cursor:pointer;font-size:12px" data-reply="' + SS.utils.esc(replies[i]) + '" onclick="SS.SmartReply._select(this)">' + SS.utils.esc(replies[i]) + '</button>';
      }
      html += '</div>';
      el.innerHTML = html;
      SS.SmartReply._onSelect = onSelect;
    });
  },
  _onSelect: null,
  _select: function(btn) {
    var text = btn.getAttribute('data-reply');
    if (SS.SmartReply._onSelect) SS.SmartReply._onSelect(text);
  }
};
