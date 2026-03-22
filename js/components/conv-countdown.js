window.SS = window.SS || {};
SS.ConvCountdown = {
  show: function(conversationId) {
    SS.api.get('/conv-countdown.php?conversation_id=' + conversationId).then(function(d) {
      var countdowns = (d.data || {}).countdowns || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvCountdown.create(' + conversationId + ')"><i class="fa-solid fa-hourglass-start"></i> Tao dem nguoc</button>';
      if (!countdowns.length) html += '<div class="empty-state p-3"><div class="empty-icon">⏳</div><div class="empty-text">Chua co dem nguoc</div></div>';
      for (var i = 0; i < countdowns.length; i++) {
        var cd = countdowns[i];
        var color = cd.is_expired ? 'var(--danger)' : 'var(--primary)';
        html += '<div class="card mb-2" style="padding:12px;border-left:4px solid ' + color + (cd.is_expired ? ';opacity:0.6' : '') + '">'
          + '<div class="flex justify-between"><span class="font-bold text-sm">' + SS.utils.esc(cd.title) + '</span>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.ConvCountdown.del(' + conversationId + ',' + cd.id + ')" style="font-size:10px"><i class="fa-solid fa-xmark text-muted"></i></button></div>'
          + '<div style="font-size:20px;font-weight:800;color:' + color + ';margin:6px 0">' + (cd.is_expired ? '⏰ Het han!' : '⏳ ' + cd.remaining_text) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(cd.target_time || '') + ' · ' + SS.utils.esc(cd.creator_name || '') + '</div></div>';
      }
      SS.ui.sheet({title: '⏳ Dem nguoc (' + countdowns.length + ')', html: html});
    });
  },
  create: function(convId) {
    SS.ui.modal({title: 'Dem nguoc', html: '<input id="ccd-title" class="form-input mb-2" placeholder="Tieu de (VD: Giao hang truoc 5h)"><input id="ccd-time" class="form-input" type="datetime-local">', confirmText: 'Tao',
      onConfirm: function() { SS.api.post('/conv-countdown.php', {conversation_id: convId, title: document.getElementById('ccd-title').value, target_time: document.getElementById('ccd-time').value.replace('T', ' ')}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvCountdown.show(convId); }); }
    });
  },
  del: function(convId, cdId) { SS.api.post('/conv-countdown.php?action=delete', {conversation_id: convId, countdown_id: cdId}).then(function() { SS.ConvCountdown.show(convId); }); }
};
