window.SS = window.SS || {};
SS.IncomeGoal = {
  show: function() {
    SS.api.get('/income-goal.php').then(function(d) {
      var data = d.data || {};
      var curr = data.current || {};
      var color = data.on_track ? 'var(--success)' : 'var(--warning)';
      var html = '<div class="text-center mb-3"><div style="width:90px;height:90px;border-radius:50%;border:5px solid ' + color + ';display:inline-flex;align-items:center;justify-content:center;flex-direction:column"><div style="font-size:20px;font-weight:800;color:' + color + '">' + (data.progress || 0) + '%</div><div style="font-size:10px">' + (data.on_track ? 'Dung tien do' : 'Cham tien do') + '</div></div></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--success)">+' + SS.utils.formatMoney(curr.income || 0) + 'd</div><div class="text-xs text-muted">Thu nhap</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--danger)">-' + SS.utils.formatMoney(curr.fuel || 0) + 'd</div><div class="text-xs text-muted">Xang</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--primary)">' + SS.utils.formatMoney(curr.net || 0) + 'd</div><div class="text-xs text-muted">Loi nhuan</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold">' + SS.utils.formatMoney(data.daily_needed || 0) + 'd</div><div class="text-xs text-muted">Can/ngay (' + (data.days_left || 0) + 'd)</div></div></div>';
      html += '<div class="text-center text-xs text-muted mb-2">Muc tieu: ' + SS.utils.formatMoney((data.goal || {}).monthly_target || 0) + 'd/thang</div>';
      html += '<button class="btn btn-ghost btn-sm" onclick="SS.IncomeGoal.setTarget()"><i class="fa-solid fa-gear"></i> Doi muc tieu</button>';
      SS.ui.sheet({title: '🎯 Muc tieu thu nhap', html: html});
    });
  },
  setTarget: function() {
    SS.ui.closeSheet();
    SS.ui.modal({title: 'Muc tieu thang', html: '<input id="ig-target" class="form-input" type="number" placeholder="Muc tieu (VND)" value="5000000">', confirmText: 'Luu',
      onConfirm: function() { SS.api.post('/income-goal.php', {monthly_target: parseInt(document.getElementById('ig-target').value)}).then(function(d) { SS.ui.toast(d.message, 'success'); }); }
    });
  }
};
