/**
 * ShipperShop Component — Content Quality Score
 */
window.SS = window.SS || {};

SS.ContentScore = {
  show: function(postId) {
    SS.api.get('/content-score.php?post_id=' + postId).then(function(d) {
      var data = d.data || {};
      var color = data.score >= 70 ? 'var(--success)' : (data.score >= 40 ? 'var(--primary)' : 'var(--warning)');
      var html = '<div class="text-center mb-3"><div style="width:80px;height:80px;border-radius:50%;border:4px solid ' + color + ';display:inline-flex;align-items:center;justify-content:center;flex-direction:column"><div style="font-size:24px;font-weight:800;color:' + color + '">' + (data.grade || 'N/A') + '</div><div style="font-size:11px">' + (data.score || 0) + '/100</div></div></div>';
      var factors = data.factors || [];
      for (var i = 0; i < factors.length; i++) {
        html += '<div class="flex justify-between text-sm p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(factors[i].name) + '</span><span class="font-bold" style="color:var(--success)">+' + factors[i].pts + '</span></div>';
      }
      SS.ui.sheet({title: 'Chat luong bai viet', html: html});
    });
  },
  preview: function(text, containerId) {
    if (!text || text.length < 5) return;
    SS.api.get('/content-score.php?text=' + encodeURIComponent(text.substring(0, 500))).then(function(d) {
      var el = document.getElementById(containerId);
      if (!el) return;
      var data = d.data || {};
      var color = data.score >= 70 ? 'var(--success)' : (data.score >= 40 ? 'var(--primary)' : 'var(--warning)');
      el.innerHTML = '<span style="font-size:11px;color:' + color + ';font-weight:700">' + (data.grade || '') + ' ' + (data.score || 0) + '/100</span>';
    }).catch(function() {});
  }
};
