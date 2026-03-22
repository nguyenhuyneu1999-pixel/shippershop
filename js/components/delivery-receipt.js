/**
 * ShipperShop Component — Delivery Receipt
 */
window.SS = window.SS || {};

SS.DeliveryReceipt = {
  show: function(conversationId) {
    SS.api.get('/delivery-receipt.php?conversation_id=' + conversationId).then(function(d) {
      var receipts = (d.data || {}).receipts || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.DeliveryReceipt.create(' + conversationId + ')"><i class="fa-solid fa-receipt"></i> Tao bien lai</button>';
      if (!receipts.length) html += '<div class="empty-state p-3"><div class="empty-icon">🧾</div><div class="empty-text">Chua co bien lai</div></div>';
      for (var i = 0; i < receipts.length; i++) {
        var r = receipts[i];
        html += '<div class="card mb-2" style="padding:12px;border-left:3px solid var(--success)">'
          + '<div class="flex justify-between"><span class="font-bold text-sm">🧾 ' + SS.utils.esc(r.receipt_no) + '</span><span class="text-xs text-muted">' + SS.utils.ago(r.delivery_time) + '</span></div>'
          + '<div class="text-xs mt-1">👤 ' + SS.utils.esc(r.recipient || '') + '</div>'
          + '<div class="text-xs">📍 ' + SS.utils.esc((r.address || '').substring(0, 50)) + '</div>'
          + (r.cod_amount ? '<div class="text-xs font-bold" style="color:var(--primary)">💰 COD: ' + SS.utils.formatMoney(r.cod_amount) + 'd ' + (r.cod_collected ? '✅' : '❌') + '</div>' : '')
          + '<div class="text-xs text-muted">Shipper: ' + SS.utils.esc(r.shipper_name || '') + '</div></div>';
      }
      SS.ui.sheet({title: '🧾 Bien lai (' + receipts.length + ')', html: html});
    });
  },
  create: function(convId) {
    SS.ui.modal({title: 'Tao bien lai', html: '<input id="dr-rec" class="form-input mb-2" placeholder="Nguoi nhan"><input id="dr-addr" class="form-input mb-2" placeholder="Dia chi"><input id="dr-items" class="form-input mb-2" placeholder="Hang hoa"><input id="dr-cod" class="form-input mb-2" type="number" placeholder="COD (d)"><label class="text-xs"><input type="checkbox" id="dr-collected" checked> Da thu COD</label>', confirmText: 'Tao',
      onConfirm: function() {
        SS.api.post('/delivery-receipt.php', {conversation_id: convId, recipient: document.getElementById('dr-rec').value, address: document.getElementById('dr-addr').value, items: document.getElementById('dr-items').value, cod_amount: parseInt(document.getElementById('dr-cod').value) || 0, cod_collected: document.getElementById('dr-collected').checked}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.DeliveryReceipt.show(convId); });
      }
    });
  }
};
