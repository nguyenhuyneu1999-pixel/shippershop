window.SS = window.SS || {};
SS.ConvDeliveryHandoff = {
  show: function(conversationId) {
    SS.api.get('/conv-delivery-handoff.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var handoffs = data.handoffs || [];
      var reasons = data.reasons || {};
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvDeliveryHandoff.request(' + conversationId + ')"><i class="fa-solid fa-people-arrows"></i> Ban giao</button>';
      if (data.pending > 0) html += '<div class="text-xs" style="color:var(--warning)">⏳ ' + data.pending + ' cho phan hoi</div>';
      var statusIcons = {pending: '⏳', accepted: '✅', rejected: '❌', completed: '🏁'};
      var statusColors = {pending: 'var(--warning)', accepted: 'var(--success)', rejected: 'var(--danger)', completed: 'var(--text-muted)'};
      for (var i = 0; i < handoffs.length; i++) {
        var h = handoffs[i];
        html += '<div class="card mb-2" style="padding:10px;border-left:3px solid ' + (statusColors[h.status] || 'var(--border)') + '">'
          + '<div class="flex justify-between"><span class="text-sm font-bold">' + (statusIcons[h.status] || '') + ' ' + SS.utils.esc(h.from_name || '') + ' → ' + SS.utils.esc(h.to_name || '') + '</span><span class="text-xs text-muted">' + SS.utils.ago(h.created_at) + '</span></div>'
          + '<div class="text-xs text-muted">📋 ' + SS.utils.esc(reasons[h.reason] || h.reason) + (h.order_count ? ' · 📦 ' + h.order_count + ' don' : '') + '</div>'
          + (h.notes ? '<div class="text-xs text-muted">' + SS.utils.esc(h.notes) + '</div>' : '')
          + (h.status === 'pending' ? '<div class="flex gap-2 mt-1"><button class="btn btn-ghost btn-sm" onclick="SS.ConvDeliveryHandoff.accept(' + conversationId + ',' + h.id + ')" style="font-size:10px;color:var(--success)">✅ Nhan</button><button class="btn btn-ghost btn-sm" onclick="SS.ConvDeliveryHandoff.reject(' + conversationId + ',' + h.id + ')" style="font-size:10px;color:var(--danger)">❌ Tu choi</button></div>' : '') + '</div>';
      }
      if (!handoffs.length) html += '<div class="empty-state p-3"><div class="empty-icon">🤝</div><div class="empty-text">Chua co ban giao</div></div>';
      SS.ui.sheet({title: '🤝 Ban giao (' + (data.count || 0) + ')', html: html});
    });
  },
  request: function(convId) {
    SS.ui.modal({title: 'Ban giao', html: '<input id="cdh-to" class="form-input mb-2" type="number" placeholder="User ID nguoi nhan"><select id="cdh-reason" class="form-select mb-2"><option value="shift_end">Het ca</option><option value="break">Nghi ngoi</option><option value="area">Het khu vuc</option><option value="vehicle">Hong xe</option><option value="personal">Ca nhan</option><option value="emergency">Khan cap</option></select><input id="cdh-count" class="form-input mb-2" type="number" placeholder="So don con lai"><textarea id="cdh-notes" class="form-textarea" rows="2" placeholder="Ghi chu"></textarea>', confirmText: 'Ban giao',
      onConfirm: function() { SS.api.post('/conv-delivery-handoff.php', {conversation_id: convId, to_id: parseInt(document.getElementById('cdh-to').value), reason: document.getElementById('cdh-reason').value, order_count: parseInt(document.getElementById('cdh-count').value) || 0, notes: document.getElementById('cdh-notes').value}).then(function() { SS.ConvDeliveryHandoff.show(convId); }); }
    });
  },
  accept: function(convId, hid) { SS.api.post('/conv-delivery-handoff.php?action=accept', {conversation_id: convId, handoff_id: hid}).then(function() { SS.ConvDeliveryHandoff.show(convId); }); },
  reject: function(convId, hid) { SS.api.post('/conv-delivery-handoff.php?action=reject', {conversation_id: convId, handoff_id: hid}).then(function() { SS.ConvDeliveryHandoff.show(convId); }); }
};
