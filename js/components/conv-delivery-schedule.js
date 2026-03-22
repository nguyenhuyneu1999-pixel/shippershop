window.SS = window.SS || {};
SS.ConvDeliverySchedule = {
  show: function(conversationId) {
    SS.api.get('/conv-delivery-schedule.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var schedules = data.schedules || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvDeliverySchedule.add(' + conversationId + ')"><i class="fa-solid fa-calendar-plus"></i> Them lich giao</button>';
      var statusIcons = {scheduled: '📅', completed: '✅', cancelled: '❌'};
      if (!schedules.length) html += '<div class="empty-state p-3"><div class="empty-icon">📅</div><div class="empty-text">Chua co lich giao hang</div></div>';
      for (var i = 0; i < schedules.length; i++) {
        var s = schedules[i];
        var color = s.is_active ? 'var(--success)' : (s.is_past ? 'var(--text-muted)' : 'var(--primary)');
        html += '<div class="card mb-2" style="padding:10px;border-left:3px solid ' + color + (s.is_past ? ';opacity:0.6' : '') + '">'
          + '<div class="flex justify-between"><span class="text-sm font-bold">' + (statusIcons[s.status] || '📅') + ' ' + SS.utils.esc(s.shipper_name || '') + (s.is_active ? ' 🟢' : '') + '</span>'
          + (s.status === 'scheduled' ? '<div class="flex gap-1"><button class="btn btn-ghost btn-sm" onclick="SS.ConvDeliverySchedule.complete(' + conversationId + ',' + s.id + ')" style="font-size:10px">✅</button><button class="btn btn-ghost btn-sm" onclick="SS.ConvDeliverySchedule.cancel(' + conversationId + ',' + s.id + ')" style="font-size:10px">❌</button></div>' : '') + '</div>'
          + '<div class="text-xs">⏰ ' + SS.utils.esc(s.start_time) + ' → ' + SS.utils.esc(s.end_time) + '</div>'
          + (s.area ? '<div class="text-xs text-muted">📍 ' + SS.utils.esc(s.area) + '</div>' : '')
          + (s.order_count ? '<div class="text-xs text-muted">📦 ' + s.order_count + ' don</div>' : '') + '</div>';
      }
      SS.ui.sheet({title: '📅 Lich giao hang (' + schedules.length + ')', html: html});
    });
  },
  add: function(convId) {
    SS.ui.modal({title: 'Them lich giao', html: '<label class="text-xs text-muted">Bat dau</label><input id="cds-start" class="form-input mb-2" type="datetime-local"><label class="text-xs text-muted">Ket thuc</label><input id="cds-end" class="form-input mb-2" type="datetime-local"><input id="cds-area" class="form-input mb-2" placeholder="Khu vuc"><input id="cds-count" class="form-input" type="number" placeholder="So don du kien">', confirmText: 'Them',
      onConfirm: function() { SS.api.post('/conv-delivery-schedule.php', {conversation_id: convId, start_time: document.getElementById('cds-start').value, end_time: document.getElementById('cds-end').value, area: document.getElementById('cds-area').value, order_count: parseInt(document.getElementById('cds-count').value) || 0}).then(function() { SS.ConvDeliverySchedule.show(convId); }); }
    });
  },
  complete: function(convId, id) { SS.api.post('/conv-delivery-schedule.php?action=complete', {conversation_id: convId, schedule_id: id}).then(function() { SS.ConvDeliverySchedule.show(convId); }); },
  cancel: function(convId, id) { SS.api.post('/conv-delivery-schedule.php?action=cancel', {conversation_id: convId, schedule_id: id}).then(function() { SS.ConvDeliverySchedule.show(convId); }); }
};
