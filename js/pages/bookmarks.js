/**
 * ShipperShop Page — Bookmarks (accessed from profile)
 * Saved posts + collections management
 * Uses: SS.api, SS.PostCard, SS.ui
 */
window.SS = window.SS || {};

SS.Bookmarks = {
  _collectionId: 0,

  load: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/bookmarks.php?action=collections').then(function(d) {
      var data = d.data || {};
      var cols = data.collections || [];
      var totalSaved = data.total_saved || 0;

      var html = '<div class="card mb-3"><div class="card-header flex justify-between items-center">'
        + 'Bài đã lưu <span class="badge badge-primary">' + totalSaved + '</span></div>'
        + '<div class="list-item" onclick="SS.Bookmarks.showPosts(0,\'Tất cả bài lưu\')" style="cursor:pointer">'
        + '<div style="width:40px;height:40px;border-radius:8px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:18px">📌</div>'
        + '<div class="flex-1"><div class="list-title">Tất cả bài lưu</div><div class="list-subtitle">' + totalSaved + ' bài</div></div>'
        + '<i class="fa-solid fa-chevron-right text-muted"></i></div>';

      for (var i = 0; i < cols.length; i++) {
        var c = cols[i];
        html += '<div class="list-item" onclick="SS.Bookmarks.showPosts(' + c.id + ',\'' + SS.utils.esc(c.name).replace(/'/g, '\\x27') + '\')" style="cursor:pointer">'
          + '<div style="width:40px;height:40px;border-radius:8px;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:18px">' + (c.icon || '📁') + '</div>'
          + '<div class="flex-1"><div class="list-title">' + SS.utils.esc(c.name) + '</div><div class="list-subtitle">' + (c.post_count || 0) + ' bài</div></div>'
          + '<button class="btn btn-icon btn-ghost btn-sm" onclick="event.stopPropagation();SS.Bookmarks.deleteCollection(' + c.id + ')" style="color:var(--danger)"><i class="fa-solid fa-trash"></i></button>'
          + '</div>';
      }

      html += '<div class="list-item" onclick="SS.Bookmarks.createCollection()" style="cursor:pointer">'
        + '<div style="width:40px;height:40px;border-radius:8px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--primary)">+</div>'
        + '<div class="flex-1 text-primary font-medium">Tạo collection mới</div></div>'
        + '</div>';

      el.innerHTML = html;
    }).catch(function() {
      el.innerHTML = '<div class="p-4 text-center text-muted">Lỗi tải bookmarks</div>';
    });
  },

  showPosts: function(collectionId, name) {
    var bd = SS.ui.sheet({title: name || 'Bài đã lưu', maxHeight: '90vh'});
    bd.innerHTML = SS.PostCard ? SS.PostCard.skeleton(3) : '';

    var url = '/bookmarks.php?action=posts';
    if (collectionId) url += '&collection_id=' + collectionId;

    SS.api.get(url).then(function(d) {
      var posts = d.data ? d.data.posts : [];
      if (!posts.length) {
        bd.innerHTML = '<div class="empty-state"><div class="empty-text">Chưa có bài lưu</div></div>';
        return;
      }
      bd.innerHTML = SS.PostCard ? SS.PostCard.renderFeed(posts) : '';
    }).catch(function() {
      bd.innerHTML = '<div class="text-center text-muted p-4">Lỗi tải</div>';
    });
  },

  createCollection: function() {
    SS.ui.modal({
      title: 'Tạo collection',
      html: '<div class="form-group"><label class="form-label">Tên</label><input id="bc-name" class="form-input" placeholder="VD: Mẹo giao hàng"></div>',
      confirmText: 'Tạo',
      onConfirm: function() {
        var name = document.getElementById('bc-name').value.trim();
        if (!name) { SS.ui.toast('Nhập tên', 'warning'); return; }
        SS.api.post('/bookmarks.php?action=create_collection', {name: name}).then(function() {
          SS.ui.toast('Đã tạo!', 'success');
        });
      }
    });
  },

  deleteCollection: function(id) {
    SS.ui.confirm('Xóa collection này?', function() {
      SS.api.post('/bookmarks.php?action=delete_collection', {collection_id: id}).then(function() {
        SS.ui.toast('Đã xóa', 'success');
      });
    }, {danger: true});
  },

  // Save post to collection (called from post card menu)
  saveToCollection: function(postId) {
    SS.api.get('/bookmarks.php?action=collections').then(function(d) {
      var cols = (d.data && d.data.collections) || [];
      var html = '<div class="list-item" onclick="SS.Bookmarks._addTo(0,' + postId + ')" style="cursor:pointer">'
        + '<div style="font-size:18px">📌</div><div class="flex-1">Lưu chung</div></div>';
      for (var i = 0; i < cols.length; i++) {
        html += '<div class="list-item" onclick="SS.Bookmarks._addTo(' + cols[i].id + ',' + postId + ')" style="cursor:pointer">'
          + '<div style="font-size:18px">' + (cols[i].icon || '📁') + '</div>'
          + '<div class="flex-1">' + SS.utils.esc(cols[i].name) + '</div></div>';
      }
      SS.ui.sheet({title: 'Lưu vào collection', html: html});
    });
  },

  _addTo: function(colId, postId) {
    SS.ui.closeSheet();
    if (colId) {
      SS.api.post('/bookmarks.php?action=add_to_collection', {collection_id: colId, post_id: postId}).then(function() {
        SS.ui.toast('Đã lưu vào collection!', 'success');
      });
    } else {
      SS.api.post('/posts.php?action=save', {post_id: postId}).then(function() {
        SS.ui.toast('Đã lưu!', 'success');
      });
    }
  }
};
