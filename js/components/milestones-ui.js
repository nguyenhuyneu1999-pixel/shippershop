/**
 * ShipperShop Component — Milestones UI
 * Timeline of unlocked milestones + progress to next goals
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.Milestones = {

  show: function(userId) {
    SS.api.get('/milestones.php?user_id=' + (userId || '')).then(function(d) {
      var data = d.data || {};
      var unlocked = data.unlocked || [];
      var next = data.next || [];

      var html = '<div class="text-center mb-3">'
        + '<div style="font-size:36px;font-weight:800;color:var(--primary)">' + unlocked.length + '/' + (data.total_milestones || 20) + '</div>'
        + '<div class="text-sm text-muted">Cột mốc đã đạt · ' + SS.utils.fN(data.total_xp || 0) + ' XP</div></div>';

      // Next goals
      if (next.length) {
        html += '<div class="text-sm font-bold mb-2">Sắp đạt được</div>';
        for (var j = 0; j < next.length; j++) {
          var n = next[j];
          html += '<div class="flex items-center gap-3 mb-3">'
            + '<div style="font-size:24px;opacity:0.4">' + n.icon + '</div>'
            + '<div class="flex-1">'
            + '<div class="flex justify-between"><span class="text-sm font-medium">' + SS.utils.esc(n.name) + '</span><span class="text-xs text-muted">+' + n.xp + 'XP</span></div>'
            + '<div class="text-xs text-muted">' + SS.utils.esc(n.desc) + '</div>'
            + '<div style="margin-top:3px;background:var(--border-light);border-radius:4px;height:5px;overflow:hidden">'
            + '<div style="width:' + n.progress + '%;height:100%;background:var(--primary);border-radius:4px;transition:width .5s"></div></div>'
            + '<div class="text-xs text-muted mt-1">' + SS.utils.fN(n.current) + '/' + SS.utils.fN(n.target) + '</div>'
            + '</div></div>';
        }
        html += '<div class="divider"></div>';
      }

      // Unlocked milestones
      if (unlocked.length) {
        html += '<div class="text-sm font-bold mb-2">Đã đạt được</div>'
          + '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px">';
        for (var i = 0; i < unlocked.length; i++) {
          var u = unlocked[i];
          html += '<div style="text-align:center;padding:8px 4px" title="' + SS.utils.esc(u.name + ': ' + u.desc) + '">'
            + '<div style="font-size:24px">' + u.icon + '</div>'
            + '<div class="text-xs font-medium" style="line-height:1.2;margin-top:2px">' + SS.utils.esc(u.name) + '</div></div>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Cột mốc', html: html});
    });
  },

  // Mini display for sidebar/profile
  renderMini: function(userId, containerId) {
    SS.api.get('/milestones.php?user_id=' + userId).then(function(d) {
      var el = document.getElementById(containerId);
      if (!el) return;
      var data = d.data || {};
      var next = data.next || [];
      if (!next.length) { el.innerHTML = ''; return; }
      var n = next[0];
      el.innerHTML = '<div class="card" style="padding:10px;cursor:pointer" onclick="SS.Milestones.show(' + userId + ')">'
        + '<div class="flex items-center gap-2"><span style="font-size:18px">' + n.icon + '</span>'
        + '<div class="flex-1"><div class="text-xs font-bold">' + SS.utils.esc(n.name) + ' · ' + n.progress + '%</div>'
        + '<div style="background:var(--border-light);border-radius:3px;height:3px;overflow:hidden;margin-top:2px">'
        + '<div style="width:' + n.progress + '%;height:100%;background:var(--primary);border-radius:3px"></div></div></div></div></div>';
    });
  }
};
