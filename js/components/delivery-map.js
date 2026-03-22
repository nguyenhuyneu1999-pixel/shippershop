/**
 * ShipperShop Component — Delivery Map
 * Delivery hotspots by province/district
 */
window.SS = window.SS || {};

SS.DeliveryMap = {
  show: function() {
    SS.api.get('/delivery-map.php').then(function(d) {
      var data = d.data || {};
      var provinces = data.by_province || [];

      var html = '<div class="card mb-3" style="padding:12px;text-align:center"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.total_with_location || 0) + '</div><div class="text-xs text-muted">Bai viet co dia diem</div></div>';

      if (provinces.length) {
        var maxP = parseInt(provinces[0].posts) || 1;
        html += '<div class="text-sm font-bold mb-2">Top tinh/thanh</div>';
        for (var i = 0; i < provinces.length; i++) {
          var p = provinces[i];
          var w = Math.max(10, Math.round(parseInt(p.posts) / maxP * 100));
          html += '<div class="mb-2" style="cursor:pointer" onclick="SS.DeliveryMap.showShippers(\'' + SS.utils.esc(p.province).replace(/'/g, '\\x27') + '\')">'
            + '<div class="flex justify-between text-sm mb-1"><span>📍 ' + SS.utils.esc(p.province) + '</span><span class="font-bold">' + p.posts + '</span></div>'
            + '<div style="height:10px;background:var(--border-light);border-radius:5px"><div style="width:' + w + '%;height:100%;background:var(--primary);border-radius:5px"></div></div></div>';
        }
      }
      SS.ui.sheet({title: '🗺️ Ban do giao hang', html: html});
    });
  },

  showShippers: function(province) {
    SS.ui.closeSheet();
    SS.api.get('/delivery-map.php?action=shippers&province=' + encodeURIComponent(province)).then(function(d) {
      var shippers = (d.data || {}).shippers || [];
      var html = '<div class="text-sm text-muted mb-2">Shipper hoat dong tai ' + SS.utils.esc(province) + '</div>';
      for (var i = 0; i < shippers.length; i++) {
        var s = shippers[i];
        html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<img src="' + (s.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-sm" loading="lazy">'
          + '<div class="flex-1"><div class="text-sm font-medium">' + SS.utils.esc(s.fullname) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(s.shipping_company || '') + ' · ' + s.post_count + ' bai</div></div></div>';
      }
      if (!shippers.length) html += '<div class="text-sm text-muted text-center p-3">Khong co shipper</div>';
      SS.ui.sheet({title: '📍 ' + SS.utils.esc(province), html: html});
    });
  },

  showUser: function(userId) {
    SS.api.get('/delivery-map.php?action=user&user_id=' + userId).then(function(d) {
      var areas = (d.data || {}).areas || [];
      var html = '';
      if (!areas.length) { html = '<div class="text-sm text-muted text-center p-3">Chua co khu vuc</div>'; }
      for (var i = 0; i < areas.length; i++) {
        var a = areas[i];
        html += '<div class="flex justify-between p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<span class="text-sm">📍 ' + SS.utils.esc(a.province || '') + (a.district ? ', ' + SS.utils.esc(a.district) : '') + '</span>'
          + '<span class="text-xs font-bold">' + a.posts + ' bai</span></div>';
      }
      SS.ui.sheet({title: 'Khu vuc hoat dong', html: html});
    });
  }
};
