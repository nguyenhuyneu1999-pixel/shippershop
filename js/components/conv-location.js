/**
 * ShipperShop Component — Conversation Location Sharing
 */
window.SS = window.SS || {};

SS.ConvLocation = {
  show: function(conversationId) {
    SS.api.get('/conv-location.php?conversation_id=' + conversationId).then(function(d) {
      var locations = (d.data || {}).locations || [];
      if (!locations.length) {
        SS.ui.sheet({title: 'Vi tri', html: '<div class="empty-state p-3"><div class="empty-icon">📍</div><div class="empty-text">Chua co vi tri duoc chia se</div><button class="btn btn-primary btn-sm mt-2" onclick="SS.ConvLocation.share(' + conversationId + ')">Chia se vi tri</button></div>'});
        return;
      }
      var html = '';
      for (var i = 0; i < locations.length; i++) {
        var l = locations[i];
        html += '<div class="card mb-2" style="padding:10px"><div class="flex items-center gap-2">'
          + '<img src="' + (l.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><div class="text-sm font-medium">' + SS.utils.esc(l.fullname || '') + '</div>'
          + '<div class="text-xs text-muted">📍 ' + l.latitude.toFixed(4) + ', ' + l.longitude.toFixed(4) + (l.label ? ' · ' + SS.utils.esc(l.label) : '') + '</div>'
          + '<div class="text-xs text-muted">Het han: ' + SS.utils.ago(l.expires_at) + '</div></div></div></div>';
      }
      html += '<button class="btn btn-primary btn-sm mt-2" onclick="SS.ConvLocation.share(' + conversationId + ')"><i class="fa-solid fa-location-dot"></i> Chia se vi tri</button>';
      SS.ui.sheet({title: '📍 Vi tri (' + locations.length + ')', html: html});
    });
  },

  share: function(conversationId) {
    if (!navigator.geolocation) { SS.ui.toast('Khong ho tro GPS', 'error'); return; }
    SS.ui.toast('Dang lay vi tri...', 'info', 2000);
    navigator.geolocation.getCurrentPosition(function(pos) {
      SS.api.post('/conv-location.php', {
        conversation_id: conversationId,
        latitude: pos.coords.latitude,
        longitude: pos.coords.longitude,
        duration_minutes: 60
      }).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); });
    }, function() { SS.ui.toast('Khong lay duoc vi tri', 'error'); });
  }
};
