/**
 * ShipperShop Component — Route Planner
 */
window.SS = window.SS || {};

SS.RoutePlanner = {
  show: function() {
    SS.api.get('/route-planner.php').then(function(d) {
      var routes = (d.data || {}).routes || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.RoutePlanner.create()"><i class="fa-solid fa-plus"></i> Tao tuyen moi</button>';
      if (!routes.length) { html += '<div class="empty-state p-3"><div class="empty-icon">🗺️</div><div class="empty-text">Chua co tuyen duong</div></div>'; }
      for (var i = 0; i < routes.length; i++) {
        var r = routes[i];
        html += '<div class="card mb-2" style="padding:12px;border-left:3px solid var(--primary)"><div class="flex justify-between"><div class="font-bold text-sm">' + SS.utils.esc(r.name) + '</div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.RoutePlanner.del(' + r.id + ')"><i class="fa-solid fa-trash text-danger" style="font-size:11px"></i></button></div>'
          + '<div class="text-xs text-muted">' + r.stop_count + ' diem dung · ' + SS.utils.ago(r.created_at) + '</div>';
        var stops = r.stops || [];
        html += '<div style="margin-top:6px;padding-left:8px;border-left:2px solid var(--border)">';
        for (var s = 0; s < Math.min(stops.length, 5); s++) {
          html += '<div class="text-xs" style="padding:2px 0">📍 ' + SS.utils.esc(stops[s].address || 'Diem ' + (s + 1)) + (stops[s].note ? ' · ' + SS.utils.esc(stops[s].note) : '') + '</div>';
        }
        if (stops.length > 5) html += '<div class="text-xs text-muted">+' + (stops.length - 5) + ' diem nua</div>';
        html += '</div></div>';
      }
      SS.ui.sheet({title: '🗺️ Tuyen duong (' + routes.length + ')', html: html});
    });
  },
  create: function() {
    SS.ui.modal({title: 'Tao tuyen duong', html: '<input id="rp-name" class="form-input mb-2" placeholder="Ten tuyen (VD: Tuyen Q7-Q1)">'
      + '<input id="rp-s1" class="form-input mb-1" placeholder="Diem 1 (bat dau)"><input id="rp-s2" class="form-input mb-1" placeholder="Diem 2">'
      + '<input id="rp-s3" class="form-input mb-1" placeholder="Diem 3 (tuy chon)"><input id="rp-s4" class="form-input" placeholder="Diem 4 (tuy chon)">', confirmText: 'Luu',
      onConfirm: function() {
        var stops = [document.getElementById('rp-s1').value, document.getElementById('rp-s2').value, document.getElementById('rp-s3').value, document.getElementById('rp-s4').value].filter(function(s) { return s.trim(); }).map(function(s) { return {address: s}; });
        SS.api.post('/route-planner.php', {name: document.getElementById('rp-name').value, stops: stops}).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); SS.RoutePlanner.show(); });
      }
    });
  },
  del: function(id) { SS.api.post('/route-planner.php?action=delete', {route_id: id}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.RoutePlanner.show(); }); }
};
