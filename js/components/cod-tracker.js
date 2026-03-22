/**
 * ShipperShop Component — COD Tracker
 */
window.SS = window.SS || {};

SS.CodTracker = {
  show: function() {
    SS.api.get('/cod-tracker.php').then(function(d) {
      var data = d.data || {};
      var entries = data.entries || [];
      var stats = data.stats || {};
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.CodTracker.collect()"><i class="fa-solid fa-plus"></i> Thu COD</button>';
      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px;background:linear-gradient(135deg,var(--warning)15,transparent)"><div class="font-bold text-lg" style="color:var(--warning)">' + SS.utils.formatMoney(stats.pending || 0) + 'd</div><div class="text-xs text-muted">Cho nop</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--success)">' + SS.utils.formatMoney(stats.total_deposited || 0) + 'd</div><div class="text-xs text-muted">Da nop</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--primary)">' + SS.utils.formatMoney(stats.today_collected || 0) + 'd</div><div class="text-xs text-muted">Hom nay</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold">' + (stats.entries || 0) + '</div><div class="text-xs text-muted">Don</div></div></div>';
      if (stats.pending > 0) html += '<button class="btn btn-ghost btn-sm mb-3" onclick="SS.CodTracker.depositAll()">💰 Nop tat ca COD cho hang</button>';
      var statusIcons = {collected: '💵', deposited: '✅'};
      for (var i = 0; i < Math.min(entries.length, 15); i++) {
        var e = entries[i];
        html += '<div class="flex justify-between items-center p-2" style="border-bottom:1px solid var(--border-light)"><div><div class="text-sm font-bold">' + (statusIcons[e.status] || '') + ' ' + SS.utils.formatMoney(e.amount) + 'd</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(e.recipient || '') + (e.company ? ' · ' + SS.utils.esc(e.company) : '') + ' · ' + SS.utils.ago(e.created_at) + '</div></div>'
          + (e.status === 'collected' ? '<button class="btn btn-ghost btn-sm" onclick="SS.CodTracker.deposit(' + e.id + ')" style="font-size:10px">Nop</button>' : '<span class="text-xs" style="color:var(--success)">✅</span>') + '</div>';
      }
      SS.ui.sheet({title: '💰 COD Tracker', html: html});
    });
  },
  collect: function() {
    SS.ui.modal({title: 'Thu COD', html: '<input id="cod-amt" class="form-input mb-2" type="number" placeholder="So tien COD"><input id="cod-rec" class="form-input mb-2" placeholder="Nguoi nhan"><input id="cod-ord" class="form-input mb-2" placeholder="Ma don (tuy chon)"><input id="cod-com" class="form-input" placeholder="Hang (GHTK, GHN...)">', confirmText: 'Thu',
      onConfirm: function() {
        SS.api.post('/cod-tracker.php', {amount: parseInt(document.getElementById('cod-amt').value) || 0, recipient: document.getElementById('cod-rec').value, order_id: document.getElementById('cod-ord').value, company: document.getElementById('cod-com').value}).then(function(d) { SS.ui.toast('OK', 'success'); SS.CodTracker.show(); });
      }
    });
  },
  deposit: function(id) { SS.api.post('/cod-tracker.php?action=deposit', {entry_id: id}).then(function() { SS.ui.toast('Da nop', 'success'); SS.CodTracker.show(); }); },
  depositAll: function() { SS.api.post('/cod-tracker.php?action=deposit_all', {}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.CodTracker.show(); }); }
};
