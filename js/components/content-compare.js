window.SS = window.SS || {};
SS.ContentCompare = {
  showPosts: function(id1, id2) {
    SS.api.get('/content-compare.php?type=posts&id1=' + id1 + '&id2=' + id2).then(function(d) {
      var data = d.data || {};
      var comp = data.comparison || [];
      var wColor = data.winner === 'A' ? 'var(--success)' : (data.winner === 'B' ? 'var(--primary)' : 'var(--text-muted)');
      var html = '<div class="text-center mb-3"><span class="font-bold" style="color:' + wColor + '">Winner: ' + data.winner + '</span></div>';
      html += '<div class="text-xs text-center text-muted mb-2">A: #' + ((data.a || {}).id || '') + ' (' + SS.utils.esc((data.a || {}).author || '') + ') vs B: #' + ((data.b || {}).id || '') + ' (' + SS.utils.esc((data.b || {}).author || '') + ')</div>';
      for (var i = 0; i < comp.length; i++) {
        var c = comp[i]; var max = Math.max(c.a, c.b, 1);
        html += '<div class="mb-2"><div class="text-xs text-center text-muted">' + SS.utils.esc(c.metric) + '</div>'
          + '<div class="flex gap-2 items-center"><div style="flex:1;text-align:right"><span class="text-xs font-bold">' + c.a + '</span><div style="height:8px;background:var(--border-light);border-radius:4px;overflow:hidden"><div style="width:' + Math.round(c.a / max * 100) + '%;height:100%;background:var(--success);float:right;border-radius:4px"></div></div></div>'
          + '<div style="flex:1"><div style="height:8px;background:var(--border-light);border-radius:4px;overflow:hidden"><div style="width:' + Math.round(c.b / max * 100) + '%;height:100%;background:var(--primary);border-radius:4px"></div></div><span class="text-xs font-bold">' + c.b + '</span></div></div></div>';
      }
      SS.ui.sheet({title: '🔄 So sanh bai viet', html: html});
    });
  },
  showUsers: function(id1, id2) {
    SS.api.get('/content-compare.php?type=users&id1=' + id1 + '&id2=' + id2).then(function(d) {
      var data = d.data || {};
      var comp = data.comparison || [];
      var html = '<div class="text-center mb-3"><span class="font-bold">' + SS.utils.esc((data.a || {}).name || '') + ' (' + ((data.a || {}).score || 0) + ') vs ' + SS.utils.esc((data.b || {}).name || '') + ' (' + ((data.b || {}).score || 0) + ')</span></div>';
      for (var i = 0; i < comp.length; i++) {
        var c = comp[i];
        html += '<div class="flex justify-between text-sm p-2" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(c.metric) + '</span><span class="font-bold">' + c.a + ' vs ' + c.b + '</span></div>';
      }
      SS.ui.sheet({title: '👥 So sanh user', html: html});
    });
  }
};
