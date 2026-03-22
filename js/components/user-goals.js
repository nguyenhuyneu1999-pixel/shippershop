/**
 * ShipperShop Component — User Goals
 */
window.SS = window.SS || {};

SS.UserGoals = {
  show: function() {
    SS.api.get('/user-goals.php').then(function(d) {
      var goals = (d.data || {}).goals || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.UserGoals.add()"><i class="fa-solid fa-plus"></i> Them muc tieu</button>';
      if (!goals.length) {
        html += '<div class="empty-state p-3"><div class="empty-icon">🎯</div><div class="empty-text">Chua co muc tieu</div></div>';
      }
      for (var i = 0; i < goals.length; i++) {
        var g = goals[i];
        var color = g.completed ? 'var(--success)' : (g.progress >= 50 ? 'var(--primary)' : 'var(--warning)');
        html += '<div class="card mb-2" style="padding:12px' + (g.completed ? ';border-left:3px solid var(--success)' : '') + '">'
          + '<div class="flex justify-between items-center mb-1"><span class="text-sm font-medium">' + (g.icon || '🎯') + ' ' + SS.utils.esc(g.name) + '</span>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.UserGoals.del(' + g.id + ')" style="padding:2px"><i class="fa-solid fa-xmark text-muted" style="font-size:10px"></i></button></div>'
          + '<div class="flex justify-between text-xs mb-1"><span>' + (g.current || 0) + '/' + (g.target || 0) + '</span><span class="font-bold" style="color:' + color + '">' + g.progress + '%' + (g.completed ? ' ✅' : '') + '</span></div>'
          + '<div style="height:6px;background:var(--border-light);border-radius:3px"><div style="width:' + g.progress + '%;height:100%;background:' + color + ';border-radius:3px;transition:width 1s"></div></div></div>';
      }
      SS.ui.sheet({title: 'Muc tieu cua toi', html: html});
    });
  },
  add: function() {
    SS.ui.modal({
      title: 'Them muc tieu',
      html: '<select id="ug-type" class="form-select mb-2"><option value="posts_month">Bai viet/thang</option><option value="posts_week">Bai viet/tuan</option><option value="followers">Nguoi theo doi</option><option value="xp">XP</option><option value="streak">Streak ngay</option></select>'
        + '<input id="ug-target" class="form-input" type="number" placeholder="Muc tieu (VD: 10)" value="10">',
      confirmText: 'Them',
      onConfirm: function() {
        SS.api.post('/user-goals.php', {
          type: document.getElementById('ug-type').value,
          target: parseInt(document.getElementById('ug-target').value) || 10
        }).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); SS.UserGoals.show(); });
      }
    });
  },
  del: function(id) {
    SS.api.post('/user-goals.php?action=delete', {goal_id: id}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.UserGoals.show(); });
  }
};
