/**
 * ShipperShop Component — Achievements
 * Milestone tracker with categories, progress bars, XP rewards
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.Achievements = {

  show: function(userId) {
    SS.api.get('/achievements.php?user_id=' + (userId || '')).then(function(d) {
      var data = d.data || {};
      var earned = data.earned || [];
      var upcoming = data.upcoming || [];

      // Header
      var html = '<div class="text-center mb-4">'
        + '<div style="font-size:40px;font-weight:800;color:var(--primary)">' + (data.total_earned || 0) + '<span style="font-size:18px;color:var(--text-muted)">/' + (data.total_available || 0) + '</span></div>'
        + '<div class="text-sm text-muted">Thành tựu đã đạt</div>'
        + '<div style="margin-top:8px;font-size:14px;font-weight:700;color:var(--warning)">⭐ ' + SS.utils.fN(data.total_xp || 0) + ' XP</div></div>';

      // Earned
      if (earned.length) {
        html += '<div class="text-sm font-bold mb-2">Đã đạt được</div>'
          + '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:16px">';
        for (var i = 0; i < earned.length; i++) {
          var e = earned[i];
          html += '<div style="text-align:center;padding:8px 4px;background:var(--bg);border-radius:10px;cursor:pointer" title="' + SS.utils.esc(e.desc) + '">'
            + '<div style="font-size:24px">' + e.icon + '</div>'
            + '<div class="text-xs font-medium" style="margin-top:2px;line-height:1.2">' + SS.utils.esc(e.name) + '</div></div>';
        }
        html += '</div>';
      }

      // Upcoming
      if (upcoming.length) {
        html += '<div class="text-sm font-bold mb-2">Sắp đạt được</div>';
        for (var j = 0; j < upcoming.length; j++) {
          var u = upcoming[j];
          var pct = u.progress || 0;
          var color = pct >= 80 ? 'var(--success)' : (pct >= 50 ? 'var(--warning)' : 'var(--primary)');
          html += '<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light)">'
            + '<div style="font-size:24px;opacity:0.5">' + u.icon + '</div>'
            + '<div style="flex:1"><div class="flex justify-between text-sm"><span class="font-medium">' + SS.utils.esc(u.name) + '</span><span class="text-xs text-muted">+' + u.xp + ' XP</span></div>'
            + '<div class="text-xs text-muted mb-1">' + SS.utils.esc(u.desc) + '</div>'
            + '<div style="display:flex;align-items:center;gap:6px">'
            + '<div style="flex:1;background:var(--border-light);border-radius:4px;height:5px;overflow:hidden"><div style="width:' + pct + '%;height:100%;background:' + color + ';border-radius:4px"></div></div>'
            + '<span class="text-xs font-bold">' + u.current + '/' + u.target + '</span></div></div></div>';
        }
      }

      SS.ui.sheet({title: 'Thành tựu', html: html});
    });
  },

  // Check for new achievements (call after actions)
  checkNew: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    SS.api.get('/achievements.php').then(function(d) {
      var earned = (d.data || {}).earned || [];
      var lastCount = parseInt(localStorage.getItem('ss_ach_count') || '0');
      if (earned.length > lastCount && lastCount > 0) {
        var newest = earned[earned.length - 1];
        SS.ui.toast(newest.icon + ' Thành tựu mới: ' + newest.name + ' (+' + newest.xp + ' XP)', 'success', 5000);
      }
      localStorage.setItem('ss_ach_count', String(earned.length));
    }).catch(function() {});
  }
};
