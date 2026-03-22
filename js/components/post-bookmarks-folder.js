/**
 * ShipperShop Component — Bookmark Folders
 */
window.SS = window.SS || {};

SS.BookmarkFolders = {
  show: function() {
    SS.api.get('/post-bookmarks-folder.php').then(function(d) {
      var folders = (d.data || {}).folders || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.BookmarkFolders.create()"><i class="fa-solid fa-plus"></i> Thu muc moi</button>';
      if (!folders.length) html += '<div class="empty-state p-3"><div class="empty-icon">📁</div><div class="empty-text">Chua co thu muc</div></div>';
      for (var i = 0; i < folders.length; i++) {
        var f = folders[i];
        html += '<div class="card mb-2" style="padding:10px;cursor:pointer"><div class="flex justify-between"><span class="font-bold text-sm">' + (f.icon || '📁') + ' ' + SS.utils.esc(f.name) + '</span><span class="text-xs text-muted">' + (f.post_count || 0) + ' bai</span></div></div>';
      }
      SS.ui.sheet({title: '📁 Thu muc luu', html: html});
    });
  },
  create: function() {
    SS.ui.modal({title: 'Tao thu muc', html: '<input id="bf-name" class="form-input" placeholder="Ten thu muc">', confirmText: 'Tao',
      onConfirm: function() { SS.api.post('/post-bookmarks-folder.php', {name: document.getElementById('bf-name').value}).then(function(d) { SS.ui.toast('OK', 'success'); SS.BookmarkFolders.show(); }); }
    });
  }
};
