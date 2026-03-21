/**
 * ShipperShop Page — Map (map.html)
 * Location picker, nearby traffic alerts
 * Uses: SS.api, SS.ui, SS.LocationPicker
 */
window.SS = window.SS || {};

SS.MapPage = {

  init: function() {
    // Load traffic pins for map
    SS.MapPage.loadPins();
  },

  loadPins: function() {
    SS.api.get('/traffic.php?action=map_data').then(function(d) {
      var pins = d.data || [];
      var el = document.getElementById('mp-pins-count');
      if (el) el.textContent = pins.length + ' cảnh báo giao thông';
    }).catch(function() {});
  },

  // Ask location modal
  askLocation: function() {
    var html = '<div style="text-align:center;margin-bottom:16px"><div style="font-size:48px">📍</div><p class="text-muted text-sm">Chọn khu vực để xem thông tin giao thông</p></div>'
      + '<div class="form-group"><select id="mp-prov" class="form-select"><option value="">Tỉnh/TP</option></select></div>'
      + '<div class="form-group"><select id="mp-dist" class="form-select"><option value="">Quận/Huyện</option></select></div>'
      + '<div class="form-group"><select id="mp-ward" class="form-select"><option value="">Xã/Phường</option></select></div>';

    SS.ui.modal({
      title: 'Chọn khu vực',
      html: html,
      confirmText: 'Xem',
      onConfirm: function() {
        if (SS.LocationPicker) {
          var loc = SS.LocationPicker.getSelected();
          if (loc.province) {
            window.location.href = '/traffic.html?province=' + encodeURIComponent(loc.province) + '&district=' + encodeURIComponent(loc.district || '');
          }
        }
      }
    });

    if (SS.LocationPicker) {
      setTimeout(function() { SS.LocationPicker.init('mp-prov', 'mp-dist', 'mp-ward'); }, 100);
    }
  }
};
