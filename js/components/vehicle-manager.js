/**
 * ShipperShop Component — Vehicle Manager
 */
window.SS = window.SS || {};

SS.VehicleManager = {
  show: function() {
    SS.api.get('/vehicle-manager.php').then(function(d) {
      var data = d.data || {};
      var vehicles = data.vehicles || [];
      var types = data.types || [];
      var typeMap = {};
      for (var t = 0; t < types.length; t++) typeMap[types[t].id] = types[t];

      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.VehicleManager.add()"><i class="fa-solid fa-plus"></i> Them xe</button>';
      if (!vehicles.length) html += '<div class="empty-state p-3"><div class="empty-icon">🏍️</div><div class="empty-text">Chua co xe</div></div>';

      for (var i = 0; i < vehicles.length; i++) {
        var v = vehicles[i];
        var typeInfo = typeMap[v.type] || {icon: '🏍️', name: v.type};
        var mCount = (v.maintenance || []).length;
        var mCost = 0;
        for (var m = 0; m < (v.maintenance || []).length; m++) mCost += parseInt(v.maintenance[m].cost || 0);

        html += '<div class="card mb-2" style="padding:12px;border-left:3px solid var(--primary)">'
          + '<div class="flex justify-between items-start"><div><div class="font-bold text-sm">' + typeInfo.icon + ' ' + SS.utils.esc(v.name) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(v.plate || 'Chua co BKS') + ' · ' + v.year + ' · ' + (v.total_km || 0) + ' km</div></div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.VehicleManager.del(' + v.id + ')"><i class="fa-solid fa-trash text-danger" style="font-size:11px"></i></button></div>'
          + '<div class="flex gap-2 mt-2"><button class="btn btn-ghost btn-sm" onclick="SS.VehicleManager.addMaint(' + v.id + ')">🔧 Bao tri (' + mCount + ')</button>'
          + '<span class="text-xs text-muted" style="line-height:28px">Chi phi: ' + SS.utils.formatMoney(mCost) + 'd</span></div></div>';
      }
      SS.ui.sheet({title: '🏍️ Xe cua toi (' + vehicles.length + '/5)', html: html});
    });
  },
  add: function() {
    SS.ui.modal({title: 'Them xe', html: '<input id="vm-name" class="form-input mb-2" placeholder="Ten xe (VD: Wave Alpha)">'
      + '<select id="vm-type" class="form-select mb-2"><option value="motorbike">🏍️ Xe may</option><option value="ebike">🔋 Xe dien</option><option value="car">🚗 O to</option><option value="truck_s">🚛 Xe tai nho</option><option value="truck_l">🚚 Xe tai lon</option><option value="bicycle">🚲 Xe dap</option></select>'
      + '<input id="vm-plate" class="form-input mb-2" placeholder="Bien so xe"><input id="vm-year" class="form-input" type="number" placeholder="Nam SX" value="2020">', confirmText: 'Them',
      onConfirm: function() {
        SS.api.post('/vehicle-manager.php', {name: document.getElementById('vm-name').value, type: document.getElementById('vm-type').value, plate: document.getElementById('vm-plate').value, year: parseInt(document.getElementById('vm-year').value)}).then(function() { SS.ui.toast('OK', 'success'); SS.VehicleManager.show(); });
      }
    });
  },
  addMaint: function(vehicleId) {
    SS.ui.modal({title: 'Bao tri', html: '<input id="vm-desc" class="form-input mb-2" placeholder="Mo ta (VD: Thay nhot)"><input id="vm-cost" class="form-input" type="number" placeholder="Chi phi (VND)">', confirmText: 'Luu',
      onConfirm: function() {
        SS.api.post('/vehicle-manager.php?action=maintenance', {vehicle_id: vehicleId, description: document.getElementById('vm-desc').value, cost: parseInt(document.getElementById('vm-cost').value) || 0}).then(function() { SS.ui.toast('OK', 'success'); SS.VehicleManager.show(); });
      }
    });
  },
  del: function(id) { SS.api.post('/vehicle-manager.php?action=delete', {vehicle_id: id}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.VehicleManager.show(); }); }
};
