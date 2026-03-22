window.SS = window.SS || {};
SS.ConvSharedNotes = {
  show: function(conversationId) {
    SS.api.get('/conv-shared-notes.php?conversation_id=' + conversationId).then(function(d) {
      var notes = (d.data || {}).notes || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvSharedNotes.add(' + conversationId + ')"><i class="fa-solid fa-sticky-note"></i> Them ghi chu</button>';
      if (!notes.length) html += '<div class="empty-state p-3"><div class="empty-icon">📝</div><div class="empty-text">Chua co ghi chu chung</div></div>';
      for (var i = 0; i < notes.length; i++) {
        var n = notes[i];
        html += '<div class="card mb-2" style="padding:12px">'
          + (n.title ? '<div class="font-bold text-sm mb-1">' + SS.utils.esc(n.title) + '</div>' : '')
          + '<div class="text-sm" style="white-space:pre-wrap">' + SS.utils.esc(n.content) + '</div>'
          + '<div class="flex justify-between mt-2"><span class="text-xs text-muted">✍️ ' + SS.utils.esc(n.author_name || '') + ' · ' + SS.utils.ago(n.created_at) + '</span>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.ConvSharedNotes.del(' + conversationId + ',' + n.id + ')" style="font-size:10px"><i class="fa-solid fa-trash text-muted"></i></button></div></div>';
      }
      SS.ui.sheet({title: '📝 Ghi chu chung (' + notes.length + ')', html: html});
    });
  },
  add: function(convId) {
    SS.ui.modal({title: 'Them ghi chu', html: '<input id="csn-title" class="form-input mb-2" placeholder="Tieu de (tuy chon)"><textarea id="csn-content" class="form-textarea" rows="4" placeholder="Noi dung..."></textarea>', confirmText: 'Luu',
      onConfirm: function() { SS.api.post('/conv-shared-notes.php', {conversation_id: convId, title: document.getElementById('csn-title').value, content: document.getElementById('csn-content').value}).then(function() { SS.ConvSharedNotes.show(convId); }); }
    });
  },
  del: function(convId, noteId) { SS.api.post('/conv-shared-notes.php?action=delete', {conversation_id: convId, note_id: noteId}).then(function() { SS.ConvSharedNotes.show(convId); }); }
};
