window.SS = window.SS || {};
SS.PlatformScorecard = {
  show: function() {
    SS.api.get('/platform-scorecard.php').then(function(d) {
      var data = d.data || {};
      var dims = data.dimensions || [];
      var gradeColors = {A: 'var(--success)', B: 'var(--primary)', C: 'var(--warning)', D: 'var(--danger)'};
      var html = '<div class="text-center mb-3"><div style="width:90px;height:90px;border-radius:50%;border:6px solid ' + (gradeColors[data.grade] || '') + ';display:inline-flex;align-items:center;justify-content:center;flex-direction:column"><div style="font-size:28px;font-weight:800;color:' + (gradeColors[data.grade] || '') + '">' + (data.grade || '?') + '</div><div style="font-size:11px">' + (data.overall || 0) + '/100</div></div></div>';
      for (var i = 0; i < dims.length; i++) {
        var dim = dims[i];
        var color = dim.score >= 70 ? 'var(--success)' : (dim.score >= 40 ? 'var(--warning)' : 'var(--danger)');
        html += '<div class="card mb-2" style="padding:10px"><div class="flex justify-between items-center"><div><div class="text-sm font-bold">' + dim.icon + ' ' + SS.utils.esc(dim.name) + '</div><div class="text-xs text-muted">' + SS.utils.esc(dim.detail) + '</div></div>'
          + '<div class="font-bold" style="color:' + color + '">' + dim.score + '</div></div>'
          + '<div style="height:6px;background:var(--border-light);border-radius:3px;margin-top:6px"><div style="width:' + dim.score + '%;height:100%;background:' + color + ';border-radius:3px"></div></div></div>';
      }
      SS.ui.sheet({title: '🏆 Platform Scorecard', html: html});
    });
  }
};
