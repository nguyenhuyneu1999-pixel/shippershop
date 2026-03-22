/**
 * ShipperShop Component — Analytics Export
 * Export personal analytics as charts/data
 */
window.SS = window.SS || {};

SS.AnalyticsExport = {
  show: function(days) {
    days = days || 30;
    SS.api.get('/analytics-export.php?action=my_posts&days=' + days).then(function(d) {
      var data = d.data || {};
      var daily = data.daily || [];

      var html = '<div class="flex gap-2 mb-3">';
      [7, 30, 90].forEach(function(dd) {
        html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.AnalyticsExport.show(' + dd + ')" style="cursor:pointer">' + dd + ' ngay</div>';
      });
      html += '</div>';

      html += '<div class="card mb-3" style="padding:12px;text-align:center"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.total_posts || 0) + '</div><div class="text-xs text-muted">Bai viet trong ' + days + ' ngay</div></div>';

      // Daily chart (simple bar)
      if (daily.length) {
        var maxP = Math.max.apply(null, daily.map(function(dd) { return dd.posts || 0; })) || 1;
        html += '<div class="text-sm font-bold mb-2">Hoat dong hang ngay</div><div style="display:flex;align-items:flex-end;gap:2px;height:60px;margin-bottom:16px">';
        for (var i = 0; i < daily.length; i++) {
          var h = Math.max(4, Math.round((daily[i].posts || 0) / maxP * 56));
          html += '<div style="flex:1;height:' + h + 'px;background:var(--primary);border-radius:2px 2px 0 0" title="' + daily[i].day + ': ' + daily[i].posts + ' bai"></div>';
        }
        html += '</div>';
      }

      // Export button
      html += '<div class="text-center mt-3"><button class="btn btn-primary btn-sm" onclick="SS.AnalyticsExport._download(' + days + ')"><i class="fa-solid fa-download"></i> Tai JSON</button></div>';

      SS.ui.sheet({title: 'Phan tich cua ban', html: html});
    });
  },

  _download: function(days) {
    SS.api.get('/analytics-export.php?action=my_posts&days=' + days).then(function(d) {
      var blob = new Blob([JSON.stringify(d.data, null, 2)], {type: 'application/json'});
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url; a.download = 'shippershop-analytics-' + days + 'd.json'; a.click();
      URL.revokeObjectURL(url);
      SS.ui.toast('Da tai xuong!', 'success');
    });
  }
};
