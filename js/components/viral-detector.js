window.SS = window.SS || {};
SS.ViralDetector = {
  show: function(hours) {
    hours = hours || 24;
    SS.api.get('/viral-detector.php?hours=' + hours).then(function(d) {
      var data = d.data || {};
      var posts = data.posts || [];
      var html = '<div class="flex gap-2 mb-3">';
      [6, 24, 72].forEach(function(h) { html += '<div class="chip ' + (h === hours ? 'chip-active' : '') + '" onclick="SS.ViralDetector.show(' + h + ')" style="cursor:pointer">' + h + 'h</div>'; });
      html += '</div><div class="text-xs text-muted mb-3">🔥 ' + (data.viral_count || 0) + ' bai viral / ' + (data.total_analyzed || 0) + ' bai</div>';
      for (var i = 0; i < Math.min(posts.length, 10); i++) {
        var p = posts[i];
        var color = p.is_viral ? 'var(--danger)' : 'var(--text-muted)';
        html += '<div class="card mb-2" style="padding:10px' + (p.is_viral ? ';border-left:3px solid var(--danger)' : '') + '"><div class="flex gap-2 items-start">'
          + '<div class="flex-1"><div class="text-sm">' + SS.utils.esc(p.content) + '</div>'
          + '<div class="text-xs text-muted mt-1">' + SS.utils.esc(p.author) + ' · ' + p.age_hours + 'h · ❤️' + p.likes + ' 💬' + p.comments + ' 🔄' + p.shares + '</div></div>'
          + '<div class="text-right"><div class="font-bold" style="color:' + color + '">' + p.viral_score + '</div><div class="text-xs text-muted">v=' + p.velocity + '/h</div></div></div></div>';
      }
      SS.ui.sheet({title: '🔥 Viral Detector', html: html});
    });
  }
};
