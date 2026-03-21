/**
 * ShipperShop Component — Collections UI
 * Manage post collections (Pinterest-style boards)
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.CollectionsUI = {

  show: function() {
    SS.api.get('/collections.php').then(function(d) {
      var colls = d.data || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.CollectionsUI.create()"><i class="fa-solid fa-plus"></i> Tạo bộ sưu tập</button>';

      if (!colls.length) {
        html += '<div class="empty-state p-4"><div class="empty-icon">📁</div><div class="empty-text">Chưa có bộ sưu tập nào</div></div>';
      } else {
        for (var i = 0; i < colls.length; i++) {
          var c = colls[i];
          html += '<div class="card mb-2 card-hover" style="cursor:pointer" onclick="SS.CollectionsUI.open(' + c.id + ')">'
            + '<div class="card-body" style="padding:12px">'
            + '<div class="flex justify-between items-center">'
            + '<div><div class="font-bold text-sm">' + SS.utils.esc(c.name) + '</div>'
            + '<div class="text-xs text-muted">' + (c.item_count || 0) + ' bài · ' + (c.is_public ? 'Công khai' : 'Riêng tư') + '</div></div>'
            + '<i class="fa-solid fa-chevron-right text-muted"></i></div></div></div>';
        }
      }
      SS.ui.sheet({title: 'Bộ sưu tập (' + colls.length + ')', html: html});
    });
  },

  open: function(collId) {
    SS.api.get('/collections.php?action=items&collection_id=' + collId).then(function(d) {
      var data = d.data || {};
      var coll = data.collection || {};
      var items = data.items || [];
      var html = '';
      if (!items.length) {
        html = '<div class="empty-state p-4"><div class="empty-text">Bộ sưu tập trống</div></div>';
      } else {
        for (var i = 0; i < items.length; i++) {
          var p = items[i];
          html += '<div class="card mb-2" style="padding:10px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.post_id + '\'">'
            + '<div class="flex items-center gap-2 mb-1"><img src="' + (p.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:20px;height:20px;border-radius:50%" loading="lazy"><span class="text-xs font-medium">' + SS.utils.esc(p.fullname) + '</span></div>'
            + '<div class="text-sm">' + SS.utils.esc((p.content || '').substring(0, 100)) + '</div>'
            + '<div class="text-xs text-muted mt-1">❤️ ' + (p.likes_count || 0) + ' · 💬 ' + (p.comments_count || 0) + '</div></div>';
        }
      }
      html += '<div class="mt-3"><button class="btn btn-danger btn-sm" onclick="SS.CollectionsUI._delete(' + collId + ')"><i class="fa-solid fa-trash"></i> Xóa bộ sưu tập</button></div>';
      SS.ui.sheet({title: SS.utils.esc(coll.name || ''), html: html});
    });
  },

  create: function() {
    SS.ui.modal({
      title: 'Tạo bộ sưu tập',
      html: '<div class="form-group"><label class="form-label">Tên</label><input id="coll-name" class="form-input" placeholder="VD: Mẹo giao hàng" maxlength="100"></div>'
        + '<div class="form-group"><label class="form-label">Mô tả</label><textarea id="coll-desc" class="form-textarea" rows="2" placeholder="Mô tả ngắn..." maxlength="300"></textarea></div>',
      confirmText: 'Tạo',
      onConfirm: function() {
        var name = document.getElementById('coll-name').value.trim();
        if (!name) { SS.ui.toast('Nhập tên', 'warning'); return; }
        SS.api.post('/collections.php?action=create', {name: name, description: document.getElementById('coll-desc').value.trim()}).then(function() {
          SS.ui.toast('Đã tạo!', 'success');
          SS.CollectionsUI.show();
        });
      }
    });
  },

  // Add post to collection (quick picker)
  addPost: function(postId) {
    SS.api.get('/collections.php').then(function(d) {
      var colls = d.data || [];
      if (!colls.length) { SS.CollectionsUI.create(); return; }
      var html = '';
      for (var i = 0; i < colls.length; i++) {
        var c = colls[i];
        html += '<div class="list-item" style="cursor:pointer" onclick="SS.CollectionsUI._addTo(' + c.id + ',' + postId + ')">'
          + '<i class="fa-solid fa-folder" style="color:var(--primary)"></i>'
          + '<div class="flex-1 text-sm font-medium">' + SS.utils.esc(c.name) + ' (' + (c.item_count || 0) + ')</div></div>';
      }
      SS.ui.sheet({title: 'Thêm vào bộ sưu tập', html: html});
    });
  },

  _addTo: function(collId, postId) {
    SS.ui.closeSheet();
    SS.api.post('/collections.php?action=add', {collection_id: collId, post_id: postId}).then(function() {
      SS.ui.toast('Đã thêm!', 'success');
    }).catch(function(e) { SS.ui.toast(e.message || 'Lỗi', 'error'); });
  },

  _delete: function(collId) {
    SS.ui.confirm('Xóa bộ sưu tập này?', function() {
      SS.api.post('/collections.php?action=delete', {collection_id: collId}).then(function() {
        SS.ui.toast('Đã xóa', 'success');
        SS.ui.closeSheet();
        SS.CollectionsUI.show();
      });
    });
  }
};
