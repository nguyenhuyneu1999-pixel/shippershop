window.SS = window.SS || {};
SS.CronMonitor = {
  show: function() {
    SS.api.get('/cron-monitor.php').then(function(d) {
      var data = d.data || {};
      var jobs = data.jobs || [];
      var html = '<div class="text-center mb-3"><div class="font-bold text-lg" style="color:' + ((data.health_pct || 0) >= 80 ? 'var(--success)' : 'var(--warning)') + '">' + (data.health_pct || 0) + '%</div><div class="text-xs text-muted">' + (data.healthy || 0) + '/' + (data.total || 0) + ' jobs healthy</div></div>';
      for (var i = 0; i < jobs.length; i++) {
        var j = jobs[i];
        var color = j.healthy ? 'var(--success)' : (j.last_status === 'never' ? 'var(--text-muted)' : 'var(--danger)');
        html += '<div class="card mb-2" style="padding:10px;border-left:3px solid ' + color + '"><div class="flex justify-between"><span class="font-bold text-sm">' + SS.utils.esc(j.name) + '</span><span class="text-xs" style="color:' + color + '">' + (j.healthy ? '✅' : (j.last_status === 'never' ? '⏳' : '❌')) + '</span></div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(j.description) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(j.schedule) + (j.last_run ? ' · Last: ' + SS.utils.ago(j.last_run) + ' (' + j.last_duration_ms + 'ms)' : ' · Never run') + '</div></div>';
      }
      SS.ui.sheet({title: '⚙️ Cron Monitor', html: html});
    });
  }
};
