window.SS = window.SS || {};
SS.QualityGate = {
  check: function(text, hasImage) {
    SS.api.get('/quality-gate.php?text=' + encodeURIComponent((text || '').substring(0, 500)) + '&has_image=' + (hasImage ? '1' : '')).then(function(d) {
      var data = d.data || {};
      var checks = data.checks || [];
      var gradeColors = {A: 'var(--success)', B: 'var(--primary)', C: 'var(--warning)', D: 'var(--danger)'};
      var statusIcons = {pass: '✅', warn: '⚠️', fail: '❌'};
      var html = '<div class="text-center mb-3"><div style="width:70px;height:70px;border-radius:50%;border:5px solid ' + (gradeColors[data.grade] || '') + ';display:inline-flex;align-items:center;justify-content:center"><div style="font-size:22px;font-weight:800;color:' + (gradeColors[data.grade] || '') + '">' + (data.grade || '?') + '</div></div>'
        + '<div class="text-sm mt-1">' + (data.pass ? '✅ San sang dang' : '❌ Can cai thien') + '</div><div class="text-xs text-muted">' + (data.score || 0) + '/100</div></div>';
      for (var i = 0; i < checks.length; i++) {
        var c = checks[i];
        html += '<div class="flex justify-between items-center p-2" style="border-bottom:1px solid var(--border-light)"><div><span>' + (statusIcons[c.status] || '') + ' ' + SS.utils.esc(c.name) + '</span><div class="text-xs text-muted">' + SS.utils.esc(c.detail) + '</div></div>'
          + (c.impact !== 0 ? '<span class="text-xs font-bold" style="color:var(--danger)">' + c.impact + '</span>' : '') + '</div>';
      }
      SS.ui.sheet({title: '🔍 Quality Gate', html: html});
    });
  }
};
