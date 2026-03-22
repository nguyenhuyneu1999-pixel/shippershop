window.SS = window.SS || {};
SS.ReportGenerator = {
  show: function(period) {
    period = period || 'week';
    SS.api.get('/report-generator.php?period=' + period).then(function(d) {
      var data = d.data || {};
      var s = data.summary || {};
      var html = '<div class="flex gap-2 mb-3">';
      ['week', 'month'].forEach(function(p) { html += '<div class="chip ' + (p === period ? 'chip-active' : '') + '" onclick="SS.ReportGenerator.show(\'' + p + '\')" style="cursor:pointer">' + (p === 'week' ? 'Tuan' : 'Thang') + '</div>'; });
      html += '</div>';
      html += '<div class="card mb-3" style="padding:12px;text-align:center;background:linear-gradient(135deg,var(--primary)08,transparent)"><div class="font-bold">' + SS.utils.esc(data.user || '') + '</div><div class="text-xs text-muted">' + SS.utils.esc(data.company || '') + ' · ' + SS.utils.esc(data.start || '') + ' → ' + SS.utils.esc(data.end || '') + '</div></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:12px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:8px"><div class="font-bold text-lg">' + (s.total_posts || 0) + '</div><div class="text-muted">Don</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold text-lg" style="color:var(--danger)">❤️' + (s.total_likes || 0) + '</div><div class="text-muted">Likes</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold text-lg">' + (s.engagement_rate || 0) + '</div><div class="text-muted">Eng/don</div></div></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:12px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (s.active_days || 0) + '</div><div class="text-muted">Ngay</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (s.avg_per_day || 0) + '</div><div class="text-muted">TB/ngay</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--success)">+' + (s.new_followers || 0) + '</div><div class="text-muted">Followers</div></div></div>';
      var tp = data.top_post;
      if (tp) html += '<div class="card mb-2" style="padding:8px"><div class="text-xs font-bold">🏆 Top post</div><div class="text-xs">' + SS.utils.esc(tp.preview || '') + '</div><div class="text-xs text-muted">❤️' + tp.likes_count + ' 💬' + tp.comments_count + '</div></div>';
      SS.ui.sheet({title: '📊 Bao cao ' + (period === 'week' ? 'tuan' : 'thang'), html: html});
    });
  }
};
