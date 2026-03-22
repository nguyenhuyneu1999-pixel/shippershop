/**
 * ShipperShop Component — Global keyboard shortcut handler
 */
window.SS = window.SS || {};

SS.KeyboardShortcuts = {
  _map: {},
  register: function(key, callback, desc) {
    SS.KeyboardShortcuts._map[key.toLowerCase()] = {fn: callback, desc: desc || key};
  },
  init: function() {
    document.addEventListener('keydown', function(e) {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
      var key = (e.ctrlKey ? 'ctrl+' : '') + (e.shiftKey ? 'shift+' : '') + e.key.toLowerCase();
      var handler = SS.KeyboardShortcuts._map[key];
      if (handler) { e.preventDefault(); handler.fn(); }
    });
  },
  showHelp: function() {
    var html = '';
    for (var key in SS.KeyboardShortcuts._map) {
      html += '<div class="flex justify-between p-1" style="border-bottom:1px solid var(--border-light)"><kbd style="background:var(--border-light);padding:2px 6px;border-radius:4px;font-size:11px">' + key + '</kbd><span class="text-sm">' + SS.utils.esc(SS.KeyboardShortcuts._map[key].desc) + '</span></div>';
    }
    SS.ui.sheet({title: 'Phim tat', html: html || '<div class="text-muted text-center p-3">Chua co phim tat</div>'});
  }
};
