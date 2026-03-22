/**
 * ShipperShop Component — Order Tracker
 */
window.SS = window.SS || {};

SS.OrderTracker = {
  show: function() {
    SS.api.get('/order-tracker.php').then(function(d) {
      var data = d.data || {};
      var orders = data.orders || [];
      var stats = data.stats || {};
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.OrderTracker.create()"><i class="fa-solid fa-plus"></i> Tao don moi</button>';
      html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:12px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:6px"><div class="font-bold">' + (stats.total || 0) + '</div><div class="text-muted">Tong</div></div>'
        + '<div class="card" style="padding:6px"><div class="font-bold" style="color:var(--success)">' + (stats.delivered || 0) + '</div><div class="text-muted">Giao</div></div>'
        + '<div class="card" style="padding:6px"><div class="font-bold" style="color:var(--warning)">' + (stats.in_transit || 0) + '</div><div class="text-muted">Dang</div></div>'
        + '<div class="card" style="padding:6px"><div class="font-bold" style="color:var(--primary)">' + (stats.success_rate || 0) + '%</div><div class="text-muted">Rate</div></div></div>';
      var statusIcons = {picked_up: '📦', in_transit: '🏍️', near_dest: '📍', delivered: '✅', failed: '❌', returned: '↩️'};
      for (var i = 0; i < Math.min(orders.length, 15); i++) {
        var o = orders[i];
        html += '<div class="card mb-2" style="padding:10px"><div class="flex justify-between"><span class="font-bold text-sm">' + (statusIcons[o.status] || '📦') + ' ' + SS.utils.esc(o.id) + '</span><span class="text-xs text-muted">' + SS.utils.esc(o.company || '') + '</span></div>'
          + '<div class="text-xs">' + SS.utils.esc(o.recipient || '') + ' · ' + SS.utils.esc(o.address || '').substring(0, 40) + '</div>'
          + (o.cod ? '<div class="text-xs font-bold" style="color:var(--primary)">COD: ' + SS.utils.formatMoney(o.cod) + 'd</div>' : '')
          + '<div class="text-xs text-muted">' + SS.utils.ago(o.created_at) + '</div></div>';
      }
      if (!orders.length) html += '<div class="empty-state p-3"><div class="empty-icon">📦</div><div class="empty-text">Chua co don</div></div>';
      SS.ui.sheet({title: '📦 Don hang (' + (stats.total || 0) + ')', html: html});
    });
  },
  create: function() {
    SS.ui.modal({title: 'Tao don', html: '<input id="ot-rec" class="form-input mb-2" placeholder="Nguoi nhan"><input id="ot-addr" class="form-input mb-2" placeholder="Dia chi"><input id="ot-phone" class="form-input mb-2" placeholder="SDT"><input id="ot-cod" class="form-input mb-2" type="number" placeholder="COD (d)"><input id="ot-company" class="form-input" placeholder="Hang (GHTK, GHN...)">', confirmText: 'Tao',
      onConfirm: function() {
        SS.api.post('/order-tracker.php', {recipient: document.getElementById('ot-rec').value, address: document.getElementById('ot-addr').value, phone: document.getElementById('ot-phone').value, cod: parseInt(document.getElementById('ot-cod').value) || 0, company: document.getElementById('ot-company').value}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.OrderTracker.show(); });
      }
    });
  }
};
