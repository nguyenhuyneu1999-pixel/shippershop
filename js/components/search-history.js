/**
 * ShipperShop Component — Search History
 * Recent searches with clear/remove
 */
window.SS = window.SS || {};

SS.SearchHistory = {
  show: function(onSelect) {
    SS.api.get('/search-history.php').then(function(d) {
      var history = (d.data || {}).history || [];
      if (!history.length) {
        SS.ui.sheet({title: 'Lich su tim kiem', html: '<div class="empty-state p-4"><div class="empty-icon">🔍</div><div class="empty-text">Chua co lich su</div></div>'});
        return;
      }
      var html = '<button class="btn btn-ghost btn-sm text-danger mb-2" onclick="SS.SearchHistory.clear()"><i class="fa-solid fa-trash"></i> Xoa tat ca</button>';
      for (var i = 0; i < history.length; i++) {
        var q = history[i];
        html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light);cursor:pointer">'
          + '<i class="fa-solid fa-clock-rotate-left text-muted" style="width:20px;font-size:12px"></i>'
          + '<span class="flex-1 text-sm" onclick="SS.ui.closeSheet();(' + (onSelect || 'function(){}') + ')(\'' + SS.utils.esc(q).replace(/'/g, '\\x27') + '\')">' + SS.utils.esc(q) + '</span>'
          + '<button class="btn btn-ghost btn-sm" style="padding:2px" onclick="SS.SearchHistory.remove(\'' + SS.utils.esc(q).replace(/'/g, '\\x27') + '\');this.parentElement.remove()"><i class="fa-solid fa-xmark text-muted"></i></button></div>';
      }
      SS.ui.sheet({title: 'Lich su tim kiem (' + history.length + ')', html: html});
    });
  },
  add: function(query) {
    if (!query || query.length < 2 || !SS.store || !SS.store.isLoggedIn()) return;
    SS.api.post('/search-history.php?action=add', {query: query}).catch(function() {});
  },
  remove: function(query) {
    SS.api.post('/search-history.php?action=remove', {query: query}).catch(function() {});
  },
  clear: function() {
    SS.api.post('/search-history.php?action=clear', {}).then(function() {
      SS.ui.toast('Da xoa!', 'success');
      SS.ui.closeSheet();
    });
  }
};
