/**
 * ShipperShop Component — Delivery Stats V2
 */
window.SS = window.SS || {};

SS.DeliveryStatsV2 = {
  show: function(userId, days) {
    days = days || 30;
    var url = '/delivery-stats-v2.php?days=' + days + (userId ? '&user_id=' + userId : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var html = '<div class="flex gap-2 mb-3">';
      [7, 30, 90].forEach(function(dd) {
        html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.DeliveryStatsV2.show(' + (userId || 'null') + ',' + dd + ')" style="cursor:pointer">' + dd + 'd</div>';
      });
      html += '</div>';
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--primary)">' + SS.utils.fN(data.total_posts || 0) + '</div><div class="text-xs text-muted">Don</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--success)">' + SS.utils.fN(data.total_likes || 0) + '</div><div class="text-xs text-muted">Likes</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.avg_likes || 0) + '</div><div class="text-xs text-muted">TB/don</div></div></div>';
      // Peak hours
      var peaks = data.peak_hours || [];
      if (peaks.length) {
        html += '<div class="text-sm font-bold mb-1">Gio cao diem</div><div class="flex gap-2 mb-3">';
        for (var i = 0; i < peaks.length; i++) html += '<div class="chip">' + peaks[i].h + ':00 (' + peaks[i].c + ')</div>';
        html += '</div>';
      }
      // Companies
      var companies = data.by_company || [];
      if (companies.length) {
        html += '<div class="text-sm font-bold mb-1">Hang van chuyen</div>';
        for (var j = 0; j < Math.min(companies.length, 5); j++) {
          var c = companies[j];
          html += '<div class="flex justify-between text-sm p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(c.company) + '</span><span class="font-bold">' + c.posts + ' don</span></div>';
        }
      }
      SS.ui.sheet({title: 'Thong ke giao hang', html: html});
    });
  }
};
