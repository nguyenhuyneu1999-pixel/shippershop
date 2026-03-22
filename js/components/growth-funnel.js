/**
 * ShipperShop Component — Growth Funnel (Admin)
 * User conversion funnel visualization
 */
window.SS = window.SS || {};

SS.GrowthFunnel = {
  show: function(days) {
    days = days || 30;
    SS.api.get('/growth-funnel.php?days=' + days).then(function(d) {
      var data = d.data || {};
      var funnel = data.funnel || [];

      var html = '<div class="flex gap-2 mb-3">';
      [7, 30, 90].forEach(function(dd) {
        html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.GrowthFunnel.show(' + dd + ')" style="cursor:pointer">' + dd + ' ngay</div>';
      });
      html += '</div>';

      // Funnel visualization
      for (var i = 0; i < funnel.length; i++) {
        var f = funnel[i];
        var width = Math.max(30, f.pct);
        var color = f.pct >= 50 ? 'var(--success)' : (f.pct >= 20 ? 'var(--primary)' : 'var(--warning)');
        html += '<div style="margin-bottom:4px"><div class="flex justify-between text-sm mb-1"><span>' + SS.utils.esc(f.stage) + '</span><span class="font-bold">' + f.count + ' (' + f.pct + '%)</span></div>'
          + '<div style="height:28px;background:var(--border-light);border-radius:6px;overflow:hidden;display:flex;align-items:center">'
          + '<div style="width:' + width + '%;height:100%;background:' + color + ';border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:700">' + f.count + '</div></div></div>';
      }

      // Retention
      var ret = data.retention || [];
      if (ret.length) {
        html += '<div class="text-sm font-bold mt-3 mb-2">Giu chan nguoi dung</div><div class="flex gap-2">';
        for (var j = 0; j < ret.length; j++) {
          html += '<div class="card" style="padding:8px;text-align:center;flex:1"><div class="font-bold">' + ret[j].active + '</div><div class="text-xs text-muted">' + SS.utils.esc(ret[j].week) + '</div></div>';
        }
        html += '</div>';
      }

      html += '<div class="text-xs text-muted text-center mt-3">Tong: ' + (data.total_users || 0) + ' users</div>';
      SS.ui.sheet({title: 'Pheu tang truong', html: html});
    });
  }
};
