window.SS = window.SS || {};
SS.ConvLocationLive = {
  show: function(conversationId) {
    SS.api.get('/conv-location-live.php?conversation_id=' + conversationId).then(function(d) {
      var locs = (d.data || {}).locations || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvLocationLive.share(' + conversationId + ')"><i class="fa-solid fa-location-dot"></i> Chia se vi tri</button>'
        + '<button class="btn btn-ghost btn-sm mb-3" onclick="SS.ConvLocationLive.stop(' + conversationId + ')">⏹ Dung chia se</button>';
      if (!locs.length) html += '<div class="empty-state p-3"><div class="empty-icon">📍</div><div class="empty-text">Chua ai chia se vi tri</div></div>';
      for (var i = 0; i < locs.length; i++) {
        var l = locs[i];
        var fresh = (l.age_seconds || 999) < 60;
        html += '<div class="card mb-2" style="padding:10px"><div class="flex items-center gap-2">'
          + '<img src="' + (l.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><div class="text-sm font-bold">' + SS.utils.esc(l.fullname || '') + ' ' + (fresh ? '🟢' : '🟡') + '</div>'
          + '<div class="text-xs text-muted">📍 ' + l.lat.toFixed(4) + ', ' + l.lng.toFixed(4) + (l.speed > 0 ? ' · 🏍️ ' + Math.round(l.speed) + ' km/h' : '') + '</div>'
          + '<div class="text-xs text-muted">' + (fresh ? 'Vua cap nhat' : l.age_seconds + 's truoc') + '</div></div></div></div>';
      }
      SS.ui.sheet({title: '📍 Vi tri truc tiep (' + locs.length + ')', html: html});
    });
  },
  share: function(convId) {
    if (!navigator.geolocation) { SS.ui.toast('Khong ho tro dinh vi', 'error'); return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
      SS.api.post('/conv-location-live.php', {conversation_id: convId, lat: pos.coords.latitude, lng: pos.coords.longitude, speed: pos.coords.speed || 0, heading: pos.coords.heading || 0}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvLocationLive.show(convId); });
    }, function() { SS.ui.toast('Khong truy cap vi tri', 'warning'); });
  },
  stop: function(convId) { SS.api.post('/conv-location-live.php?action=stop', {conversation_id: convId}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvLocationLive.show(convId); }); }
};
