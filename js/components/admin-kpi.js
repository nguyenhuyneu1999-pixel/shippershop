window.SS = window.SS || {};
SS.AdminKPI = {
  show: function() {
    SS.api.get('/admin-kpi.php').then(function(d) {
      var kpis = (d.data || {}).kpis || [];
      var html = '';
      for (var i = 0; i < kpis.length; i++) {
        var k = kpis[i];
        html += '<div class="card mb-2" style="padding:12px;border-left:4px solid ' + (k.good ? 'var(--success)' : 'var(--warning)') + '">'
          + '<div class="flex justify-between items-center"><div><div class="text-sm">' + k.icon + ' ' + SS.utils.esc(k.name) + '</div><div class="text-xs text-muted">Target: ' + k.target + '</div></div>'
          + '<div class="font-bold text-lg" style="color:' + (k.good ? 'var(--success)' : 'var(--warning)') + '">' + k.value + '</div></div></div>';
      }
      SS.ui.sheet({title: 'KPI Dashboard', html: html});
    });
  }
};
