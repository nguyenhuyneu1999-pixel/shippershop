window.SS = window.SS || {};
SS.ShiftLogger = {
  show: function() {
    SS.api.get('/shift-logger.php').then(function(d) {
      var data = d.data || {};
      var active = data.active;
      var stats = data.stats || {};
      var shifts = data.shifts || [];
      var html = '';
      if (active) {
        var elapsed = Math.round((Date.now() - new Date(active.start).getTime()) / 3600000 * 10) / 10;
        html += '<div class="card mb-3" style="padding:14px;border:2px solid var(--success);text-align:center"><div style="font-size:12px;color:var(--success);font-weight:600">🟢 DANG LAM</div><div class="font-bold text-lg mt-1">' + elapsed + 'h</div><div class="text-xs text-muted">Bat dau: ' + SS.utils.ago(active.start) + '</div>'
          + '<button class="btn btn-primary mt-2" onclick="SS.ShiftLogger.clockOut()" style="width:100%">⏹ Ket thuc ca</button></div>';
      } else {
        html += '<button class="btn btn-primary mb-3" onclick="SS.ShiftLogger.clockIn()" style="width:100%">▶️ Bat dau ca lam</button>';
      }
      html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:12px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:6px"><div class="font-bold">' + (stats.today || 0) + 'h</div><div class="text-muted">Nay</div></div>'
        + '<div class="card" style="padding:6px"><div class="font-bold">' + (stats.week || 0) + 'h</div><div class="text-muted">Tuan</div></div>'
        + '<div class="card" style="padding:6px"><div class="font-bold">' + (stats.month || 0) + 'h</div><div class="text-muted">Thang</div></div>'
        + '<div class="card" style="padding:6px"><div class="font-bold" style="color:' + ((stats.overtime || 0) > 0 ? 'var(--danger)' : 'var(--success)') + '">' + (stats.overtime || 0) + 'h</div><div class="text-muted">OT</div></div></div>';
      for (var i = 0; i < Math.min(shifts.length, 10); i++) {
        var s = shifts[i];
        if (!s.end) continue;
        html += '<div class="flex justify-between text-xs p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.ago(s.start) + '</span><span class="font-bold">' + (s.hours || 0) + 'h · ' + (s.deliveries || 0) + ' don</span></div>';
      }
      SS.ui.sheet({title: '⏰ Ca lam viec', html: html});
    });
  },
  clockIn: function() { SS.api.post('/shift-logger.php', {}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ShiftLogger.show(); }); },
  clockOut: function() {
    SS.ui.modal({title: 'Ket thuc ca', html: '<input id="sl-del" class="form-input" type="number" placeholder="So don da giao">', confirmText: 'Ket thuc',
      onConfirm: function() { SS.api.post('/shift-logger.php?action=clock_out', {deliveries: parseInt(document.getElementById('sl-del').value) || 0}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ShiftLogger.show(); }); }
    });
  }
};
