/**
 * ShipperShop Component — Post Location Map
 * Shows a mini static map for posts with province/district data
 * Also provides location label rendering
 */
window.SS = window.SS || {};

SS.PostLocation = {

  // Render location badge in post meta
  badge: function(province, district, ward) {
    if (!province && !district) return '';
    var parts = [];
    if (ward) parts.push(ward);
    if (district) parts.push(district);
    if (province && !district) parts.push(province);
    var label = parts.join(', ');
    return '<span style="color:#3b82f6;font-size:11px;cursor:pointer" onclick="SS.PostLocation.showMap(\'' + SS.utils.esc(label).replace(/'/g, "\\'") + '\')" title="' + SS.utils.esc(province + ' ' + district) + '">📍 ' + SS.utils.esc(label) + '</span>';
  },

  // Show location on a simple map overlay
  showMap: function(locationText) {
    if (!locationText) return;
    var mapUrl = 'https://www.google.com/maps/search/' + encodeURIComponent(locationText + ', Việt Nam');
    var html = '<div class="text-center">'
      + '<div style="font-size:48px;margin-bottom:12px">📍</div>'
      + '<div class="font-bold text-lg mb-2">' + SS.utils.esc(locationText) + '</div>'
      + '<a href="' + mapUrl + '" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-solid fa-map-location-dot"></i> Mở Google Maps</a>'
      + '</div>';
    SS.ui.sheet({title: 'Vị trí', html: html});
  },

  // Filter posts by location
  filterByLocation: function(province, district) {
    var params = [];
    if (province) params.push('province=' + encodeURIComponent(province));
    if (district) params.push('district=' + encodeURIComponent(district));
    if (params.length) {
      window.location.href = '/?filter=location&' + params.join('&');
    }
  }
};
