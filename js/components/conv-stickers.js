/**
 * ShipperShop Component — Conversation Stickers
 */
window.SS = window.SS || {};

SS.ConvStickers = {
  show: function(callback) {
    SS.api.get('/conv-stickers.php').then(function(d) {
      var packs = (d.data || {}).packs || [];
      SS.ConvStickers._cb = callback;
      var html = '';
      for (var p = 0; p < packs.length; p++) {
        var pack = packs[p];
        html += '<div class="text-sm font-bold mb-1 mt-2">' + SS.utils.esc(pack.name) + '</div>'
          + '<div style="display:grid;grid-template-columns:repeat(10,1fr);gap:2px;margin-bottom:8px">';
        for (var s = 0; s < pack.stickers.length; s++) {
          html += '<div style="font-size:22px;text-align:center;cursor:pointer;padding:4px;border-radius:6px" onclick="SS.ConvStickers._pick(\'' + pack.stickers[s] + '\')" onmouseover="this.style.background=\'var(--border-light)\'" onmouseout="this.style.background=\'\'">' + pack.stickers[s] + '</div>';
        }
        html += '</div>';
      }
      SS.ui.sheet({title: '🎨 Stickers (' + (d.data || {}).total_stickers + ')', html: html});
    });
  },
  _cb: null,
  _pick: function(sticker) { SS.ui.closeSheet(); if (SS.ConvStickers._cb) SS.ConvStickers._cb(sticker); }
};
