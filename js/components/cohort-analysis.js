/**
 * ShipperShop Component — Cohort Analysis (Admin)
 */
window.SS = window.SS || {};

SS.CohortAnalysis = {
  show: function() {
    SS.api.get('/cohort-analysis.php').then(function(d) {
      var data = d.data || {};
      var cohorts = data.cohorts || [];

      var html = '<div class="text-sm text-muted mb-3">' + (data.months_analyzed || 0) + ' thang phan tich</div>';

      // Cohort table
      html += '<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:11px">';
      html += '<tr><th style="padding:6px;text-align:left">Thang</th><th style="padding:6px">Dang ky</th><th style="padding:6px">M+0</th><th style="padding:6px">M+1</th><th style="padding:6px">M+2</th><th style="padding:6px">M+3</th></tr>';

      for (var i = 0; i < cohorts.length; i++) {
        var c = cohorts[i];
        var ret = c.retention || [];
        html += '<tr><td style="padding:4px;font-weight:600">' + SS.utils.esc(c.cohort) + '</td><td style="padding:4px;text-align:center">' + c.signups + '</td>';
        for (var r = 0; r < 4; r++) {
          var rd = ret[r] || {rate: 0};
          var bg = rd.rate >= 50 ? 'rgba(34,197,94,0.2)' : (rd.rate >= 20 ? 'rgba(124,58,237,0.15)' : (rd.rate > 0 ? 'rgba(245,158,11,0.15)' : ''));
          html += '<td style="padding:4px;text-align:center;background:' + bg + '">' + (rd.rate || 0) + '%</td>';
        }
        html += '</tr>';
      }
      html += '</table></div>';

      SS.ui.sheet({title: 'Phan tich Cohort', html: html});
    });
  }
};
