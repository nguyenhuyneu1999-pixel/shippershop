/**
 * ShipperShop Component — Income Tracker
 */
window.SS = window.SS || {};

SS.IncomeTracker = {
  show: function() {
    SS.api.get('/income-tracker.php').then(function(d) {
      var data = d.data || {};
      var sum = data.summary || {};
      var entries = data.entries || [];

      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.IncomeTracker.add()"><i class="fa-solid fa-plus"></i> Them thu nhap</button>';

      // Summary cards
      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px;background:linear-gradient(135deg,var(--primary),#6d28d9);color:#fff;border-radius:10px"><div class="text-xs" style="opacity:.8">Hom nay</div><div class="font-bold">' + SS.utils.formatMoney(sum.today || 0) + 'd</div></div>'
        + '<div class="card" style="padding:10px"><div class="text-xs text-muted">Tuan nay</div><div class="font-bold" style="color:var(--success)">' + SS.utils.formatMoney(sum.week || 0) + 'd</div></div>'
        + '<div class="card" style="padding:10px"><div class="text-xs text-muted">Thang nay</div><div class="font-bold" style="color:var(--primary)">' + SS.utils.formatMoney(sum.month || 0) + 'd</div></div>'
        + '<div class="card" style="padding:10px"><div class="text-xs text-muted">TB/ngay</div><div class="font-bold">' + SS.utils.formatMoney(sum.avg_daily || 0) + 'd</div></div></div>';

      // Entries
      if (entries.length) {
        html += '<div class="text-sm font-bold mb-2">Lich su</div>';
        for (var i = 0; i < Math.min(entries.length, 15); i++) {
          var e = entries[i];
          html += '<div class="flex justify-between items-center p-2" style="border-bottom:1px solid var(--border-light)">'
            + '<div><div class="text-sm font-bold" style="color:var(--success)">+' + SS.utils.formatMoney(e.amount) + 'd</div>'
            + '<div class="text-xs text-muted">' + SS.utils.esc(e.date || '') + (e.deliveries ? ' · ' + e.deliveries + ' don' : '') + (e.note ? ' · ' + SS.utils.esc(e.note) : '') + '</div></div>'
            + '<button class="btn btn-ghost btn-sm" onclick="SS.IncomeTracker.del(' + e.id + ')"><i class="fa-solid fa-xmark text-muted" style="font-size:10px"></i></button></div>';
        }
      }
      SS.ui.sheet({title: '💰 Thu nhap Shipper', html: html});
    });
  },

  add: function() {
    SS.ui.modal({
      title: 'Them thu nhap',
      html: '<input id="it-amount" class="form-input mb-2" type="number" placeholder="So tien (VND)">'
        + '<input id="it-deliveries" class="form-input mb-2" type="number" placeholder="So don giao">'
        + '<input id="it-date" class="form-input mb-2" type="date" value="' + new Date().toISOString().split('T')[0] + '">'
        + '<input id="it-note" class="form-input" placeholder="Ghi chu (tuy chon)">',
      confirmText: 'Them',
      onConfirm: function() {
        SS.api.post('/income-tracker.php', {
          amount: parseInt(document.getElementById('it-amount').value) || 0,
          deliveries: parseInt(document.getElementById('it-deliveries').value) || 0,
          date: document.getElementById('it-date').value,
          note: document.getElementById('it-note').value
        }).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); SS.IncomeTracker.show(); });
      }
    });
  },

  del: function(id) {
    SS.api.post('/income-tracker.php?action=delete', {entry_id: id}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.IncomeTracker.show(); });
  }
};
