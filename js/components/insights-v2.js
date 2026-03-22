/**
 * ShipperShop Component — Content Insights V2
 */
window.SS = window.SS || {};

SS.InsightsV2 = {
  showPost: function(postId) {
    SS.api.get('/insights-v2.php?post_id=' + postId).then(function(d) {
      var data = d.data || {};
      if (!data.post_id) { SS.ui.toast('Khong tim thay', 'error'); return; }
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--danger)">❤️ ' + (data.likes || 0) + '</div><div class="text-xs text-muted">Likes</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">💬 ' + (data.comments || 0) + '</div><div class="text-xs text-muted">Comments</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">🔄 ' + (data.shares || 0) + '</div><div class="text-xs text-muted">Shares</div></div></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;text-align:center">'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--primary)">' + (data.engagement_rate || 0) + '%</div><div class="text-xs text-muted">Eng Rate</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (data.eng_per_hour || 0) + '</div><div class="text-xs text-muted">Eng/h</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (data.hour_posted || 0) + ':00</div><div class="text-xs text-muted">Gio dang</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + Math.round(data.age_hours || 0) + 'h</div><div class="text-xs text-muted">Tuoi bai</div></div></div>';
      SS.ui.sheet({title: 'Insights #' + postId, html: html});
    });
  },
  showUser: function(userId) {
    SS.api.get('/insights-v2.php?user_id=' + userId).then(function(d) {
      var data = d.data || {};
      var html = '<div class="text-center mb-3"><div class="font-bold text-lg">' + (data.posts_analyzed || 0) + ' bai phan tich</div></div>'
        + '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.total_engagement || 0) + '</div><div class="text-xs text-muted">Tong tuong tac</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.avg_engagement || 0) + '</div><div class="text-xs text-muted">TB/bai</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--primary)">' + (data.avg_engagement_rate || 0) + '%</div><div class="text-xs text-muted">Eng Rate</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--success)">' + (data.best_engagement || 0) + '</div><div class="text-xs text-muted">Best post</div></div></div>';
      SS.ui.sheet({title: 'User Insights', html: html});
    });
  }
};
