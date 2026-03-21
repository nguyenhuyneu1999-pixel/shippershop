/**
 * ShipperShop Component — Reputation Display
 * Shows user reputation level, score badge, breakdown
 * Uses: SS.api, SS.ui, SS.Charts
 */
window.SS = window.SS || {};

SS.Reputation = {

  // Render inline level badge
  badge: function(tier, levelName, icon) {
    if (!tier || tier <= 1) return '';
    var colors = {2:'#22c55e',3:'#3b82f6',4:'#f59e0b',5:'#7C3AED',6:'#ec4899'};
    var c = colors[tier] || '#666';
    return ' <span style="display:inline-flex;align-items:center;gap:2px;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:600;background:' + c + '15;color:' + c + '">' + (icon || '') + ' ' + SS.utils.esc(levelName || '') + '</span>';
  },

  // Show full reputation card
  showCard: function(userId) {
    SS.api.get('/reputation.php?user_id=' + userId).then(function(d) {
      var data = d.data || {};
      if (!data.score && data.score !== 0) return;

      var progressColor = data.tier >= 5 ? '#7C3AED' : (data.tier >= 3 ? '#3b82f6' : '#22c55e');

      var html = '<div style="text-align:center;padding:8px 0">'
        + '<div style="font-size:48px;margin-bottom:8px">' + (data.icon || '🌱') + '</div>'
        + '<div style="font-size:20px;font-weight:800;color:var(--text)">' + SS.utils.esc(data.level || '') + '</div>'
        + '<div style="font-size:32px;font-weight:800;color:' + progressColor + ';margin:8px 0">' + SS.utils.fN(data.score) + '</div>'
        + '<div class="text-sm text-muted">Điểm uy tín</div>';

      // Progress to next level
      if (data.next_level) {
        html += '<div style="margin:16px 0;background:var(--border-light);border-radius:8px;height:8px;overflow:hidden">'
          + '<div style="width:' + data.progress + '%;height:100%;background:' + progressColor + ';border-radius:8px;transition:width .5s"></div></div>'
          + '<div class="text-xs text-muted">Còn ' + (data.next_level.min - data.score) + ' điểm để lên ' + data.next_level.icon + ' ' + data.next_level.name + '</div>';
      }

      html += '</div>';

      // Breakdown
      if (data.factors && data.factors.length) {
        html += '<div class="divider"></div><div class="text-sm font-bold mb-2">Chi tiết</div>';
        for (var i = 0; i < data.factors.length; i++) {
          var f = data.factors[i];
          var pct = f.max > 0 ? Math.round(f.value / f.max * 100) : 0;
          html += '<div style="margin-bottom:8px">'
            + '<div class="flex justify-between text-xs mb-1"><span>' + SS.utils.esc(f.name) + '</span><span class="font-bold">' + f.value + '/' + f.max + '</span></div>'
            + '<div style="background:var(--border-light);border-radius:4px;height:4px;overflow:hidden"><div style="width:' + pct + '%;height:100%;background:' + progressColor + ';border-radius:4px"></div></div>'
            + '</div>';
        }
      }

      SS.ui.sheet({title: 'Uy tín', html: html});
    });
  },

  // Render mini badge for post cards
  renderMini: function(userId, containerId) {
    SS.api.get('/reputation.php?user_id=' + userId).then(function(d) {
      var data = d.data || {};
      var el = document.getElementById(containerId);
      if (el && data.tier > 1) {
        el.innerHTML = SS.Reputation.badge(data.tier, data.level, data.icon);
      }
    });
  }
};
