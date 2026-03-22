/**
 * ShipperShop Component — Real-time Monitor (Admin)
 */
window.SS = window.SS || {};

SS.RealtimeMonitor = {
  show: function(minutes) {
    minutes = minutes || 5;
    SS.api.get('/realtime-monitor.php?minutes=' + minutes).then(function(d) {
      var data = d.data || {};
      var html = '<div class="flex gap-2 mb-3">';
      [5, 15, 30, 60].forEach(function(m) {
        html += '<div class="chip ' + (m === minutes ? 'chip-active' : '') + '" onclick="SS.RealtimeMonitor.show(' + m + ')" style="cursor:pointer">' + m + 'p</div>';
      });
      html += '</div>';

      // Live counters
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--success)">' + (data.online || 0) + '</div><div class="text-xs text-muted">Online</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.active_now || 0) + '</div><div class="text-xs text-muted">Active 1h</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.logins || 0) + '</div><div class="text-xs text-muted">Logins</div></div></div>';

      html += '<div class="flex gap-2 mb-3"><div class="chip">📝 ' + (data.posts || []).length + ' bai</div><div class="chip">💬 ' + (data.comments || []).length + ' cmt</div><div class="chip">❤️ ' + (data.likes_count || 0) + ' likes</div></div>';

      // Recent posts
      var posts = data.posts || [];
      if (posts.length) {
        html += '<div class="text-sm font-bold mb-2">Bai moi (' + minutes + ' phut)</div>';
        for (var i = 0; i < posts.length; i++) {
          var p = posts[i];
          html += '<div class="flex gap-2 p-2" style="border-bottom:1px solid var(--border-light)"><img src="' + (p.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
            + '<div class="flex-1"><div class="text-xs font-medium">' + SS.utils.esc(p.fullname) + '</div><div class="text-xs text-muted">' + SS.utils.esc((p.content || '').substring(0, 60)) + '</div></div>'
            + '<div class="text-xs text-muted">' + SS.utils.ago(p.created_at) + '</div></div>';
        }
      }
      SS.ui.sheet({title: '🔴 Real-time (' + minutes + 'p)', html: html});
    });
  }
};
