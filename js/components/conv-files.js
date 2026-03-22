/**
 * ShipperShop Component — Conversation File Manager
 */
window.SS = window.SS || {};

SS.ConvFiles = {
  show: function(conversationId) {
    SS.api.get('/conv-files.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var files = data.files || [];
      var byType = data.by_type || {};

      var html = '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:8px;flex:1"><div class="font-bold">' + (data.count || 0) + '</div><div class="text-xs text-muted">Files</div></div>'
        + '<div class="card" style="padding:8px;flex:1"><div class="font-bold">' + (data.total_size_kb || 0) + ' KB</div><div class="text-xs text-muted">Dung luong</div></div></div>';

      // Type chips
      var types = Object.keys(byType);
      if (types.length) {
        html += '<div class="flex gap-1 flex-wrap mb-3">';
        for (var t = 0; t < types.length; t++) html += '<span class="chip">' + types[t] + ' (' + byType[types[t]] + ')</span>';
        html += '</div>';
      }

      if (!files.length) { html += '<div class="empty-state p-3"><div class="empty-icon">📁</div><div class="empty-text">Chua co file</div></div>'; }
      for (var i = 0; i < Math.min(files.length, 20); i++) {
        var f = files[i];
        var icons = {image: '🖼️', video: '🎬', document: '📄', audio: '🎵', other: '📎'};
        html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<span style="font-size:20px">' + (icons[f.type] || '📎') + '</span>'
          + '<div class="flex-1"><div class="text-sm">' + SS.utils.esc(f.filename) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(f.uploader_name || '') + ' · ' + SS.utils.ago(f.uploaded_at) + (f.size ? ' · ' + Math.round(f.size / 1024) + ' KB' : '') + '</div></div></div>';
      }
      SS.ui.sheet({title: '📁 Files (' + (data.count || 0) + ')', html: html});
    });
  }
};
