window.SS = window.SS || {};
SS.DeliveryZones = {
  show: function() {
    SS.api.get('/delivery-zones.php').then(function(d) {
      var data = d.data || {};
      var zones = data.zones || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.DeliveryZones.add()"><i class="fa-solid fa-map-pin"></i> Them khu vuc</button>';
      html += '<div class="text-xs text-muted mb-2">' + (data.active || 0) + '/' + (data.count || 0) + ' khu vuc dang hoat dong</div>';
      if (!zones.length) html += '<div class="empty-state p-3"><div class="empty-icon">🗺️</div><div class="empty-text">Chua co khu vuc giao hang</div></div>';
      for (var i = 0; i < zones.length; i++) {
        var z = zones[i];
        html += '<div class="card mb-2" style="padding:10px;opacity:' + (z.active ? '1' : '0.5') + '">'
          + '<div class="flex justify-between"><span class="font-bold text-sm">' + (z.active ? '🟢' : '⚪') + ' ' + SS.utils.esc(z.name) + '</span>'
          + '<div class="flex gap-1"><button class="btn btn-ghost btn-sm" onclick="SS.DeliveryZones.toggle(' + z.id + ')" style="font-size:10px">' + (z.active ? '⏸' : '▶') + '</button>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.DeliveryZones.del(' + z.id + ')" style="font-size:10px">🗑</button></div></div>'
          + '<div class="text-xs text-muted mt-1">💰 ' + SS.utils.formatMoney(z.base_price) + 'd + ' + SS.utils.formatMoney(z.price_per_km) + 'd/km</div>'
          + (z.districts && z.districts.length ? '<div class="flex gap-1 flex-wrap mt-1">' + z.districts.map(function(dd) { return '<span class="chip" style="font-size:10px">' + SS.utils.esc(dd) + '</span>'; }).join('') + '</div>' : '') + '</div>';
      }
      SS.ui.sheet({title: '🗺️ Khu vuc giao hang', html: html});
    });
  },
  add: function() {
    SS.ui.modal({title: 'Them khu vuc', html: '<input id="dz-name" class="form-input mb-2" placeholder="Ten khu vuc (VD: Noi thanh Q1-Q10)"><input id="dz-base" class="form-input mb-2" type="number" placeholder="Gia co ban (VND)" value="15000"><input id="dz-km" class="form-input" type="number" placeholder="Gia/km (VND)" value="4000">', confirmText: 'Them',
      onConfirm: function() { SS.api.post('/delivery-zones.php', {name: document.getElementById('dz-name').value, base_price: parseInt(document.getElementById('dz-base').value), price_per_km: parseInt(document.getElementById('dz-km').value)}).then(function() { SS.DeliveryZones.show(); }); }
    });
  },
  toggle: function(id) { SS.api.post('/delivery-zones.php?action=toggle', {zone_id: id}).then(function() { SS.DeliveryZones.show(); }); },
  del: function(id) { SS.api.post('/delivery-zones.php?action=delete', {zone_id: id}).then(function() { SS.DeliveryZones.show(); }); }
};
