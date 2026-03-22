/**
 * ShipperShop Component — Route Optimizer
 */
window.SS = window.SS || {};

SS.RouteOptimizer = {
  show: function() {
    SS.api.get('/route-optimizer.php').then(function(d) {
      var data = d.data || {};
      var tips = data.tips || [];
      var html = '<div class="text-sm font-bold mb-2">Meo toi uu tuyen duong</div><div class="flex gap-2 flex-wrap mb-3">';
      for (var i = 0; i < tips.length; i++) html += '<span class="chip" style="font-size:11px">💡 ' + SS.utils.esc(tips[i]) + '</span>';
      html += '</div>';
      html += '<div class="text-sm text-muted mb-3">Nhap cac diem giao de toi uu thu tu</div>';
      html += '<textarea id="ro-stops" class="form-textarea mb-2" rows="4" placeholder="Nhap moi dia chi tren 1 dong:\n123 Nguyen Trai, Q5\n456 Le Loi, Q1\n789 CMT8, Q3"></textarea>';
      html += '<button class="btn btn-primary" onclick="SS.RouteOptimizer.optimize()" style="width:100%"><i class="fa-solid fa-route"></i> Toi uu tuyen duong</button>';
      html += '<div id="ro-result" class="mt-3"></div>';
      SS.ui.sheet({title: '🗺️ Toi uu tuyen duong', html: html});
    });
  },
  optimize: function() {
    var text = document.getElementById('ro-stops').value;
    var stops = text.split('\n').filter(function(s) { return s.trim(); }).map(function(s) { return {address: s.trim()}; });
    if (stops.length < 2) { SS.ui.toast('Can it nhat 2 diem', 'warning'); return; }
    SS.api.post('/route-optimizer.php', {stops: stops}).then(function(d) {
      var data = d.data || {};
      var opt = data.optimized || [];
      var html = '<div class="text-center mb-2"><span class="font-bold" style="color:var(--success)">Tiet kiem ~' + (data.saved_percent || 0) + '%</span></div>';
      html += '<div style="padding-left:12px;border-left:3px solid var(--primary)">';
      for (var i = 0; i < opt.length; i++) {
        html += '<div class="flex items-center gap-2 py-1"><span class="font-bold text-xs" style="color:var(--primary);min-width:20px">' + (i + 1) + '</span><span class="text-sm">' + SS.utils.esc(opt[i].address) + '</span></div>';
      }
      html += '</div>';
      document.getElementById('ro-result').innerHTML = html;
    });
  }
};
