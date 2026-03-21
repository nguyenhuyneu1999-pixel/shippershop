/**
 * ShipperShop Component — Shipper Map Data
 * Shows active shippers on map, province heatmap
 */
window.SS = window.SS || {};

SS.ShipperMapData = {
  loadPins: function(callback) {
    SS.api.get('/shipper-map.php?action=pins&limit=100').then(function(d) {
      if (callback) callback((d.data || {}).pins || []);
    });
  },

  showProvinceHeat: function() {
    SS.api.get('/shipper-map.php?action=province_heat').then(function(d) {
      var provinces = d.data || [];
      var html = '<div class="text-sm font-bold mb-2">Phan bo shipper theo tinh/thanh</div>';
      var maxCount = provinces.length ? provinces[0].shippers : 1;
      for (var i = 0; i < provinces.length; i++) {
        var p = provinces[i];
        var width = Math.round(intval(p.shippers) / maxCount * 100);
        html += '<div class="flex items-center gap-2 mb-1"><span class="text-xs" style="width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + SS.utils.esc(p.province) + '</span>'
          + '<div style="flex:1;height:16px;background:var(--border-light);border-radius:4px;overflow:hidden"><div style="width:' + width + '%;height:100%;background:var(--primary);border-radius:4px"></div></div>'
          + '<span class="text-xs font-bold" style="width:30px;text-align:right">' + p.shippers + '</span></div>';
      }
      SS.ui.sheet({title: 'Ban do Shipper', html: html});
    });
  }
};

function intval(v) { return parseInt(v) || 0; }
