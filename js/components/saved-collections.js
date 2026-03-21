/**
 * ShipperShop Component — Saved Collections UI
 * Browse saved posts, organize into collections
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.SavedCollections = {

  // Show collections list
  show: function() {
    SS.api.get('/saved-collections.php').then(function(d) {
      var data = d.data || {};
      var colls = data.collections || [];
      var unsorted = data.unsorted_count || 0;

      var html = '<div class="list-item" style="cursor:pointer" onclick="SS.SavedCollections._showPosts(0,\'Chưa phân loại\')">'
        + '<i class="fa-solid fa-bookmark" style="color:var(--primary);width:24px;font-size:18px"></i>'
        + '<div class="flex-1"><div class="font-medium text-sm">Chưa phân loại</div></div>'
        + '<span class="badge">' + unsorted + '</span></div>';

      for (var i = 0; i < colls.length; i++) {
        var c = colls[i];
        html += '<div class="list-item" style="cursor:pointer" onclick="SS.SavedCollections._showPosts(' + c.id + ',\'' + SS.utils.esc(c.name).replace(/'/g, '\\x27') + '\')">'
          + '<i class="fa-solid fa-folder" style="color:var(--warning);width:24px;font-size:18px"></i>'
          + '<div class="flex-1"><div class="font-medium text-sm">' + SS.utils.esc(c.name) + '</div></div>'
          + '<span class="badge">' + (c.post_count || 0) + '</span></div>';
      }

      html += '<div class="divider"></div>'
        + '<div class="list-item" style="cursor:pointer;color:var(--primary)" onclick="SS.SavedCollections._create()">'
        + '<i class="fa-solid fa-plus" style="width:24px"></i>'
        + '<div class="flex-1 font-medium text-sm">Tạo bộ sưu tập mới</div></div>';

      SS.ui.sheet({title: 'Đã lưu', html: html});
    });
  },

  _showPosts: function(collId, name) {
    SS.ui.closeSheet();
    SS.api.get('/saved-collections.php?action=posts&collection_id=' + collId).then(function(d) {
      var posts = (d.data || {}).posts || [];
      var html = '';
      if (!posts.length) {
        html = '<div class="empty-state p-4"><div class="empty-text">Chưa có bài lưu</div></div>';
      }
      for (var i = 0; i < posts.length; i++) {
        var p = posts[i];
        html += '<div class="card mb-2" style="padding:12px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'">'
          + '<div class="flex items-center gap-2 mb-1">'
          + '<img src="' + (p.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:24px;height:24px;border-radius:50%" loading="lazy">'
          + '<span class="text-xs font-medium">' + SS.utils.esc(p.fullname || '') + '</span>'
          + '<span class="text-xs text-muted">' + SS.utils.ago(p.created_at) + '</span></div>'
          + '<div class="text-sm" style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">' + SS.utils.esc((p.content || '').substring(0, 150)) + '</div>'
          + '<div class="flex gap-3 text-xs text-muted mt-1">'
          + '<span>❤️ ' + (p.likes_count || 0) + '</span>'
          + '<span>💬 ' + (p.comments_count || 0) + '</span></div></div>';
      }
      SS.ui.sheet({title: name, html: html});
    });
  },

  _create: function() {
    SS.ui.closeSheet();
    SS.ui.prompt('Tên bộ sưu tập', function(name) {
      if (!name) return;
      SS.api.post('/saved-collections.php?action=create', {name: name}).then(function(d) {
        SS.ui.toast('Đã tạo: ' + name, 'success');
      });
    });
  },

  // Save post to collection (picker)
  saveToCollection: function(postId) {
    SS.api.get('/saved-collections.php').then(function(d) {
      var colls = (d.data || {}).collections || [];
      var html = '';
      for (var i = 0; i < colls.length; i++) {
        var c = colls[i];
        html += '<div class="list-item" style="cursor:pointer" onclick="SS.SavedCollections._addTo(' + c.id + ',' + postId + ')">'
          + '<i class="fa-solid fa-folder" style="color:var(--warning);width:24px"></i>'
          + '<div class="flex-1 text-sm font-medium">' + SS.utils.esc(c.name) + '</div></div>';
      }
      html += '<div class="divider"></div><div class="list-item" style="cursor:pointer;color:var(--primary)" onclick="SS.SavedCollections._createAndAdd(' + postId + ')">'
        + '<i class="fa-solid fa-plus" style="width:24px"></i>'
        + '<div class="flex-1 font-medium text-sm">Tạo mới</div></div>';
      SS.ui.sheet({title: 'Lưu vào bộ sưu tập', html: html});
    });
  },

  _addTo: function(collId, postId) {
    SS.ui.closeSheet();
    SS.api.post('/saved-collections.php?action=add', {collection_id: collId, post_id: postId}).then(function() {
      SS.ui.toast('Đã lưu vào bộ sưu tập!', 'success');
    });
  },

  _createAndAdd: function(postId) {
    SS.ui.closeSheet();
    SS.ui.prompt('Tên bộ sưu tập', function(name) {
      if (!name) return;
      SS.api.post('/saved-collections.php?action=create', {name: name}).then(function(d) {
        var collId = (d.data || {}).id;
        if (collId) SS.SavedCollections._addTo(collId, postId);
      });
    });
  }
};
