/**
 * ShipperShop Component — Drafts Manager
 * List, edit, publish, delete post drafts
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.DraftsManager = {

  show: function() {
    SS.api.get('/drafts-manager.php').then(function(d) {
      var drafts = (d.data || {}).drafts || [];
      if (!drafts.length) {
        SS.ui.sheet({title: 'Ban nhap', html: '<div class="empty-state p-4"><div class="empty-icon">📝</div><div class="empty-text">Khong co ban nhap nao</div><button class="btn btn-primary btn-sm mt-3" onclick="SS.DraftsManager.create()">Tao ban nhap</button></div>'});
        return;
      }
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.DraftsManager.create()"><i class="fa-solid fa-plus"></i> Tao moi</button>';
      for (var i = 0; i < drafts.length; i++) {
        var dr = drafts[i];
        html += '<div class="card mb-2" style="padding:12px">'
          + '<div class="text-sm">' + SS.utils.esc((dr.content || '').substring(0, 120)) + '</div>'
          + '<div class="text-xs text-muted mt-1">' + SS.utils.ago(dr.created_at) + '</div>'
          + '<div class="flex gap-2 mt-2">'
          + '<button class="btn btn-primary btn-sm" onclick="SS.DraftsManager._publish(' + dr.id + ')"><i class="fa-solid fa-paper-plane"></i> Dang</button>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.DraftsManager._edit(' + dr.id + ')"><i class="fa-solid fa-pen"></i></button>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.DraftsManager._delete(' + dr.id + ')"><i class="fa-solid fa-trash text-danger"></i></button>'
          + '</div></div>';
      }
      SS.ui.sheet({title: 'Ban nhap (' + drafts.length + ')', html: html});
    });
  },

  create: function() {
    SS.ui.modal({
      title: 'Tao ban nhap',
      html: '<textarea id="dm-content" class="form-textarea" rows="5" placeholder="Viet noi dung..."></textarea>',
      confirmText: 'Luu nhap',
      onConfirm: function() {
        var content = document.getElementById('dm-content').value;
        SS.api.post('/drafts-manager.php?action=save', {content: content}).then(function(d) {
          SS.ui.toast('Da luu!', 'success');
          SS.DraftsManager.show();
        });
      }
    });
  },

  _edit: function(id) {
    SS.api.get('/drafts-manager.php').then(function(d) {
      var drafts = (d.data || {}).drafts || [];
      var draft = drafts.find(function(dr) { return dr.id == id; });
      if (!draft) return;
      SS.ui.closeSheet();
      SS.ui.modal({
        title: 'Sua ban nhap',
        html: '<textarea id="dm-edit" class="form-textarea" rows="5">' + SS.utils.esc(draft.content || '') + '</textarea>',
        confirmText: 'Cap nhat',
        onConfirm: function() {
          SS.api.post('/drafts-manager.php?action=save', {draft_id: id, content: document.getElementById('dm-edit').value}).then(function() {
            SS.ui.toast('Da cap nhat!', 'success'); SS.DraftsManager.show();
          });
        }
      });
    });
  },

  _publish: function(id) {
    SS.ui.confirm('Dang bai tu ban nhap nay?', function() {
      SS.api.post('/drafts-manager.php?action=publish', {draft_id: id}).then(function(d) {
        SS.ui.toast(d.message || 'Da dang!', 'success'); SS.DraftsManager.show();
      });
    });
  },

  _delete: function(id) {
    SS.ui.confirm('Xoa ban nhap?', function() {
      SS.api.post('/drafts-manager.php?action=delete', {draft_id: id}).then(function() {
        SS.ui.toast('Da xoa', 'success'); SS.DraftsManager.show();
      });
    });
  }
};
