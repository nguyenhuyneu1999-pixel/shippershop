/**
 * ShipperShop Component — Churn Prediction (Admin)
 */
window.SS = window.SS || {};

SS.ChurnPredict = {
  show: function() {
    SS.api.get('/churn-predict.php').then(function(d) {
      var data = d.data || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--warning)">' + (data.at_risk_count || 0) + '</div><div class="text-xs text-muted">Co nguy co</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--danger)">' + (data.churned_count || 0) + '</div><div class="text-xs text-muted">Da roi bo</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.declining_count || 0) + '</div><div class="text-xs text-muted">Giam hoat dong</div></div></div>';
      var atRisk = data.at_risk || [];
      if (atRisk.length) {
        html += '<div class="text-sm font-bold mb-2">Co nguy co roi bo</div>';
        for (var i = 0; i < Math.min(atRisk.length, 10); i++) {
          var u = atRisk[i];
          html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)"><img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy"><div class="flex-1"><div class="text-sm">' + SS.utils.esc(u.fullname) + '</div><div class="text-xs text-muted">' + u.total_posts + ' bai · ' + SS.utils.ago(u.last_post) + '</div></div></div>';
        }
      }
      SS.ui.sheet({title: 'Du doan roi bo', html: html});
    });
  }
};
