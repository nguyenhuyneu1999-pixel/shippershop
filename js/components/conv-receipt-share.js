window.SS = window.SS || {};
SS.ConvReceiptShare = {
  show: function(conversationId) {
    SS.api.get('/conv-receipt-share.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var receipts = data.receipts || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvReceiptShare.create(' + conversationId + ')"><i class="fa-solid fa-receipt"></i> Tao phieu</button>';
      html += '<div class="flex gap-2 mb-2 text-xs"><span class="chip">💰 COD: ' + SS.utils.formatMoney(data.total_cod || 0) + 'd</span><span class="chip">🚚 Phi: ' + SS.utils.formatMoney(data.total_fee || 0) + 'd</span></div>';
      var statusIcons = {pending: '⏳', confirmed: '✅'};
      for (var i = 0; i < Math.min(receipts.length, 15); i++) {
        var r = receipts[i];
        html += '<div class="card mb-2" style="padding:10px;border-left:3px solid ' + (r.status === 'confirmed' ? 'var(--success)' : 'var(--warning)') + '">'
          + '<div class="flex justify-between"><span class="font-bold text-xs" style="color:var(--primary)">' + SS.utils.esc(r.receipt_no) + '</span><span class="text-xs">' + (statusIcons[r.status] || '⏳') + '</span></div>'
          + '<div class="text-sm mt-1">👤 ' + SS.utils.esc(r.recipient_name) + (r.recipient_phone ? ' · 📞 ' + SS.utils.esc(r.recipient_phone) : '') + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(r.company || '') + (r.order_code ? ' · #' + SS.utils.esc(r.order_code) : '') + '</div>'
          + (r.cod_amount > 0 ? '<div class="text-xs font-bold" style="color:var(--primary)">💰 COD: ' + SS.utils.formatMoney(r.cod_amount) + 'd</div>' : '')
          + '<div class="text-xs text-muted">' + SS.utils.esc(r.shipper_name || '') + ' · ' + SS.utils.ago(r.created_at) + ' · 🔑 ' + SS.utils.esc(r.verify_code || '') + '</div>'
          + (r.status === 'pending' ? '<button class="btn btn-ghost btn-sm mt-1" onclick="SS.ConvReceiptShare.confirm(' + conversationId + ',\'' + r.receipt_no + '\')" style="font-size:10px">✅ Xac nhan</button>' : '') + '</div>';
      }
      SS.ui.sheet({title: '🧾 Phieu giao (' + (data.count || 0) + ')', html: html});
    });
  },
  create: function(convId) {
    SS.ui.modal({title: 'Tao phieu giao', html: '<input id="crs-name" class="form-input mb-2" placeholder="Ten nguoi nhan"><input id="crs-phone" class="form-input mb-2" placeholder="SDT"><input id="crs-addr" class="form-input mb-2" placeholder="Dia chi"><input id="crs-order" class="form-input mb-2" placeholder="Ma don"><input id="crs-cod" class="form-input mb-2" type="number" placeholder="COD (VND)"><input id="crs-fee" class="form-input" type="number" placeholder="Phi ship (VND)">', confirmText: 'Tao',
      onConfirm: function() { SS.api.post('/conv-receipt-share.php', {conversation_id: convId, recipient_name: document.getElementById('crs-name').value, recipient_phone: document.getElementById('crs-phone').value, address: document.getElementById('crs-addr').value, order_code: document.getElementById('crs-order').value, cod_amount: parseInt(document.getElementById('crs-cod').value) || 0, shipping_fee: parseInt(document.getElementById('crs-fee').value) || 0}).then(function(d) { SS.ui.toast('Da tao phieu!', 'success'); SS.ConvReceiptShare.show(convId); }); }
    });
  },
  confirm: function(convId, receiptNo) { SS.api.post('/conv-receipt-share.php?action=confirm', {conversation_id: convId, receipt_no: receiptNo}).then(function() { SS.ConvReceiptShare.show(convId); }); }
};
