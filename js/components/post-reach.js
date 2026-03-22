/**
 * ShipperShop Component — Post Reach Estimator
 */
window.SS = window.SS || {};

SS.PostReach = {
  show: function(postId, userId) {
    var url = postId ? '/post-reach.php?post_id=' + postId : '/post-reach.php?user_id=' + userId;
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var html = '<div class="text-center mb-3" style="padding:16px;background:linear-gradient(135deg,var(--primary),#6d28d9);color:#fff;border-radius:12px">'
        + '<div style="font-size:32px;font-weight:800">' + SS.utils.fN(data.total_estimated_reach || 0) + '</div>'
        + '<div class="text-sm" style="opacity:0.8">Uoc tinh tiep can</div></div>';
      var items = [
        {icon: '👥', label: 'Follower truc tiep', value: data.direct_followers || 0},
        {icon: '🏘️', label: 'Thanh vien nhom', value: data.group_reach || 0},
        {icon: '🌐', label: 'Organic', value: data.organic_estimate || 0},
      ];
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center">';
      for (var i = 0; i < items.length; i++) {
        html += '<div class="card" style="padding:10px"><div>' + items[i].icon + '</div><div class="font-bold">' + SS.utils.fN(items[i].value) + '</div><div class="text-xs text-muted">' + items[i].label + '</div></div>';
      }
      html += '</div>';
      html += '<div class="text-xs text-muted text-center mt-3">' + (data.groups_in || 0) + ' nhom · TB ' + (data.avg_views || 0) + ' luot xem</div>';
      SS.ui.sheet({title: 'Pham vi tiep can', html: html});
    });
  }
};
