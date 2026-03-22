window.SS = window.SS || {};
SS.ScheduleV3 = {
  show: function() {
    SS.api.get('/schedule-v3.php').then(function(d) {
      var data = d.data || {};
      var upcoming = data.upcoming || [];
      var html = '<div class="flex gap-2 mb-3"><button class="btn btn-primary btn-sm" onclick="SS.ScheduleV3.add()"><i class="fa-solid fa-clock"></i> Hen gio</button>'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.ScheduleV3.addAuto()">⚡ Tu dong gio vang</button></div>';
      html += '<div class="text-xs text-muted mb-2">Gio vang: ' + (data.optimal_hour || 20) + ':00 · ' + upcoming.length + ' cho dang</div>';
      var recIcons = {none: '', daily: '🔁', weekly: '📅'};
      for (var i = 0; i < upcoming.length; i++) {
        var s = upcoming[i];
        html += '<div class="card mb-2" style="padding:10px"><div class="flex justify-between"><div class="flex-1"><div class="text-xs font-bold" style="color:var(--primary)">📅 ' + SS.utils.esc(s.scheduled_at) + ' ' + (recIcons[s.recurring] || '') + '</div>'
          + '<div class="text-sm mt-1">' + SS.utils.esc((s.content || '').substring(0, 80)) + '</div></div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.ScheduleV3.cancel(' + s.id + ')" style="font-size:10px">❌</button></div></div>';
      }
      if (!upcoming.length) html += '<div class="empty-state p-3"><div class="empty-icon">📅</div><div class="empty-text">Chua co bai hen gio</div></div>';
      SS.ui.sheet({title: '📅 Hen dang bai v3', html: html});
    });
  },
  add: function() {
    SS.ui.modal({title: 'Hen gio dang', html: '<textarea id="sv3-content" class="form-textarea mb-2" rows="3" placeholder="Noi dung bai viet"></textarea><input id="sv3-time" class="form-input mb-2" type="datetime-local"><select id="sv3-rec" class="form-select"><option value="none">Mot lan</option><option value="daily">Hang ngay</option><option value="weekly">Hang tuan</option></select>', confirmText: 'Hen',
      onConfirm: function() { SS.api.post('/schedule-v3.php', {content: document.getElementById('sv3-content').value, scheduled_at: document.getElementById('sv3-time').value, recurring: document.getElementById('sv3-rec').value}).then(function() { SS.ScheduleV3.show(); }); }
    });
  },
  addAuto: function() {
    SS.ui.modal({title: 'Tu dong gio vang', html: '<textarea id="sv3a-content" class="form-textarea" rows="3" placeholder="Noi dung (tu dong chon gio vang)"></textarea>', confirmText: 'Hen',
      onConfirm: function() { SS.api.post('/schedule-v3.php', {content: document.getElementById('sv3a-content').value, auto_optimal: true}).then(function(d) { SS.ui.toast('Da hen gio vang!', 'success'); SS.ScheduleV3.show(); }); }
    });
  },
  cancel: function(id) { SS.api.post('/schedule-v3.php?action=cancel', {schedule_id: id}).then(function() { SS.ScheduleV3.show(); }); }
};
