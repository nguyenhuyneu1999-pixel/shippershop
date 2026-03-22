/**
 * ShipperShop Component — Drafts Sync
 */
window.SS = window.SS || {};

SS.DraftsSync = {
  show: function() {
    SS.api.get('/drafts-sync.php').then(function(d) {
      var drafts = (d.data || {}).drafts || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.DraftsSync.create()"><i class="fa-solid fa-plus"></i> Tao nhap moi</button>';
      if (!drafts.length) {
        html += '<div class="empty-state p-3"><div class="empty-icon">📝</div><div class="empty-text">Chua co ban nhap</div></div>';
      }
      for (var i = 0; i < drafts.length; i++) {
        var dr = drafts[i];
        html += '<div class="card mb-2" style="padding:10px"><div class="flex justify-between items-start"><div class="flex-1">'
          + (dr.title ? '<div class="font-bold text-sm">' + SS.utils.esc(dr.title) + '</div>' : '')
          + '<div class="text-sm text-muted">' + SS.utils.esc((dr.content || '').substring(0, 80)) + '</div>'
          + '<div class="text-xs text-muted mt-1">v' + (dr.version || 1) + ' · ' + SS.utils.ago(dr.updated_at) + '</div></div>'
          + '<div class="flex gap-1"><button class="btn btn-ghost btn-sm" onclick="SS.DraftsSync.edit(\'' + dr.id + '\')"><i class="fa-solid fa-edit" style="font-size:11px"></i></button>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.DraftsSync.del(\'' + dr.id + '\')"><i class="fa-solid fa-trash text-danger" style="font-size:11px"></i></button></div></div></div>';
      }
      SS.ui.sheet({title: 'Ban nhap (' + drafts.length + ')', html: html});
      SS.DraftsSync._drafts = drafts;
    });
  },
  _drafts: [],
  create: function() {
    SS.ui.modal({title: 'Ban nhap moi', html: '<input id="ds-title" class="form-input mb-2" placeholder="Tieu de"><textarea id="ds-content" class="form-textarea" rows="4" placeholder="Noi dung..."></textarea>', confirmText: 'Luu',
      onConfirm: function() {
        SS.api.post('/drafts-sync.php', {title: document.getElementById('ds-title').value, content: document.getElementById('ds-content').value}).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); SS.DraftsSync.show(); });
      }
    });
  },
  edit: function(draftId) {
    var dr = null; for (var i = 0; i < SS.DraftsSync._drafts.length; i++) { if (SS.DraftsSync._drafts[i].id === draftId) { dr = SS.DraftsSync._drafts[i]; break; } }
    if (!dr) return;
    SS.ui.closeSheet();
    SS.ui.modal({title: 'Sua ban nhap', html: '<input id="ds-title" class="form-input mb-2" placeholder="Tieu de" value="' + SS.utils.esc(dr.title || '') + '"><textarea id="ds-content" class="form-textarea" rows="4">' + SS.utils.esc(dr.content || '') + '</textarea>', confirmText: 'Luu',
      onConfirm: function() {
        SS.api.post('/drafts-sync.php', {draft_id: draftId, title: document.getElementById('ds-title').value, content: document.getElementById('ds-content').value}).then(function(d) { SS.ui.toast('Da dong bo v' + ((dr.version || 1) + 1), 'success'); });
      }
    });
  },
  del: function(draftId) { SS.api.post('/drafts-sync.php?action=delete', {draft_id: draftId}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.DraftsSync.show(); }); }
};
