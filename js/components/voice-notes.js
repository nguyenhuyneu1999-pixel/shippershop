/**
 * ShipperShop Component — Voice Notes
 */
window.SS = window.SS || {};

SS.VoiceNotes = {
  show: function(conversationId) {
    SS.api.get('/voice-notes.php?conversation_id=' + conversationId).then(function(d) {
      var notes = (d.data || {}).notes || [];
      if (!notes.length) {
        SS.ui.sheet({title: 'Voice Notes', html: '<div class="empty-state p-4"><div class="empty-icon">🎙️</div><div class="empty-text">Chua co voice note</div></div>'});
        return;
      }
      var html = '';
      for (var i = 0; i < notes.length; i++) {
        var n = notes[i];
        var dur = n.duration || 0;
        var min = Math.floor(dur / 60);
        var sec = dur % 60;
        html += '<div class="flex items-center gap-3 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<img src="' + (n.sender_avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><div class="text-sm">' + SS.utils.esc(n.sender_name || '') + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.ago(n.created_at) + '</div></div>'
          + '<div class="flex items-center gap-2"><span class="text-xs">🎙️ ' + min + ':' + (sec < 10 ? '0' : '') + sec + '</span></div></div>';
      }
      SS.ui.sheet({title: '🎙️ Voice Notes (' + notes.length + ')', html: html});
    });
  }
};
