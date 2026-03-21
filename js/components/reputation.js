/**
 * ShipperShop Component — Reputation Score
 * Shows user reputation level + score, breakdown modal
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.Reputation = {

  render: function(containerId, userId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    SS.api.get('/reputation.php?user_id=' + userId).then(function(d) {
      var s = d.data;
      if (!s) { el.innerHTML = ''; return; }
      var color = s.pct >= 80 ? '#22c55e' : (s.pct >= 50 ? '#f59e0b' : (s.pct >= 25 ? '#3b82f6' : '#94a3b8'));
      el.innerHTML = '<div class="card mt-3" onclick="SS.Reputation.showDetail(' + userId + ')" style="cursor:pointer"><div class="card-body">'
        + '<div class="flex items-center justify-between mb-2">'
        + '<div class="text-sm font-bold">Uy tín</div>'
        + '<span style="font-size:12px;font-weight:700;color:' + color + ';background:' + color + '15;padding:2px 8px;border-radius:10px">' + SS.utils.esc(s.level) + '</span>'
        + '</div>'
        + '<div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden">'
        + '<div style="height:100%;width:' + s.pct + '%;background:' + color + ';border-radius:4px;transition:width .8s ease"></div>'
        + '</div>'
        + '<div class="flex justify-between mt-1"><span class="text-xs text-muted">' + SS.utils.fN(s.score) + ' / ' + SS.utils.fN(s.max) + '</span><span class="text-xs text-muted">' + s.pct + '%</span></div>'
        + '</div></div>';
    }).catch(function() { el.innerHTML = ''; });
  },

  showDetail: function(userId) {
    SS.api.get('/reputation.php?user_id=' + userId).then(function(d) {
      var s = d.data;
      if (!s) return;
      var bd = s.breakdown || {};
      var labels = {posts:'Bài viết',likes_received:'Thành công nhận',comments_received:'Ghi chú nhận',engagement:'Tương tác',followers:'Người theo dõi',streak:'Streak',xp:'Kinh nghiệm',age_days:'Thâm niên',verified:'Xác minh'};
      var html = '<div class="text-center mb-3"><div style="font-size:36px;font-weight:900;color:var(--primary)">' + SS.utils.fN(s.score) + '</div><div class="text-sm text-muted">' + SS.utils.esc(s.level) + '</div></div>';
      for (var key in bd) {
        var item = bd[key];
        var pct = item.max > 0 ? Math.round(item.points / item.max * 100) : 0;
        html += '<div style="margin-bottom:8px"><div class="flex justify-between text-xs mb-1"><span>' + (labels[key] || key) + ' (' + SS.utils.fN(item.value) + ')</span><span class="text-muted">' + item.points + '/' + item.max + '</span></div>'
          + '<div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden"><div style="height:100%;width:' + pct + '%;background:var(--primary);border-radius:2px"></div></div></div>';
      }
      SS.ui.sheet({title: 'Chi tiết uy tín', html: html});
    });
  }
};
