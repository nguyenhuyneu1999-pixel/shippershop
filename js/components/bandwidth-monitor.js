/**
 * ShipperShop Component — Bandwidth Monitor (Admin)
 */
window.SS = window.SS || {};

SS.BandwidthMonitor = {
  show: function() {
    SS.api.get('/bandwidth-monitor.php').then(function(d) {
      var data = d.data || {};
      var storage = data.storage || [];
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.total_upload_mb || 0) + '</div><div class="text-xs text-muted">Upload MB</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.db_size_mb || 0) + '</div><div class="text-xs text-muted">DB MB</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.api_count || 0) + '</div><div class="text-xs text-muted">APIs</div></div></div>';
      if (storage.length) {
        var maxS = Math.max.apply(null, storage.map(function(s) { return s.size_mb || 0; })) || 1;
        html += '<div class="text-sm font-bold mb-2">Dung luong upload</div>';
        for (var i = 0; i < storage.length; i++) {
          var s = storage[i];
          var w = Math.max(5, Math.round(s.size_mb / maxS * 100));
          html += '<div class="mb-2"><div class="flex justify-between text-xs mb-1"><span>' + SS.utils.esc(s.name) + ' (' + s.files + ' files)</span><span class="font-bold">' + s.size_mb + ' MB</span></div>'
            + '<div style="height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + w + '%;height:100%;background:var(--primary);border-radius:4px"></div></div></div>';
        }
      }
      SS.ui.sheet({title: '📡 Bandwidth', html: html});
    });
  }
};
