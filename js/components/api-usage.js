/**
 * ShipperShop Component — API Usage Monitor (Admin)
 */
window.SS = window.SS || {};

SS.ApiUsage = {
  show: function() {
    SS.api.get('/api-usage.php').then(function(d) {
      var data = d.data || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.total_apis || 0) + '</div><div class="text-xs text-muted">APIs</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.calls_last_hour || 0) + '</div><div class="text-xs text-muted">Calls/1h</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:' + ((data.error_rate_24h || 0) > 5 ? 'var(--danger)' : 'var(--success)') + '">' + (data.error_rate_24h || 0) + '%</div><div class="text-xs text-muted">Error 24h</div></div></div>';

      // Top pages
      var pages = data.top_pages || [];
      if (pages.length) {
        html += '<div class="text-sm font-bold mb-2">Top trang</div>';
        var maxV = pages[0] ? parseInt(pages[0].views) : 1;
        for (var i = 0; i < pages.length; i++) {
          var p = pages[i];
          var w = Math.max(10, Math.round(parseInt(p.views) / maxV * 100));
          html += '<div class="mb-1"><div class="flex justify-between text-xs mb-1"><span>' + SS.utils.esc(p.page || '') + '</span><span class="font-bold">' + p.views + '</span></div>'
            + '<div style="height:6px;background:var(--border-light);border-radius:3px"><div style="width:' + w + '%;height:100%;background:var(--primary);border-radius:3px"></div></div></div>';
        }
      }
      SS.ui.sheet({title: 'API Usage', html: html});
    });
  }
};
