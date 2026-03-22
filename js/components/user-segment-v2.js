/**
 * ShipperShop Component — User Segmentation V2 (Admin)
 */
window.SS = window.SS || {};

SS.UserSegmentV2 = {
  show: function() {
    SS.api.get('/user-segment-v2.php').then(function(d) {
      var data = d.data || {};
      var segments = data.segments || [];
      var total = data.total || 0;

      var html = '<div class="text-center mb-3"><div class="font-bold text-lg">' + total + '</div><div class="text-xs text-muted">Tong nguoi dung</div></div>';

      // Pie-like breakdown
      html += '<div style="display:flex;height:24px;border-radius:12px;overflow:hidden;margin-bottom:16px">';
      for (var i = 0; i < segments.length; i++) {
        var s = segments[i];
        if (s.pct > 0) html += '<div style="width:' + Math.max(2, s.pct) + '%;background:' + s.color + '" title="' + s.name + ': ' + s.pct + '%"></div>';
      }
      html += '</div>';

      for (var j = 0; j < segments.length; j++) {
        var seg = segments[j];
        html += '<div class="flex items-center gap-3 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<div style="width:10px;height:10px;border-radius:50%;background:' + seg.color + '"></div>'
          + '<span style="font-size:18px">' + seg.icon + '</span>'
          + '<div class="flex-1"><div class="text-sm font-bold">' + SS.utils.esc(seg.name) + '</div><div class="text-xs text-muted">' + SS.utils.esc(seg.desc) + '</div></div>'
          + '<div class="text-right"><div class="font-bold">' + seg.count + '</div><div class="text-xs text-muted">' + seg.pct + '%</div></div></div>';
      }
      SS.ui.sheet({title: 'Phan khuc nguoi dung', html: html});
    });
  }
};
