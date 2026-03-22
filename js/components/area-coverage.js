/**
 * ShipperShop Component — Area Coverage Map
 */
window.SS = window.SS || {};

SS.AreaCoverage = {
  show: function() {
    SS.api.get('/area-coverage.php').then(function(d) {
      var data = d.data || {};
      var stats = data.stats || {};
      var provinces = data.provinces || [];

      var html = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--primary)">' + (stats.total_provinces || 0) + '</div><div class="text-xs text-muted">Tinh</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (stats.total_shippers || 0) + '</div><div class="text-xs text-muted">Shipper</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--success)">' + (stats.high_density || 0) + '</div><div class="text-xs text-muted">Dong</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--warning)">' + (stats.low_density || 0) + '</div><div class="text-xs text-muted">Thua</div></div></div>';

      if (provinces.length) {
        var maxS = parseInt(provinces[0].shippers) || 1;
        html += '<div class="text-sm font-bold mb-2">Phu song theo tinh</div>';
        for (var i = 0; i < Math.min(provinces.length, 12); i++) {
          var p = provinces[i];
          var w = Math.max(8, Math.round(parseInt(p.shippers) / maxS * 100));
          var color = parseInt(p.shippers) >= 10 ? 'var(--success)' : (parseInt(p.shippers) >= 3 ? 'var(--primary)' : 'var(--warning)');
          html += '<div class="mb-1"><div class="flex justify-between text-xs"><span>' + SS.utils.esc(p.province) + '</span><span class="font-bold">' + p.shippers + ' shipper</span></div>'
            + '<div style="height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + w + '%;height:100%;background:' + color + ';border-radius:4px"></div></div></div>';
        }
      }
      html += '<button class="btn btn-ghost btn-sm mt-3" onclick="SS.AreaCoverage.showGaps()">📍 Khu vuc trong</button>';
      SS.ui.sheet({title: '🗺️ Phu song khu vuc', html: html});
    });
  },
  showGaps: function() {
    SS.ui.closeSheet();
    SS.api.get('/area-coverage.php?action=gaps').then(function(d) {
      var data = d.data || {};
      var gaps = data.gaps || [];
      var html = '<div class="text-center mb-3"><div class="font-bold text-lg">' + (data.covered || 0) + '/' + (data.total || 63) + '</div><div class="text-xs text-muted">tinh da phu song</div></div>';
      html += '<div class="text-sm font-bold mb-2" style="color:var(--warning)">' + gaps.length + ' tinh chua co shipper</div><div class="flex gap-2 flex-wrap">';
      for (var i = 0; i < gaps.length; i++) html += '<span class="chip" style="font-size:11px">' + SS.utils.esc(gaps[i]) + '</span>';
      html += '</div>';
      SS.ui.sheet({title: '📍 Khu vuc trong (' + gaps.length + ')', html: html});
    });
  }
};
