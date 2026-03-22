/**
 * ShipperShop Component — Retention Score (Admin)
 */
window.SS = window.SS || {};

SS.RetentionScore = {
  show: function() {
    SS.api.get('/retention-score.php').then(function(d) {
      var data = d.data || {};
      var atRisk = data.at_risk || [];
      var html = '<div class="text-center mb-3"><div class="font-bold text-lg" style="color:var(--warning)">' + (data.count || 0) + '</div><div class="text-xs text-muted">Nguoi dung co nguy co</div></div>';
      for (var i = 0; i < Math.min(atRisk.length, 15); i++) {
        var u = atRisk[i];
        html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light);cursor:pointer" onclick="SS.RetentionScore.detail(' + u.id + ')">'
          + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><div class="text-sm">' + SS.utils.esc(u.fullname) + '</div><div class="text-xs text-muted">' + u.total_posts + ' bai · ' + (u.last_post ? SS.utils.ago(u.last_post) : 'Chua dang') + '</div></div></div>';
      }
      SS.ui.sheet({title: 'Retention', html: html});
    });
  },
  detail: function(userId) {
    SS.ui.closeSheet();
    SS.api.get('/retention-score.php?user_id=' + userId).then(function(d) {
      var data = d.data || {};
      var f = data.factors || {};
      var color = data.score >= 70 ? 'var(--success)' : (data.score >= 40 ? 'var(--warning)' : 'var(--danger)');
      var html = '<div class="text-center mb-3"><div style="width:70px;height:70px;border-radius:50%;border:4px solid ' + color + ';display:inline-flex;align-items:center;justify-content:center"><div style="font-size:22px;font-weight:800;color:' + color + '">' + (data.score || 0) + '</div></div>'
        + '<div class="text-sm mt-1">Risk: ' + SS.utils.esc(data.risk || '') + '</div></div>';
      html += '<div class="text-sm"><div class="flex justify-between p-1"><span>Ngay chua dang</span><span class="font-bold">' + (f.days_since_post || 0) + 'd</span></div>'
        + '<div class="flex justify-between p-1"><span>Bai 7 ngay</span><span class="font-bold">' + (f.posts_7d || 0) + '</span></div>'
        + '<div class="flex justify-between p-1"><span>Streak</span><span class="font-bold">' + (f.streak || 0) + '</span></div>'
        + '<div class="flex justify-between p-1"><span>Followers</span><span class="font-bold">' + (f.followers || 0) + '</span></div></div>';
      SS.ui.sheet({title: 'Chi tiet retention', html: html});
    });
  }
};
