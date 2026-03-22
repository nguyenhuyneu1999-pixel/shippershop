window.SS = window.SS || {};
SS.ConvVoiceMemo = {
  show: function(conversationId) {
    SS.api.get('/conv-voice-memo.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var memos = data.memos || [];
      var prioIcons = {urgent: '🔴', high: '🟠', normal: '🔵', low: '⚪'};
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvVoiceMemo.add(' + conversationId + ')"><i class="fa-solid fa-microphone"></i> Ghi memo</button>';
      html += '<div class="text-xs text-muted mb-2">🎤 ' + (data.count || 0) + ' memo · ' + Math.round((data.total_duration || 0) / 60) + ' phut</div>';
      for (var i = 0; i < memos.length; i++) {
        var m = memos[i];
        var listened = (m.listened_by || []).length;
        html += '<div class="card mb-2" style="padding:10px"><div class="flex items-center gap-2">'
          + '<img src="' + (m.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><div class="text-sm">' + (prioIcons[m.priority] || '🔵') + ' ' + SS.utils.esc(m.transcript) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(m.user_name || '') + ' · ' + m.duration + 's · 👁 ' + listened + ' · ' + SS.utils.ago(m.created_at) + '</div></div></div></div>';
      }
      if (!memos.length) html += '<div class="empty-state p-3"><div class="empty-icon">🎤</div><div class="empty-text">Chua co voice memo</div></div>';
      SS.ui.sheet({title: '🎤 Voice Memo', html: html});
    });
  },
  add: function(convId) {
    SS.ui.modal({title: 'Ghi memo', html: '<textarea id="cvm-text" class="form-textarea mb-2" rows="3" placeholder="Noi dung memo..."></textarea><input id="cvm-dur" class="form-input mb-2" type="number" placeholder="Thoi gian (giay)" value="10"><select id="cvm-prio" class="form-select"><option value="normal">🔵 Binh thuong</option><option value="urgent">🔴 Gap</option><option value="high">🟠 Quan trong</option></select>', confirmText: 'Ghi',
      onConfirm: function() { SS.api.post('/conv-voice-memo.php', {conversation_id: convId, transcript: document.getElementById('cvm-text').value, duration: parseInt(document.getElementById('cvm-dur').value) || 10, priority: document.getElementById('cvm-prio').value}).then(function() { SS.ConvVoiceMemo.show(convId); }); }
    });
  }
};
