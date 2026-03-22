window.SS = window.SS || {};
SS.ConvDeliveryMap = {
  show: function(conversationId) {
    SS.api.get('/conv-delivery-map.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var pins = data.pins || [];
      var typeIcons = {stop: '📍', pickup: '📦', dropoff: '🏠', warehouse: '🏭'};
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvDeliveryMap.add(' + conversationId + ')"><i class="fa-solid fa-map-pin"></i> Them diem</button>';
      html += '<div class="text-xs text-muted mb-2">' + (data.completed || 0) + '/' + (data.count || 0) + ' hoan thanh</div>';
      // Progress bar
      var pct = data.count > 0 ? Math.round(data.completed / data.count * 100) : 0;
      html += '<div style="height:6px;background:var(--border-light);border-radius:3px;margin-bottom:12px"><div style="width:' + pct + '%;height:100%;background:var(--success);border-radius:3px"></div></div>';
      if (!pins.length) html += '<div class="empty-state p-3"><div class="empty-icon">🗺️</div><div class="empty-text">Chua co diem giao</div></div>';
      for (var i = 0; i < pins.length; i++) {
        var p = pins[i];
        html += '<div class="flex items-center gap-2 p-2" style="border-left:3px solid ' + (p.completed ? 'var(--success)' : 'var(--primary)') + ';margin-bottom:4px;opacity:' + (p.completed ? '0.6' : '1') + '">'
          + '<span class="font-bold text-xs" style="width:22px;color:var(--primary)">#' + (p.order || i + 1) + '</span>'
          + '<div class="flex-1"><div class="text-sm ' + (p.completed ? '' : 'font-bold') + '" style="' + (p.completed ? 'text-decoration:line-through' : '') + '">' + (typeIcons[p.type] || '📍') + ' ' + SS.utils.esc(p.label || p.address) + '</div>'
          + (p.address && p.label ? '<div class="text-xs text-muted">' + SS.utils.esc(p.address) + '</div>' : '')
          + '<div class="text-xs text-muted">' + SS.utils.esc(p.user_name || '') + '</div></div>'
          + (!p.completed ? '<button class="btn btn-ghost btn-sm" onclick="SS.ConvDeliveryMap.complete(' + conversationId + ',' + p.id + ')" style="font-size:10px">✅</button>' : '') + '</div>';
      }
      SS.ui.sheet({title: '🗺️ Ban do giao (' + (data.count || 0) + ')', html: html});
    });
  },
  add: function(convId) {
    SS.ui.modal({title: 'Them diem giao', html: '<input id="cdm-label" class="form-input mb-2" placeholder="Ten diem"><input id="cdm-addr" class="form-input mb-2" placeholder="Dia chi"><select id="cdm-type" class="form-select"><option value="stop">📍 Diem dung</option><option value="pickup">📦 Lay hang</option><option value="dropoff">🏠 Giao hang</option><option value="warehouse">🏭 Kho</option></select>', confirmText: 'Them',
      onConfirm: function() { SS.api.post('/conv-delivery-map.php', {conversation_id: convId, label: document.getElementById('cdm-label').value, address: document.getElementById('cdm-addr').value, type: document.getElementById('cdm-type').value}).then(function() { SS.ConvDeliveryMap.show(convId); }); }
    });
  },
  complete: function(convId, pinId) { SS.api.post('/conv-delivery-map.php?action=complete', {conversation_id: convId, pin_id: pinId}).then(function() { SS.ConvDeliveryMap.show(convId); }); }
};
