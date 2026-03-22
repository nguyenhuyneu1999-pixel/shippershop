/**
 * ShipperShop Component — Platform Health Score (Admin)
 */
window.SS = window.SS || {};

SS.HealthScore = {
  show: function() {
    SS.api.get('/health-score.php').then(function(d) {
      var data = d.data || {};
      var scores = data.scores || {};
      var gradeColors = {A: 'var(--success)', B: 'var(--primary)', C: 'var(--warning)', D: 'var(--danger)'};
      var color = gradeColors[data.grade] || '#999';

      var html = '<div class="text-center mb-3"><div style="width:90px;height:90px;border-radius:50%;border:5px solid ' + color + ';display:inline-flex;align-items:center;justify-content:center;flex-direction:column"><div style="font-size:28px;font-weight:800;color:' + color + '">' + (data.grade || 'D') + '</div><div style="font-size:12px">' + (data.overall || 0) + '/100</div></div></div>';

      // Score bars
      var items = [
        {key: 'growth', name: 'Tang truong', icon: '📈'},
        {key: 'engagement', name: 'Tuong tac', icon: '🔥'},
        {key: 'content', name: 'Noi dung', icon: '📝'},
        {key: 'quality', name: 'Chat luong', icon: '⭐'},
        {key: 'retention', name: 'Giu chan', icon: '🔄'},
      ];
      for (var i = 0; i < items.length; i++) {
        var val = scores[items[i].key] || 0;
        var barColor = val >= 70 ? 'var(--success)' : (val >= 40 ? 'var(--primary)' : 'var(--warning)');
        html += '<div class="mb-2"><div class="flex justify-between text-sm mb-1"><span>' + items[i].icon + ' ' + items[i].name + '</span><span class="font-bold">' + val + '</span></div>'
          + '<div style="height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + val + '%;height:100%;background:' + barColor + ';border-radius:4px;transition:width 1s"></div></div></div>';
      }

      // Key metrics
      var m = data.metrics || {};
      html += '<div class="flex gap-2 mt-3 text-center" style="font-size:11px">'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold">' + (m.dau || 0) + '</div><div class="text-muted">DAU</div></div>'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold">' + (m.posts_today || 0) + '</div><div class="text-muted">Posts</div></div>'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold">' + (m.growth_rate || 0) + '%</div><div class="text-muted">Growth</div></div></div>';

      SS.ui.sheet({title: 'Suc khoe nen tang', html: html});
    });
  }
};
