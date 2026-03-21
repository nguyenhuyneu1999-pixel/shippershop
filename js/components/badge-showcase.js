/**
 * ShipperShop Component — Badge Showcase
 * Grid of earned badges with progress indicators for locked ones
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.BadgeShowcase = {

  show: function(userId) {
    SS.api.get('/badge-showcase.php?user_id=' + (userId || '')).then(function(d) {
      var data = d.data || {};
      var earned = data.earned || [];
      var available = data.available || [];

      var html = '<div class="text-center mb-3">'
        + '<div style="font-size:32px;font-weight:800;color:var(--primary)">' + earned.length + '/' + data.total_available + '</div>'
        + '<div class="text-sm text-muted">Huy hiệu đã mở</div></div>';

      // Earned badges
      if (earned.length) {
        html += '<div class="text-sm font-bold mb-2">Đã đạt được</div>'
          + '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px">';
        for (var i = 0; i < earned.length; i++) {
          var b = earned[i];
          html += '<div class="card" style="text-align:center;padding:12px 8px">'
            + '<div style="font-size:28px;margin-bottom:4px">' + b.icon + '</div>'
            + '<div class="text-xs font-bold" style="line-height:1.2">' + SS.utils.esc(b.name) + '</div></div>';
        }
        html += '</div>';
      }

      // Available badges with progress
      if (available.length) {
        html += '<div class="text-sm font-bold mb-2">Sắp đạt được</div>';
        for (var j = 0; j < Math.min(available.length, 5); j++) {
          var a = available[j];
          var pct = a.progress || 0;
          html += '<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light)">'
            + '<div style="font-size:24px;opacity:0.4">' + a.icon + '</div>'
            + '<div style="flex:1;min-width:0">'
            + '<div class="text-sm font-medium">' + SS.utils.esc(a.name) + '</div>'
            + '<div class="text-xs text-muted">' + SS.utils.esc(a.desc) + '</div>'
            + '<div style="margin-top:4px;background:var(--border-light);border-radius:4px;height:4px;overflow:hidden">'
            + '<div style="width:' + pct + '%;height:100%;background:var(--primary);border-radius:4px"></div></div>'
            + '<div class="text-xs text-muted" style="margin-top:2px">' + (a.current || 0) + '/' + (a.target || '?') + '</div>'
            + '</div></div>';
        }
      }

      SS.ui.sheet({title: 'Huy hiệu', html: html});
    });
  },

  // Render mini badge row (for profile cards)
  renderMini: function(userId, containerId) {
    SS.api.get('/badge-showcase.php?user_id=' + userId).then(function(d) {
      var el = document.getElementById(containerId);
      if (!el) return;
      var earned = (d.data || {}).earned || [];
      if (!earned.length) { el.innerHTML = ''; return; }
      var html = '<div style="display:flex;gap:2px;flex-wrap:wrap">';
      for (var i = 0; i < Math.min(earned.length, 8); i++) {
        html += '<span title="' + SS.utils.esc(earned[i].name) + '" style="font-size:16px;cursor:default">' + earned[i].icon + '</span>';
      }
      if (earned.length > 8) html += '<span class="text-xs text-muted">+' + (earned.length - 8) + '</span>';
      html += '</div>';
      el.innerHTML = html;
    });
  }
};
