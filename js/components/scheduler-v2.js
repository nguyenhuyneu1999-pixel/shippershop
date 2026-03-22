/**
 * ShipperShop Component — Content Scheduler V2
 */
window.SS = window.SS || {};

SS.SchedulerV2 = {
  show: function() {
    SS.api.get('/scheduler-v2.php').then(function(d) {
      var data = d.data || {};
      var schedules = data.schedules || [];
      var optimal = data.optimal_hours || [];

      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.SchedulerV2.create()"><i class="fa-solid fa-clock"></i> Len lich</button>';

      if (optimal.length) {
        html += '<div class="text-xs text-muted mb-2">Gio tot nhat:';
        for (var o = 0; o < Math.min(optimal.length, 3); o++) html += ' 🕐' + optimal[o].h + 'h';
        html += '</div>';
      }

      if (!schedules.length) html += '<div class="empty-state p-3"><div class="empty-icon">📅</div><div class="empty-text">Chua co lich</div></div>';
      for (var i = 0; i < schedules.length; i++) {
        var s = schedules[i];
        var statusColor = s.status === 'active' ? 'var(--success)' : 'var(--text-muted)';
        var recurLabels = {none: '', daily: '🔄 Hang ngay', weekly: '🔄 Hang tuan'};
        html += '<div class="card mb-2" style="padding:10px"><div class="flex justify-between mb-1"><span class="text-sm">' + SS.utils.esc((s.content || '').substring(0, 50)) + '</span>'
          + '<div class="flex gap-1"><button class="btn btn-ghost btn-sm" onclick="SS.SchedulerV2.toggle(' + s.id + ',\'' + (s.status === 'active' ? 'pause' : 'resume') + '\')" style="font-size:10px">' + (s.status === 'active' ? '⏸' : '▶') + '</button>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.SchedulerV2.del(' + s.id + ')" style="font-size:10px">🗑</button></div></div>'
          + '<div class="text-xs text-muted">⏰ ' + SS.utils.esc(s.next_run || '') + ' ' + (recurLabels[s.recurring] || '') + ' · <span style="color:' + statusColor + '">' + s.status + '</span></div></div>';
      }
      SS.ui.sheet({title: '📅 Scheduler v2 (' + schedules.length + ')', html: html});
    });
  },
  create: function() {
    SS.ui.modal({title: 'Len lich dang bai', html: '<textarea id="sv2-content" class="form-textarea mb-2" rows="3" placeholder="Noi dung..."></textarea>'
      + '<input id="sv2-time" class="form-input mb-2" type="datetime-local">'
      + '<select id="sv2-recur" class="form-select"><option value="none">1 lan</option><option value="daily">Hang ngay</option><option value="weekly">Hang tuan</option></select>', confirmText: 'Tao',
      onConfirm: function() {
        SS.api.post('/scheduler-v2.php', {content: document.getElementById('sv2-content').value, schedule_at: document.getElementById('sv2-time').value.replace('T', ' '), recurring: document.getElementById('sv2-recur').value}).then(function(d) { SS.ui.toast('OK', 'success'); SS.SchedulerV2.show(); });
      }
    });
  },
  toggle: function(id, action) { SS.api.post('/scheduler-v2.php?action=' + action, {schedule_id: id}).then(function() { SS.SchedulerV2.show(); }); },
  del: function(id) { SS.api.post('/scheduler-v2.php?action=delete', {schedule_id: id}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.SchedulerV2.show(); }); }
};
