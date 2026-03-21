/**
 * ShipperShop Component — Engagement Score
 * Shows user engagement score vs platform average
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.EngagementScore = {

  show: function(userId, days) {
    days = days || 30;
    SS.api.get('/engagement-score.php?user_id=' + (userId || '') + '&days=' + days).then(function(d) {
      var data = d.data || {};
      var score = data.score || 0;
      var rank = data.rank || 'Moi';
      var metrics = data.metrics || {};
      var avg = data.platform_avg || {};

      var color = score >= 80 ? 'var(--success)' : (score >= 50 ? 'var(--primary)' : (score >= 30 ? 'var(--warning)' : 'var(--danger)'));

      // Gauge
      var html = '<div style="text-align:center;padding:8px 0">'
        + '<div style="position:relative;width:120px;height:60px;margin:0 auto;overflow:hidden">'
        + '<div style="position:absolute;inset:0;border:8px solid var(--border-light);border-bottom:none;border-radius:60px 60px 0 0"></div>'
        + '<div style="position:absolute;inset:0;border:8px solid ' + color + ';border-bottom:none;border-radius:60px 60px 0 0;clip-path:polygon(0 100%,' + Math.min(100, score) + '% 100%,' + Math.min(100, score) + '% 0,0 0);transition:clip-path 1s"></div>'
        + '<div style="position:absolute;bottom:0;left:50%;transform:translateX(-50%);font-size:28px;font-weight:800;color:' + color + '">' + score + '</div>'
        + '</div>'
        + '<div class="font-bold" style="color:' + color + ';margin-top:4px">' + SS.utils.esc(rank) + '</div>'
        + '<div class="text-xs text-muted">' + days + ' ngay gan day</div></div>';

      // Metrics comparison
      html += '<div class="divider"></div><div class="text-sm font-bold mb-2">So voi trung binh</div>';
      var items = [
        {label: 'Bai viet', value: metrics.posts || 0, avg: avg.posts || 0, icon: '📝'},
        {label: 'Ghi chu', value: metrics.comments || 0, avg: avg.comments || 0, icon: '💬'},
        {label: 'Luot thich nhan', value: metrics.likes_received || 0, avg: '-', icon: '❤️'},
        {label: 'Luot thich cho', value: metrics.likes_given || 0, avg: '-', icon: '👍'},
      ];

      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        var better = typeof it.avg === 'number' && it.value > it.avg;
        html += '<div class="flex justify-between items-center text-sm" style="padding:6px 0;border-bottom:1px solid var(--border-light)">'
          + '<span>' + it.icon + ' ' + it.label + '</span>'
          + '<span><span class="font-bold' + (better ? '" style="color:var(--success)"' : '"') + '>' + it.value + '</span>';
        if (typeof it.avg === 'number') {
          html += ' <span class="text-xs text-muted">(avg ' + it.avg + ')</span>';
        }
        html += '</span></div>';
      }

      html += '<div class="text-xs text-muted text-center mt-3">' + (avg.active_users || 0) + ' nguoi dung hoat dong trong ' + days + ' ngay</div>';

      SS.ui.sheet({title: 'Diem tuong tac', html: html});
    });
  },

  // Mini badge for profile
  renderBadge: function(userId, containerId) {
    SS.api.get('/engagement-score.php?user_id=' + userId + '&days=30').then(function(d) {
      var el = document.getElementById(containerId);
      if (!el) return;
      var score = (d.data || {}).score || 0;
      var rank = (d.data || {}).rank || '';
      if (score <= 0) { el.innerHTML = ''; return; }
      var color = score >= 80 ? 'var(--success)' : (score >= 50 ? 'var(--primary)' : 'var(--warning)');
      el.innerHTML = '<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600;background:' + color + '15;color:' + color + ';cursor:pointer" onclick="SS.EngagementScore.show(' + userId + ')">' + score + ' ' + SS.utils.esc(rank) + '</span>';
    });
  }
};
