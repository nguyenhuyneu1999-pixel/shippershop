/**
 * ShipperShop Component — Shift Planner
 */
window.SS = window.SS || {};

SS.ShiftPlanner = {
  show: function() {
    SS.api.get('/shift-planner.php').then(function(d) {
      var data = d.data || {};
      var plan = data.plan || {};
      var days = data.days || [];
      var shifts = data.shifts || [];
      var html = '<div class="text-center mb-3"><span class="font-bold">' + (data.total_shifts || 0) + ' ca</span> · <span class="text-muted">' + (data.hours_per_week || 0) + 'h/tuan</span></div>';
      html += '<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:12px"><tr><th></th>';
      for (var i = 0; i < days.length; i++) html += '<th style="padding:6px;text-align:center">' + days[i] + '</th>';
      html += '</tr>';
      for (var s = 0; s < shifts.length; s++) {
        html += '<tr><td style="padding:4px;white-space:nowrap">' + shifts[s].icon + '</td>';
        for (var di = 0; di < days.length; di++) {
          var active = (plan[days[di]] || []).indexOf(shifts[s].id) >= 0;
          html += '<td style="padding:2px;text-align:center"><div style="width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;background:' + (active ? 'var(--primary)' : 'var(--border-light)') + ';color:' + (active ? '#fff' : 'var(--text-muted)') + '" onclick="SS.ShiftPlanner.toggle(\'' + days[di] + '\',\'' + shifts[s].id + '\')">' + (active ? '✓' : '') + '</div></td>';
        }
        html += '</tr>';
      }
      html += '</table></div>';
      SS.ui.sheet({title: 'Lich lam viec', html: html});
    });
  },
  toggle: function(day, shift) {
    SS.api.post('/shift-planner.php?action=toggle', {day: day, shift: shift}).then(function() { SS.ShiftPlanner.show(); });
  }
};
