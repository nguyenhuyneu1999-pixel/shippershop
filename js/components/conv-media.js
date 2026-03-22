/**
 * ShipperShop Component — Conversation Media Gallery
 * Browse shared images/files/links in a conversation
 */
window.SS = window.SS || {};

SS.ConvMedia = {
  show: function(conversationId, type) {
    type = type || 'all';
    SS.api.get('/conv-media.php?conversation_id=' + conversationId + '&type=' + type).then(function(d) {
      var data = d.data || {};
      var media = data.media || [];
      var counts = data.counts || {};

      var html = '<div class="flex gap-2 mb-3">';
      var tabs = [{id: 'all', label: 'Tat ca'}, {id: 'image', label: 'Anh (' + (counts.images || 0) + ')'}, {id: 'file', label: 'File (' + (counts.files || 0) + ')'}, {id: 'link', label: 'Link (' + (counts.links || 0) + ')'}];
      for (var t = 0; t < tabs.length; t++) {
        html += '<div class="chip ' + (type === tabs[t].id ? 'chip-active' : '') + '" onclick="SS.ConvMedia.show(' + conversationId + ',\'' + tabs[t].id + '\')" style="cursor:pointer">' + tabs[t].label + '</div>';
      }
      html += '</div>';

      if (!media.length) {
        html += '<div class="empty-state p-4"><div class="empty-icon">🖼️</div><div class="empty-text">Khong co media</div></div>';
      }

      for (var i = 0; i < media.length; i++) {
        var m = media[i];
        html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<img src="' + (m.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><div class="text-sm">' + SS.utils.esc((m.content || '').substring(0, 60)) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(m.fullname || '') + ' · ' + SS.utils.ago(m.created_at) + '</div></div></div>';
      }

      SS.ui.sheet({title: 'Media (' + media.length + ')', html: html});
    });
  }
};
