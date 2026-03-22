/**
 * ShipperShop Component — A/B Split Test
 */
window.SS = window.SS || {};

SS.AbSplit = {
  show: function() {
    SS.api.get('/ab-split.php').then(function(d) {
      var splits = (d.data || {}).splits || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.AbSplit.create()"><i class="fa-solid fa-plus"></i> Tao A/B test</button>';
      if (!splits.length) html += '<div class="empty-state p-3"><div class="empty-icon">🔬</div><div class="empty-text">Chua co test nao</div></div>';
      for (var i = 0; i < splits.length; i++) {
        var s = splits[i];
        var wColor = s.winner === 'A' ? 'var(--success)' : (s.winner === 'B' ? 'var(--primary)' : 'var(--text-muted)');
        html += '<div class="card mb-2" style="padding:12px"><div class="flex justify-between mb-2"><span class="font-bold text-sm">' + SS.utils.esc(s.name) + '</span><span class="font-bold" style="color:' + wColor + '">Winner: ' + s.winner + '</span></div>'
          + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;text-align:center">'
          + '<div style="padding:6px;border-radius:6px;background:' + (s.winner === 'A' ? 'var(--success)15' : 'var(--border-light)') + '"><div class="text-xs font-bold">A</div><div class="font-bold">' + (s.a_engagement || 0) + '</div></div>'
          + '<div style="padding:6px;border-radius:6px;background:' + (s.winner === 'B' ? 'var(--primary)15' : 'var(--border-light)') + '"><div class="text-xs font-bold">B</div><div class="font-bold">' + (s.b_engagement || 0) + '</div></div></div>'
          + '<div class="text-xs text-muted mt-1">' + SS.utils.ago(s.created_at) + '</div></div>';
      }
      SS.ui.sheet({title: '🔬 A/B Split (' + splits.length + ')', html: html});
    });
  },
  create: function() {
    SS.ui.modal({title: 'Tao A/B test', html: '<input id="abs-name" class="form-input mb-2" placeholder="Ten test">'
      + '<input id="abs-a" class="form-input mb-2" placeholder="Post ID A" type="number">'
      + '<input id="abs-b" class="form-input" placeholder="Post ID B" type="number">', confirmText: 'Tao',
      onConfirm: function() {
        SS.api.post('/ab-split.php', {name: document.getElementById('abs-name').value, post_a_id: parseInt(document.getElementById('abs-a').value), post_b_id: parseInt(document.getElementById('abs-b').value)}).then(function(d) { SS.ui.toast('OK', 'success'); SS.AbSplit.show(); });
      }
    });
  }
};
