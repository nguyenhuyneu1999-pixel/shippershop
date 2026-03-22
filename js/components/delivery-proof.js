window.SS = window.SS || {};
SS.DeliveryProof = {
  show: function(conversationId) {
    SS.api.get('/delivery-proof.php?conversation_id=' + conversationId).then(function(d) {
      var proofs = (d.data || {}).proofs || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.DeliveryProof.create(' + conversationId + ')"><i class="fa-solid fa-camera"></i> Tao bang chung</button>';
      if (!proofs.length) html += '<div class="empty-state p-3"><div class="empty-icon">📸</div><div class="empty-text">Chua co bang chung giao hang</div></div>';
      var methodIcons = {hand: '🤝', door: '🚪', locker: '📦', guard: '👮'};
      for (var i = 0; i < Math.min(proofs.length, 15); i++) {
        var p = proofs[i];
        html += '<div class="card mb-2" style="padding:12px;border-left:3px solid var(--success)">'
          + '<div class="flex justify-between"><span class="font-bold text-sm">📸 ' + SS.utils.esc(p.proof_no) + '</span><span class="text-xs text-muted">' + SS.utils.ago(p.timestamp) + '</span></div>'
          + '<div class="text-xs mt-1">👤 ' + SS.utils.esc(p.recipient_name || 'N/A') + ' · ' + (methodIcons[p.delivery_method] || '📦') + ' ' + SS.utils.esc(p.delivery_method) + (p.has_signature ? ' ✍️' : '') + '</div>'
          + (p.cod_collected > 0 ? '<div class="text-xs font-bold" style="color:var(--primary)">💰 COD: ' + SS.utils.formatMoney(p.cod_collected) + 'd</div>' : '')
          + (p.photo_desc ? '<div class="text-xs text-muted mt-1">📝 ' + SS.utils.esc(p.photo_desc) + '</div>' : '')
          + '<div class="text-xs text-muted">Shipper: ' + SS.utils.esc(p.shipper_name || '') + '</div></div>';
      }
      SS.ui.sheet({title: '📸 Bang chung (' + proofs.length + ')', html: html});
    });
  },
  create: function(convId) {
    SS.ui.modal({title: 'Tao bang chung', html: '<input id="dp-rec" class="form-input mb-2" placeholder="Ten nguoi nhan"><input id="dp-order" class="form-input mb-2" placeholder="Ma don"><input id="dp-desc" class="form-input mb-2" placeholder="Mo ta anh (VD: De truoc cua)"><input id="dp-cod" class="form-input mb-2" type="number" placeholder="COD da thu (VND)"><select id="dp-method" class="form-select mb-2"><option value="hand">🤝 Tay-tay</option><option value="door">🚪 De cua</option><option value="locker">📦 Tu do</option><option value="guard">👮 Bao ve</option></select><label class="text-xs"><input type="checkbox" id="dp-sig"> Co chu ky</label>', confirmText: 'Luu',
      onConfirm: function() {
        SS.api.post('/delivery-proof.php', {conversation_id: convId, recipient_name: document.getElementById('dp-rec').value, order_id: document.getElementById('dp-order').value, photo_desc: document.getElementById('dp-desc').value, cod_collected: parseInt(document.getElementById('dp-cod').value) || 0, delivery_method: document.getElementById('dp-method').value, has_signature: document.getElementById('dp-sig').checked}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.DeliveryProof.show(convId); });
      }
    });
  }
};
