window.SS = window.SS || {};
SS.MileageLog = {
  show: function() {
    SS.api.get('/mileage-log.php').then(function(d) {
      var data = d.data || {};
      var stats = data.stats || {};
      var entries = data.entries || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.MileageLog.add()"><i class="fa-solid fa-road"></i> Ghi km</button>';
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:12px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (stats.today || 0) + '</div><div class="text-muted">km nay</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (stats.week || 0) + '</div><div class="text-muted">km tuan</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (stats.month || 0) + '</div><div class="text-muted">km thang</div></div></div>';
      html += '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:8px;flex:1"><div class="font-bold" style="color:var(--primary)">' + SS.utils.formatMoney(stats.cost_per_km || 0) + 'd/km</div></div>'
        + '<div class="card" style="padding:8px;flex:1"><div class="font-bold">' + SS.utils.formatMoney(stats.fuel_month || 0) + 'd xang</div></div></div>';
      for (var i = 0; i < Math.min(entries.length, 10); i++) {
        var e = entries[i];
        html += '<div class="flex justify-between text-xs p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(e.date) + (e.note ? ' · ' + SS.utils.esc(e.note) : '') + '</span><span class="font-bold">' + e.km + ' km</span></div>';
      }
      SS.ui.sheet({title: '🛣️ So km (' + (stats.total || 0) + ' km)', html: html});
    });
  },
  add: function() {
    SS.ui.modal({title: 'Ghi km', html: '<input id="ml-km" class="form-input mb-2" type="number" placeholder="So km" step="0.1"><input id="ml-odo" class="form-input mb-2" type="number" placeholder="Odometer (tuy chon)"><input id="ml-note" class="form-input" placeholder="Ghi chu">', confirmText: 'Luu',
      onConfirm: function() { SS.api.post('/mileage-log.php', {km: parseFloat(document.getElementById('ml-km').value), odometer: parseInt(document.getElementById('ml-odo').value) || 0, note: document.getElementById('ml-note').value}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.MileageLog.show(); }); }
    });
  }
};
