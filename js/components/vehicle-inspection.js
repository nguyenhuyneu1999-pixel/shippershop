window.SS = window.SS || {};
SS.VehicleInspection = {
  show: function() {
    SS.api.get('/vehicle-inspection.php').then(function(d) {
      var data = d.data || {};
      var checks = data.checks || [];
      var latest = data.latest;
      var html = '<div class="text-center mb-3">' + (data.needs_inspection ? '<div class="font-bold" style="color:var(--danger)">⚠️ Can kiem tra xe!</div><div class="text-xs text-muted">' + (data.days_since || 0) + ' ngay truoc</div>' : '<div class="font-bold" style="color:var(--success)">✅ Xe da kiem tra</div><div class="text-xs text-muted">' + (data.days_since || 0) + ' ngay truoc</div>') + '</div>';
      if (latest) html += '<div class="card mb-3" style="padding:10px;text-align:center"><div class="font-bold text-lg" style="color:' + (latest.score >= 85 ? 'var(--success)' : 'var(--warning)') + '">' + latest.grade + ' (' + latest.score + '/100)</div><div class="text-xs text-muted">✅' + latest.good + ' ⚠️' + latest.fair + ' ❌' + latest.bad + '</div></div>';
      html += '<button class="btn btn-primary btn-sm mb-3" onclick="SS.VehicleInspection.inspect()"><i class="fa-solid fa-clipboard-check"></i> Kiem tra ngay</button>';
      var inspections = data.inspections || [];
      for (var i = 0; i < Math.min(inspections.length, 5); i++) {
        var insp = inspections[i];
        html += '<div class="flex justify-between text-xs p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(insp.date) + '</span><span class="font-bold" style="color:' + (insp.score >= 85 ? 'var(--success)' : 'var(--warning)') + '">' + insp.grade + ' ' + insp.score + '</span></div>';
      }
      SS.ui.sheet({title: '🏍️ Kiem tra xe', html: html});
    });
  },
  inspect: function() { SS.ui.toast('Mo form kiem tra 12 hang muc', 'info'); }
};
