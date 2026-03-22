window.SS = window.SS || {};
SS.ConvPaymentSplit = {
  show: function(conversationId) {
    SS.api.get('/conv-payment-split.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var payments = data.payments || [];
      var balances = data.balances || [];
      var html = '<div class="flex gap-2 mb-3"><button class="btn btn-primary btn-sm" onclick="SS.ConvPaymentSplit.add(' + conversationId + ')"><i class="fa-solid fa-plus"></i> Them</button>'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.ConvPaymentSplit.settle(' + conversationId + ')"><i class="fa-solid fa-handshake"></i> Thanh toan</button></div>';
      html += '<div class="card mb-3" style="padding:10px;text-align:center"><div class="font-bold text-lg" style="color:var(--primary)">' + SS.utils.formatMoney(data.total || 0) + 'd</div><div class="text-xs text-muted">' + (data.count || 0) + ' khoan</div></div>';
      // Balances
      if (balances.length) {
        html += '<div class="text-sm font-bold mb-2">So du</div>';
        for (var b = 0; b < balances.length; b++) {
          var bl = balances[b];
          var color = bl.balance > 0 ? 'var(--success)' : (bl.balance < 0 ? 'var(--danger)' : 'var(--text-muted)');
          html += '<div class="flex justify-between text-sm p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(bl.name) + '</span><span class="font-bold" style="color:' + color + '">' + (bl.balance > 0 ? '+' : '') + SS.utils.formatMoney(bl.balance) + 'd</span></div>';
        }
      }
      // Payments
      if (payments.length) {
        html += '<div class="text-sm font-bold mb-2 mt-3">Chi tiet</div>';
        for (var i = 0; i < Math.min(payments.length, 10); i++) {
          var p = payments[i];
          html += '<div class="text-xs p-1" style="border-bottom:1px solid var(--border-light)">' + SS.utils.esc(p.payer_name || '') + ' tra ' + SS.utils.formatMoney(p.amount) + 'd — ' + SS.utils.esc(p.description) + ' · ' + SS.utils.ago(p.created_at) + '</div>';
        }
      }
      SS.ui.sheet({title: '💳 Chia tien', html: html});
    });
  },
  add: function(convId) {
    SS.ui.modal({title: 'Them khoan chi', html: '<input id="cps-desc" class="form-input mb-2" placeholder="Mo ta (VD: Tien xang chung)"><input id="cps-amount" class="form-input" type="number" placeholder="So tien (VND)">', confirmText: 'Them',
      onConfirm: function() { SS.api.post('/conv-payment-split.php', {conversation_id: convId, description: document.getElementById('cps-desc').value, amount: parseInt(document.getElementById('cps-amount').value) || 0, members: []}).then(function() { SS.ConvPaymentSplit.show(convId); }); }
    });
  },
  settle: function(convId) { SS.api.post('/conv-payment-split.php?action=settle', {conversation_id: convId}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvPaymentSplit.show(convId); }); }
};
