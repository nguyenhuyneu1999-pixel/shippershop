/**
 * ShipperShop Component — User Funnel (Admin)
 */
window.SS = window.SS || {};

SS.UserFunnel = {
  show: function() {
    SS.api.get('/user-funnel.php').then(function(d) {
      var data = d.data || {};
      var stages = data.stages || [];

      var html = '';
      if (stages.length) {
        var maxC = Math.max.apply(null, stages.map(function(s) { return s.count || 0; })) || 1;
        for (var i = 0; i < stages.length; i++) {
          var s = stages[i];
          var w = Math.max(20, Math.round(s.count / maxC * 100));
          html += '<div class="mb-3" style="text-align:center"><div style="width:' + w + '%;margin:0 auto;padding:10px;background:linear-gradient(135deg,var(--primary)' + (10 + i * 5) + ',var(--primary)' + (30 + i * 10) + ');border-radius:8px;color:#fff">'
            + '<div class="font-bold text-lg">' + SS.utils.fN(s.count) + '</div>'
            + '<div class="text-xs" style="opacity:.9">' + s.icon + ' ' + SS.utils.esc(s.stage) + (s.rate !== undefined ? ' (' + s.rate + '%)' : '') + '</div></div></div>';
        }
      }

      html += '<div class="card mt-3" style="padding:10px;text-align:center"><div class="text-xs text-muted">Ti le chuyen doi tong</div><div class="font-bold text-lg" style="color:var(--primary)">' + (data.overall_conversion || 0) + '%</div><div class="text-xs text-muted">Truy cap → Subscriber</div></div>';
      SS.ui.sheet({title: '📊 Pheu nguoi dung', html: html});
    });
  }
};
