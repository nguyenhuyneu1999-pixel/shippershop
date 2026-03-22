/**
 * ShipperShop Component — Expense Splitter
 */
window.SS = window.SS || {};

SS.ExpenseSplitter = {
  show: function(conversationId) {
    SS.api.get('/expense-splitter.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var expenses = data.expenses || [];
      var catIcons = {fuel: '⛽', toll: '🛣️', parking: '🅿️', food: '🍜', repair: '🔧', other: '📎'};

      var html = '<div class="flex gap-2 mb-3"><button class="btn btn-primary btn-sm" onclick="SS.ExpenseSplitter.add(' + conversationId + ')"><i class="fa-solid fa-plus"></i> Them chi phi</button>'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.ExpenseSplitter.settle(' + conversationId + ')"><i class="fa-solid fa-handshake"></i> Thanh toan</button></div>';

      html += '<div class="card mb-3" style="padding:10px;text-align:center"><div class="font-bold text-lg" style="color:var(--primary)">' + SS.utils.formatMoney(data.total || 0) + 'd</div><div class="text-xs text-muted">' + (data.count || 0) + ' chi phi</div></div>';

      if (!expenses.length) html += '<div class="empty-state p-3"><div class="empty-icon">💸</div><div class="empty-text">Chua co chi phi chung</div></div>';
      for (var i = 0; i < expenses.length; i++) {
        var e = expenses[i];
        html += '<div class="card mb-2" style="padding:10px"><div class="flex justify-between"><span class="text-sm">' + (catIcons[e.category] || '📎') + ' ' + SS.utils.esc(e.description) + '</span>'
          + '<span class="font-bold text-sm" style="color:var(--danger)">-' + SS.utils.formatMoney(e.amount) + 'd</span></div>'
          + '<div class="text-xs text-muted">Tra boi: ' + SS.utils.esc(e.payer_name || '') + ' · ' + SS.utils.formatMoney(e.per_person || 0) + 'd/nguoi · ' + SS.utils.ago(e.created_at) + '</div></div>';
      }
      SS.ui.sheet({title: '💸 Chia chi phi', html: html});
    });
  },
  add: function(convId) {
    SS.ui.modal({title: 'Them chi phi', html: '<input id="es-desc" class="form-input mb-2" placeholder="Mo ta (VD: Do xang)">'
      + '<input id="es-amount" class="form-input mb-2" type="number" placeholder="So tien (VND)">'
      + '<select id="es-cat" class="form-select"><option value="fuel">⛽ Xang</option><option value="toll">🛣️ Phi cau duong</option><option value="parking">🅿️ Gui xe</option><option value="food">🍜 An uong</option><option value="repair">🔧 Sua xe</option><option value="other">📎 Khac</option></select>', confirmText: 'Them',
      onConfirm: function() {
        SS.api.post('/expense-splitter.php', {conversation_id: convId, description: document.getElementById('es-desc').value, amount: parseInt(document.getElementById('es-amount').value) || 0, category: document.getElementById('es-cat').value, split_with: []}).then(function(d) { SS.ui.toast('OK', 'success'); SS.ExpenseSplitter.show(convId); });
      }
    });
  },
  settle: function(convId) { SS.api.post('/expense-splitter.php?action=settle', {conversation_id: convId}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ExpenseSplitter.show(convId); }); }
};
