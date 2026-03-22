window.SS = window.SS || {};
SS.DailyPlanner = {
  show: function(date) {
    date = date || new Date().toISOString().split('T')[0];
    SS.api.get('/daily-planner.php?date=' + date).then(function(d) {
      var data = d.data || {};
      var slots = data.slots || [];
      var weatherIcons = {sunny: '☀️', cloudy: '☁️', rain: '🌧️', storm: '⛈️', hot: '🔥'};
      var html = '<div class="flex justify-between items-center mb-3"><span class="font-bold">' + SS.utils.esc(data.date || '') + ' ' + (weatherIcons[data.weather] || '☀️') + '</span>'
        + '<div class="text-right"><span class="font-bold" style="color:var(--primary)">' + (data.progress || 0) + '%</span><div class="text-xs text-muted">' + (data.total_actual || 0) + '/' + (data.total_target || 0) + ' don</div></div></div>';
      html += '<div style="height:6px;background:var(--border-light);border-radius:3px;margin-bottom:12px"><div style="width:' + Math.min(100, data.progress || 0) + '%;height:100%;background:var(--success);border-radius:3px"></div></div>';
      var typeIcons = {delivery: '🏍️', break: '☕'};
      for (var i = 0; i < slots.length; i++) {
        var s = slots[i];
        var isDone = s.status === 'done';
        var isBreak = s.type === 'break';
        html += '<div class="card mb-2" style="padding:10px' + (isBreak ? ';background:var(--border-light)30' : '') + (isDone ? ';opacity:0.6' : '') + '">'
          + '<div class="flex justify-between items-center"><div><div class="text-sm ' + (isDone ? '' : 'font-bold') + '">' + (typeIcons[s.type] || '📍') + ' ' + SS.utils.esc(s.time) + '</div>'
          + (s.area ? '<div class="text-xs text-muted">📍 ' + SS.utils.esc(s.area) + '</div>' : '')
          + (!isBreak ? '<div class="text-xs">Target: ' + s.target + ' don' + (s.actual ? ' · Thuc: ' + s.actual : '') + '</div>' : '<div class="text-xs text-muted">Nghi ngoi</div>') + '</div>'
          + (!isDone && !isBreak ? '<button class="btn btn-ghost btn-sm" onclick="SS.DailyPlanner.done(\'' + date + '\',' + s.id + ')" style="font-size:10px">✅</button>' : '') + '</div></div>';
      }
      html += '<button class="btn btn-ghost btn-sm mt-2" onclick="SS.DailyPlanner.addSlot(\'' + date + '\')"><i class="fa-solid fa-plus"></i> Them slot</button>';
      SS.ui.sheet({title: '📅 Ke hoach ngay', html: html});
    });
  },
  done: function(date, slotId) { SS.api.post('/daily-planner.php', {slot_id: slotId, status: 'done'}).then(function() { SS.DailyPlanner.show(date); }); },
  addSlot: function(date) {
    SS.ui.modal({title: 'Them slot', html: '<input id="dpl-time" class="form-input mb-2" placeholder="VD: 09:00-11:00"><input id="dpl-area" class="form-input mb-2" placeholder="Khu vuc"><input id="dpl-target" class="form-input" type="number" placeholder="Target don" value="5">', confirmText: 'Them',
      onConfirm: function() { SS.api.post('/daily-planner.php?action=add_slot', {time: document.getElementById('dpl-time').value, area: document.getElementById('dpl-area').value, target: parseInt(document.getElementById('dpl-target').value) || 5}).then(function() { SS.DailyPlanner.show(date); }); }
    });
  }
};
