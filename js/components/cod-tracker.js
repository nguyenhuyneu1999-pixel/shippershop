/**
 * ShipperShop Component — COD Tracker
 */
window.SS = window.SS || {};

SS.CodTracker = {
  show: function() {
    SS.api.get('/cod-tracker.php').then(function(d) {
      var data = d.data || {};
      var sum = data.summary || {};
      var entries = data.entries || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.CodTracker.add()"><i class="fa-solid fa-plus"></i> Thu COD</button>';
      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px;background:linear-gradient(135deg,var(--success)15,transparent)"><div class="font-bold text-lg" style="color:var(--success)">' + SS.utils.formatMoney(sum.today || 0) + 'd</div><div class="text-xs text-muted">Hom nay</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--warning)">' + SS.utils.formatMoney(sum.collected || 0) + 'd</div><div class="text-xs text-muted">Chua nop (' + (sum.total_orders || 0) + ' don)</div></div></div>';
      if (sum.collected > 0) {
        html += '<button class="btn btn-ghost btn-sm mb-3" onclick="SS.CodTracker.remitAll()" style="width:100%">✅ Nop tat ca COD</button>';
      }
      for (var i = 0; i < Math.min(entries.length, 10); i++) {
        var e = entries[i];
        var statusIcon = e.status === 'remitted' ? '✅' : (e.status === 'collected' ? '💰' : '⏳');
        html += '<div class="flex justify-between items-center p-2" style="border-bottom:1px solid var(--border-light)"><div><div class="text-sm font-bold">' + statusIcon + ' ' + SS.utils.formatMoney(e.amount) + 'd</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(e.order_id || '') + (e.customer ? ' · ' + SS.utils.esc(e.customer) : '') + ' · ' + e.date + '</div></div>'
          + (e.status === 'collected' ? '<button class="btn btn-ghost btn-sm" onclick="SS.CodTracker.remit(' + e.id + ')">Nop</button>' : '') + '</div>';
      }
      SS.ui.sheet({title: '💰 COD Tracker', html: html});
    });
  },
  add: function() {
    SS.ui.modal({title: 'Thu COD', html: '<input id="cod-amt" class="form-input mb-2" type="number" placeholder="So tien (VND)"><input id="cod-oid" class="form-input mb-2" placeholder="Ma don hang"><input id="cod-cust" class="form-input" placeholder="Ten khach (tuy chon)">', confirmText: 'Them',
      onConfirm: function() {
        SS.api.post('/cod-tracker.php', {amount: parseInt(document.getElementById('cod-amt').value)||0, order_id: document.getElementById('cod-oid').value, customer: document.getElementById('cod-cust').value}).then(function() { SS.ui.toast('OK', 'success'); SS.CodTracker.show(); });
      }
    });
  },
  remit: function(id) { SS.api.post('/cod-tracker.php?action=remit', {entry_id: id}).then(function() { SS.ui.toast('Da nop', 'success'); SS.CodTracker.show(); }); },
  remitAll: function() { SS.api.post('/cod-tracker.php?action=remit_all', {}).then(function() { SS.ui.toast('Da nop tat ca', 'success'); SS.CodTracker.show(); }); }
};
