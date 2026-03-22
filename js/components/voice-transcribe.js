/**
 * ShipperShop Component — Voice Transcribe
 */
window.SS = window.SS || {};

SS.VoiceTranscribe = {
  show: function(conversationId) {
    SS.api.get('/voice-transcribe.php?conversation_id=' + conversationId).then(function(d) {
      var transcripts = (d.data || {}).transcriptions || [];
      var html = '<div class="flex gap-2 mb-3"><input id="vt-search" class="form-input flex-1" placeholder="Tim kiem trong ban ghi am..."><button class="btn btn-primary btn-sm" onclick="SS.VoiceTranscribe.search(' + conversationId + ')"><i class="fa-solid fa-search"></i></button></div>';
      if (!transcripts.length) html += '<div class="empty-state p-3"><div class="empty-icon">🎙️</div><div class="empty-text">Chua co ban ghi am</div></div>';
      for (var i = 0; i < transcripts.length; i++) {
        var t = transcripts[i];
        var dur = t.duration || 0;
        html += '<div class="card mb-2" style="padding:10px"><div class="text-sm">' + SS.utils.esc(t.text) + '</div>'
          + '<div class="text-xs text-muted mt-1">🎙️ ' + Math.floor(dur / 60) + ':' + (dur % 60 < 10 ? '0' : '') + (dur % 60) + ' · ' + SS.utils.ago(t.created_at) + '</div></div>';
      }
      SS.ui.sheet({title: '🎙️ Ban ghi am (' + transcripts.length + ')', html: html});
    });
  },
  search: function(conversationId) {
    var q = document.getElementById('vt-search').value;
    if (!q) return;
    SS.api.get('/voice-transcribe.php?conversation_id=' + conversationId + '&search=' + encodeURIComponent(q)).then(function(d) {
      var transcripts = (d.data || {}).transcriptions || [];
      SS.ui.toast(transcripts.length + ' ket qua', 'info');
    });
  }
};
