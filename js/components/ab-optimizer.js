window.SS = window.SS || {};
SS.AbOptimizer = {
  show: function() {
    SS.api.get('/ab-optimizer.php').then(function(d) {
      var data = d.data || {};
      var active = data.active || [];
      var completed = data.completed || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.AbOptimizer.create()"><i class="fa-solid fa-flask"></i> Tao A/B test</button>';
      if (active.length) {
        html += '<div class="text-sm font-bold mb-2">Dang chay</div>';
        for (var i = 0; i < active.length; i++) {
          var t = active[i];
          html += '<div class="card mb-2" style="padding:10px;border-left:3px solid var(--primary)"><div class="font-bold text-sm">' + SS.utils.esc(t.title) + '</div><div class="text-xs text-muted">' + (t.variants || []).length + ' phien ban · ' + SS.utils.ago(t.created_at) + '</div>'
            + '<button class="btn btn-ghost btn-sm mt-1" onclick="SS.AbOptimizer.complete(' + t.id + ')" style="font-size:10px">✅ Ket thuc</button></div>';
        }
      }
      if (completed.length) {
        html += '<div class="text-sm font-bold mb-2 mt-3">Hoan thanh</div>';
        for (var j = 0; j < completed.length; j++) {
          var c = completed[j];
          html += '<div class="card mb-2" style="padding:10px;opacity:0.7"><div class="text-sm">' + SS.utils.esc(c.title) + '</div><div class="text-xs" style="color:var(--success)">Winner: Phien ban ' + ((c.winner || 0) + 1) + '</div></div>';
        }
      }
      if (!active.length && !completed.length) html += '<div class="empty-state p-3"><div class="empty-icon">🧪</div><div class="empty-text">Chua co A/B test</div></div>';
      SS.ui.sheet({title: '🧪 A/B Optimizer', html: html});
    });
  },
  create: function() { SS.ui.toast('Tao A/B test moi', 'info'); },
  complete: function(id) { SS.api.post('/ab-optimizer.php?action=complete', {test_id: id}).then(function() { SS.AbOptimizer.show(); }); }
};
