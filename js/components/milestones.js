/**
 * ShipperShop Component — User Milestones
 * Shows earned milestones and progress toward next ones
 */
window.SS = window.SS || {};

SS.Milestones = {
  show: function(userId) {
    SS.api.get('/milestones.php' + (userId ? '?user_id=' + userId : '')).then(function(d) {
      var data = d.data || {};
      var earned = data.earned || [];
      var progress = data.progress || [];

      var html = '<div class="text-center mb-3"><div style="font-size:36px;font-weight:800;color:var(--primary)">' + (data.total_earned || 0) + '/' + (data.total || 0) + '</div><div class="text-sm text-muted">Thanh tuu dat duoc</div></div>';

      // Earned badges grid
      if (earned.length) {
        html += '<div class="text-sm font-bold mb-2">Da dat duoc</div><div style="display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin-bottom:16px">';
        for (var i = 0; i < earned.length; i++) {
          var e = earned[i];
          html += '<div class="text-center" title="' + SS.utils.esc(e.name) + '"><div style="font-size:24px">' + e.icon + '</div><div class="text-xs" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + SS.utils.esc(e.name) + '</div></div>';
        }
        html += '</div>';
      }

      // Progress list
      var inProgress = progress.filter(function(p) { return !p.earned; });
      if (inProgress.length) {
        html += '<div class="text-sm font-bold mb-2">Dang tien den</div>';
        for (var j = 0; j < inProgress.length; j++) {
          var p = inProgress[j];
          var pct = p.progress || 0;
          var color = pct >= 75 ? 'var(--success)' : (pct >= 50 ? 'var(--primary)' : 'var(--warning)');
          html += '<div class="mb-2"><div class="flex justify-between items-center text-sm"><span>' + p.icon + ' ' + SS.utils.esc(p.name) + '</span><span class="text-xs text-muted">' + (p.current || 0) + '/' + p.threshold + '</span></div>'
            + '<div style="height:6px;background:var(--border-light);border-radius:3px;margin-top:4px"><div style="width:' + pct + '%;height:100%;background:' + color + ';border-radius:3px;transition:width 1s"></div></div></div>';
        }
      }

      SS.ui.sheet({title: 'Thanh tuu', html: html});
    });
  },

  // Compact badge for profile
  renderBadge: function(userId, containerId) {
    SS.api.get('/milestones.php?user_id=' + userId).then(function(d) {
      var el = document.getElementById(containerId);
      if (!el) return;
      var total = (d.data || {}).total_earned || 0;
      if (total <= 0) { el.innerHTML = ''; return; }
      el.innerHTML = '<span class="chip" style="cursor:pointer;font-size:11px" onclick="SS.Milestones.show(' + userId + ')">🏆 ' + total + ' thanh tuu</span>';
    }).catch(function() {});
  }
};
