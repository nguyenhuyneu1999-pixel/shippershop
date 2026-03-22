window.SS = window.SS || {};
SS.ConvDeliverySummary = {
  show: function(conversationId) {
    SS.api.get('/conv-delivery-summary.php?conversation_id=' + conversationId).then(function(d) {
      var summaries = (d.data || {}).summaries || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvDeliverySummary.add(' + conversationId + ')"><i class="fa-solid fa-clipboard-list"></i> Bao cao ngay</button>';
      for (var i = 0; i < Math.min(summaries.length, 10); i++) {
        var s = summaries[i];
        html += '<div class="card mb-2" style="padding:12px"><div class="flex justify-between"><span class="font-bold text-sm">📅 ' + SS.utils.esc(s.date) + '</span><span class="text-xs" style="color:' + (s.success_rate >= 90 ? 'var(--success)' : 'var(--warning)') + '">' + (s.success_rate || 0) + '%</span></div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(s.shipper_name || '') + '</div>'
          + '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:4px;margin-top:6px;text-align:center;font-size:10px">'
          + '<div>✅ ' + (s.completed || 0) + '/' + (s.total_orders || 0) + '</div>'
          + '<div>💰 ' + SS.utils.formatMoney(s.cod_collected || 0) + 'd</div>'
          + '<div>🏍️ ' + (s.km_driven || 0) + 'km</div>'
          + '<div>⏰ ' + (s.hours_worked || 0) + 'h</div>'
          + '<div>⛽ ' + SS.utils.formatMoney(s.fuel_cost || 0) + 'd</div>'
          + '<div style="color:var(--success)">💵 ' + SS.utils.formatMoney(s.income || 0) + 'd</div></div></div>';
      }
      if (!summaries.length) html += '<div class="empty-state p-3"><div class="empty-icon">📊</div><div class="empty-text">Chua co bao cao</div></div>';
      SS.ui.sheet({title: '📊 Bao cao ngay', html: html});
    });
  },
  add: function(convId) {
    SS.ui.modal({title: 'Bao cao cuoi ngay', html: '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px"><input id="cdsm-total" class="form-input" type="number" placeholder="Tong don"><input id="cdsm-done" class="form-input" type="number" placeholder="Thanh cong"><input id="cdsm-cod" class="form-input" type="number" placeholder="COD thu"><input id="cdsm-km" class="form-input" type="number" placeholder="Km di" step="0.1"><input id="cdsm-hours" class="form-input" type="number" placeholder="Gio lam" step="0.5"><input id="cdsm-fuel" class="form-input" type="number" placeholder="Tien xang"><input id="cdsm-income" class="form-input" type="number" placeholder="Thu nhap" style="grid-column:span 2"></div>', confirmText: 'Luu',
      onConfirm: function() { SS.api.post('/conv-delivery-summary.php', {conversation_id: convId, total_orders: parseInt(document.getElementById('cdsm-total').value) || 0, completed_orders: parseInt(document.getElementById('cdsm-done').value) || 0, cod_collected: parseInt(document.getElementById('cdsm-cod').value) || 0, km_driven: parseFloat(document.getElementById('cdsm-km').value) || 0, hours_worked: parseFloat(document.getElementById('cdsm-hours').value) || 0, fuel_cost: parseInt(document.getElementById('cdsm-fuel').value) || 0, income: parseInt(document.getElementById('cdsm-income').value) || 0}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvDeliverySummary.show(convId); }); }
    });
  }
};
