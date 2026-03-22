/**
 * ShipperShop Component — KPI Dashboard
 */
window.SS = window.SS || {};

SS.KpiDashboard = {
  show: function() {
    SS.api.get('/kpi-dashboard.php').then(function(d) {
      var data = d.data || {};
      var kpis = data.kpis || [];
      var html = '<div class="text-center mb-3"><div style="font-size:28px;font-weight:800;color:var(--primary)">' + (data.overall_score || 0) + '%</div><div class="text-xs text-muted">' + (data.achieved || 0) + '/' + (data.total || 0) + ' KPI dat</div></div>';
      for (var i = 0; i < kpis.length; i++) {
        var k = kpis[i];
        var color = k.status === 'achieved' ? 'var(--success)' : (k.status === 'on_track' ? 'var(--primary)' : 'var(--warning)');
        html += '<div class="card mb-2" style="padding:10px' + (k.status === 'achieved' ? ';border-left:3px solid var(--success)' : '') + '">'
          + '<div class="flex justify-between mb-1"><span class="text-sm">' + k.icon + ' ' + SS.utils.esc(k.name) + '</span><span class="text-xs text-muted">' + SS.utils.esc(k.period) + '</span></div>'
          + '<div class="flex justify-between text-xs mb-1"><span>' + k.current + ' / ' + k.target + '</span><span class="font-bold" style="color:' + color + '">' + k.progress + '%' + (k.status === 'achieved' ? ' ✅' : '') + '</span></div>'
          + '<div style="height:6px;background:var(--border-light);border-radius:3px"><div style="width:' + Math.min(100, k.progress) + '%;height:100%;background:' + color + ';border-radius:3px;transition:width .5s"></div></div></div>';
      }
      html += '<button class="btn btn-ghost btn-sm mt-2" onclick="SS.KpiDashboard.setTargets()"><i class="fa-solid fa-gear"></i> Thiet lap KPI</button>';
      SS.ui.sheet({title: '📊 KPI Dashboard', html: html});
    });
  },
  setTargets: function() {
    SS.ui.closeSheet();
    SS.ui.modal({title: 'Thiet lap KPI', html: '<label class="text-xs text-muted">Bai/ngay</label><input id="kpi-dp" class="form-input mb-2" type="number" value="5">'
      + '<label class="text-xs text-muted">Likes/tuan</label><input id="kpi-wl" class="form-input mb-2" type="number" value="50">'
      + '<label class="text-xs text-muted">Followers/thang</label><input id="kpi-mf" class="form-input" type="number" value="10">', confirmText: 'Luu',
      onConfirm: function() {
        SS.api.post('/kpi-dashboard.php', {daily_posts: parseInt(document.getElementById('kpi-dp').value), weekly_likes: parseInt(document.getElementById('kpi-wl').value), monthly_followers: parseInt(document.getElementById('kpi-mf').value)}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.KpiDashboard.show(); });
      }
    });
  }
};
