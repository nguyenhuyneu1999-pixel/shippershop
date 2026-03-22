/**
 * ShipperShop Component — Weekly Challenge
 */
window.SS = window.SS || {};

SS.WeeklyChallenge = {
  show: function() {
    SS.api.get('/weekly-challenge.php').then(function(d) {
      var data = d.data || {};
      var challenges = data.challenges || [];

      var html = '<div class="flex justify-between items-center mb-3"><div><div class="font-bold">' + (data.completed || 0) + '/' + (data.total || 0) + ' hoan thanh</div><div class="text-xs text-muted">Ket thuc: ' + SS.utils.esc(data.week_ends || '') + '</div></div>'
        + '<div class="text-right"><div class="font-bold" style="color:var(--primary)">+' + (data.xp_earned || 0) + ' XP</div></div></div>';

      for (var i = 0; i < challenges.length; i++) {
        var c = challenges[i];
        var color = c.completed ? 'var(--success)' : (c.progress >= 50 ? 'var(--primary)' : 'var(--border)');
        html += '<div class="card mb-2" style="padding:10px' + (c.completed ? ';border-left:3px solid var(--success);opacity:0.8' : '') + '">'
          + '<div class="flex justify-between items-center mb-1"><span class="text-sm">' + c.icon + ' ' + SS.utils.esc(c.name) + (c.completed ? ' ✅' : '') + '</span>'
          + '<span class="text-xs font-bold" style="color:var(--primary)">+' + c.xp + ' XP</span></div>'
          + '<div class="text-xs text-muted mb-1">' + SS.utils.esc(c.desc) + '</div>'
          + '<div class="flex justify-between text-xs mb-1"><span>' + c.current + '/' + c.target + '</span><span>' + c.progress + '%</span></div>'
          + '<div style="height:6px;background:var(--border-light);border-radius:3px"><div style="width:' + c.progress + '%;height:100%;background:' + color + ';border-radius:3px;transition:width .5s"></div></div></div>';
      }
      SS.ui.sheet({title: '🏆 Thach thuc tuan', html: html});
    });
  }
};
