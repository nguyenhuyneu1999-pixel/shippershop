window.SS = window.SS || {};
SS.FleetManager = {
  show: function() {
    SS.api.get('/fleet-manager.php').then(function(d) {
      var data = d.data || {};
      var members = data.members || [];
      var roleIcons = {shipper: '🏍️', lead: '⭐', manager: '👔'};
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.FleetManager.add()"><i class="fa-solid fa-user-plus"></i> Them thanh vien</button>';
      html += '<div class="text-xs text-muted mb-2">' + (data.active || 0) + '/' + (data.count || 0) + ' dang hoat dong</div>';
      if (!members.length) html += '<div class="empty-state p-3"><div class="empty-icon">👥</div><div class="empty-text">Chua co thanh vien</div></div>';
      for (var i = 0; i < members.length; i++) {
        var m = members[i];
        var statusColor = m.status === 'active' ? 'var(--success)' : 'var(--text-muted)';
        html += '<div class="card mb-2" style="padding:10px"><div class="flex items-center gap-2">'
          + '<img src="' + (m.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-sm" loading="lazy">'
          + '<div class="flex-1"><div class="font-bold text-sm">' + (roleIcons[m.role] || '🏍️') + ' ' + SS.utils.esc(m.fullname || 'User #' + m.user_id) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(m.company || '') + ' · 📍 ' + SS.utils.esc(m.area || 'Chua gan') + ' · Quota: ' + (m.daily_quota || 0) + '/ngay</div>'
          + '<div class="text-xs"><span style="color:' + statusColor + '">' + (m.status === 'active' ? '🟢 Active' : '⚪ Inactive') + '</span> · ' + (m.active_7d || 0) + ' bai/7d · ' + (m.posts || 0) + ' tong</div></div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.FleetManager.remove(' + m.user_id + ')" style="font-size:10px"><i class="fa-solid fa-xmark text-muted"></i></button></div></div>';
      }
      SS.ui.sheet({title: '👥 Doi shipper (' + (data.count || 0) + ')', html: html});
    });
  },
  add: function() {
    SS.ui.modal({title: 'Them thanh vien', html: '<input id="fm-uid" class="form-input mb-2" type="number" placeholder="User ID"><input id="fm-area" class="form-input mb-2" placeholder="Khu vuc (VD: Q1-Q5)"><input id="fm-quota" class="form-input mb-2" type="number" placeholder="Quota don/ngay" value="10"><select id="fm-role" class="form-select"><option value="shipper">🏍️ Shipper</option><option value="lead">⭐ Lead</option><option value="manager">👔 Manager</option></select>', confirmText: 'Them',
      onConfirm: function() { SS.api.post('/fleet-manager.php', {user_id: parseInt(document.getElementById('fm-uid').value), area: document.getElementById('fm-area').value, daily_quota: parseInt(document.getElementById('fm-quota').value) || 10, role: document.getElementById('fm-role').value}).then(function(d) { SS.ui.toast('OK', 'success'); SS.FleetManager.show(); }); }
    });
  },
  remove: function(userId) { SS.api.post('/fleet-manager.php?action=remove', {user_id: userId}).then(function() { SS.FleetManager.show(); }); }
};
