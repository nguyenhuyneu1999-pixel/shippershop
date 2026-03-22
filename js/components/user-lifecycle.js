/**
 * ShipperShop Component — User Lifecycle (Admin)
 */
window.SS = window.SS || {};

SS.UserLifecycle = {
  show: function() {
    SS.api.get('/user-lifecycle.php').then(function(d) {
      var data = d.data || {};
      var stages = data.stages || [];

      var html = '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:8px;flex:1"><div class="font-bold" style="color:var(--primary)">' + (data.total || 0) + '</div><div class="text-xs text-muted">Tong</div></div>'
        + '<div class="card" style="padding:8px;flex:1"><div class="font-bold" style="color:var(--success)">' + (data.new_this_week || 0) + '</div><div class="text-xs text-muted">Moi tuan nay</div></div>'
        + '<div class="card" style="padding:8px;flex:1"><div class="font-bold">' + (data.avg_hours_to_first_post || 0) + 'h</div><div class="text-xs text-muted">TB den bai 1</div></div></div>';

      html += '<div class="text-sm font-bold mb-2">Hanh trinh nguoi dung</div>';
      for (var i = 0; i < stages.length; i++) {
        var s = stages[i];
        var color = (s.pct !== undefined && s.pct >= 50) ? 'var(--success)' : ((s.pct !== undefined && s.pct >= 20) ? 'var(--primary)' : 'var(--warning)');
        html += '<div class="flex items-center gap-3 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<span style="font-size:20px">' + (s.icon || '') + '</span>'
          + '<div class="flex-1"><div class="text-sm">' + SS.utils.esc(s.stage) + '</div></div>'
          + '<div class="text-right"><div class="font-bold">' + SS.utils.fN(s.count) + '</div>'
          + (s.pct !== undefined ? '<div class="text-xs" style="color:' + color + '">' + s.pct + '%</div>' : '') + '</div></div>';
      }
      SS.ui.sheet({title: 'Vong doi nguoi dung', html: html});
    });
  }
};
