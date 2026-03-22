/**
 * ShipperShop Component — Fuel Tracker
 */
window.SS = window.SS || {};

SS.FuelTracker = {
  show: function() {
    SS.api.get('/fuel-tracker.php').then(function(d) {
      var data = d.data || {};
      var sum = data.summary || {};
      var entries = data.entries || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.FuelTracker.add()"><i class="fa-solid fa-plus"></i> Them do xang</button>';
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--danger)">' + SS.utils.formatMoney(sum.month_cost || 0) + 'd</div><div class="text-xs text-muted">Thang nay</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (sum.cost_per_km || 0) + 'd</div><div class="text-xs text-muted">/km</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--success)">' + (sum.km_per_liter || 0) + '</div><div class="text-xs text-muted">km/L</div></div></div>';
      for (var i = 0; i < Math.min(entries.length, 10); i++) {
        var e = entries[i];
        html += '<div class="flex justify-between items-center p-2" style="border-bottom:1px solid var(--border-light)"><div><div class="text-sm font-bold" style="color:var(--danger)">-' + SS.utils.formatMoney(e.cost) + 'd</div>'
          + '<div class="text-xs text-muted">' + e.date + (e.liters ? ' · ' + e.liters + 'L' : '') + (e.km ? ' · ' + e.km + 'km' : '') + (e.station ? ' · ' + SS.utils.esc(e.station) : '') + '</div></div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.FuelTracker.del(' + e.id + ')"><i class="fa-solid fa-xmark text-muted" style="font-size:10px"></i></button></div>';
      }
      SS.ui.sheet({title: '⛽ Chi phi xang', html: html});
    });
  },
  add: function() {
    SS.ui.modal({title: 'Do xang', html: '<input id="ft-cost" class="form-input mb-2" type="number" placeholder="So tien (VND)">'
      + '<input id="ft-liters" class="form-input mb-2" type="number" step="0.1" placeholder="So lit">'
      + '<input id="ft-km" class="form-input mb-2" type="number" placeholder="So km da di">'
      + '<input id="ft-station" class="form-input mb-2" placeholder="Tram xang (tuy chon)">'
      + '<input id="ft-date" class="form-input" type="date" value="' + new Date().toISOString().split('T')[0] + '">', confirmText: 'Them',
      onConfirm: function() {
        SS.api.post('/fuel-tracker.php', {cost: parseInt(document.getElementById('ft-cost').value)||0, liters: parseFloat(document.getElementById('ft-liters').value)||0, km: parseFloat(document.getElementById('ft-km').value)||0, station: document.getElementById('ft-station').value, date: document.getElementById('ft-date').value}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.FuelTracker.show(); });
      }
    });
  },
  del: function(id) { SS.api.post('/fuel-tracker.php?action=delete', {entry_id: id}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.FuelTracker.show(); }); }
};
