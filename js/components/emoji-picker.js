/**
 * ShipperShop Component — Frequently used emoji grid picker
 */
window.SS = window.SS || {};

SS.EmojiPicker = {
  _emojis: ['👍','❤️','😂','😮','😢','😡','🔥','💪','🙏','👏','🎉','⭐','📦','🚛','💰','✅'],
  show: function(callback) {
    var html = '<div style="display:grid;grid-template-columns:repeat(8,1fr);gap:4px">';
    for (var i = 0; i < SS.EmojiPicker._emojis.length; i++) {
      html += '<div style="font-size:24px;text-align:center;cursor:pointer;padding:8px;border-radius:8px" onclick="SS.EmojiPicker._pick(' + i + ')" onmouseover="this.style.background=\'var(--border-light)\'" onmouseout="this.style.background=\'\'"">' + SS.EmojiPicker._emojis[i] + '</div>';
    }
    html += '</div>';
    SS.EmojiPicker._cb = callback;
    SS.ui.sheet({title: 'Emoji', html: html});
  },
  _cb: null,
  _pick: function(i) { SS.ui.closeSheet(); if (SS.EmojiPicker._cb) SS.EmojiPicker._cb(SS.EmojiPicker._emojis[i]); }
};
