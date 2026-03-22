/**
 * ShipperShop Component — Notification Analytics (Admin)
 */
window.SS = window.SS || {};

SS.NotifAnalytics = {
  show: function() {
    SS.api.get('/notif-analytics.php').then(function(d) {
      var data = d.data || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--primary)">' + SS.utils.fN(data.total || 0) + '</div><div class="text-xs text-muted">Tong gui</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--success)">' + (data.read_rate || 0) + '%</div><div class="text-xs text-muted">Ti le doc</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.push_subscriptions || 0) + '</div><div class="text-xs text-muted">Push subs</div></div></div>';
      var byType = data.by_type || [];
      if (byType.length) {
        html += '<div class="text-sm font-bold mb-2">Theo loai</div>';
        for (var i = 0; i < byType.length; i++) {
          var t = byType[i];
          html += '<div class="flex justify-between text-sm p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(t.type) + '</span><span class="text-xs">' + t.count + ' gui · ' + t.read_rate + '% doc</span></div>';
        }
      }
      SS.ui.sheet({title: 'Notification Analytics', html: html});
    });
  }
};
