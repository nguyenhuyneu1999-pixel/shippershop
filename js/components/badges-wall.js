/**
 * ShipperShop Component — Badges Wall
 * All badges with progress bars toward earning them
 */
window.SS = window.SS || {};

SS.BadgesWall = {
  show: function(userId) {
    SS.api.get('/badges-wall.php' + (userId ? '?user_id=' + userId : '')).then(function(d) {
      var data = d.data || {};
      var badges = data.badges || [];
      var html = '<div class="text-center mb-3"><div class="font-bold" style="font-size:20px">' + (data.earned_count || 0) + '/' + (data.total_count || 0) + '</div>'
        + '<div class="text-xs text-muted">Hoan thanh ' + (data.completion || 0) + '%</div>'
        + '<div style="height:6px;background:var(--border-light);border-radius:3px;margin-top:4px"><div style="width:' + (data.completion || 0) + '%;height:100%;background:var(--primary);border-radius:3px;transition:width 1s"></div></div></div>';

      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">';
      for (var i = 0; i < badges.length; i++) {
        var b = badges[i];
        var opacity = b.earned ? '1' : '0.5';
        var border = b.earned ? '2px solid var(--primary)' : '1px solid var(--border-light)';
        html += '<div class="card" style="padding:10px;text-align:center;opacity:' + opacity + ';border:' + border + '">'
          + '<div style="font-size:24px">' + b.icon + '</div>'
          + '<div class="text-xs font-bold mt-1">' + SS.utils.esc(b.name) + '</div>';
        if (!b.earned && b.progress !== undefined) {
          html += '<div style="height:3px;background:var(--border-light);border-radius:2px;margin-top:4px"><div style="width:' + b.progress + '%;height:100%;background:var(--primary);border-radius:2px"></div></div>'
            + '<div class="text-xs text-muted">' + (b.current || 0) + '/' + (b.target || '?') + '</div>';
        }
        if (b.earned) html += '<div class="text-xs" style="color:var(--success)">✅</div>';
        html += '</div>';
      }
      html += '</div>';
      SS.ui.sheet({title: 'Tuong huy chuong', html: html});
    });
  }
};
