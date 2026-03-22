/**
 * ShipperShop Component — Engagement Score V2 (Admin)
 */
window.SS = window.SS || {};

SS.EngagementScoreV2 = {
  show: function() {
    SS.api.get('/engagement-score-v2.php').then(function(d) {
      var users = d.data || [];
      var tierColors = {diamond: '#b9f2ff', gold: '#fbbf24', silver: '#c0c0c0', bronze: '#cd7f32', starter: 'var(--border-light)'};
      var tierIcons = {diamond: '💎', gold: '🥇', silver: '🥈', bronze: '🥉', starter: '⭐'};
      var html = '';
      for (var i = 0; i < Math.min(users.length, 20); i++) {
        var u = users[i];
        html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light);cursor:pointer" onclick="SS.EngagementScoreV2.detail(' + u.id + ')">'
          + '<span class="font-bold text-sm" style="width:24px;color:var(--text-muted)">#' + (i + 1) + '</span>'
          + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><div class="text-sm">' + SS.utils.esc(u.fullname) + '</div>'
          + '<div class="text-xs text-muted">' + u.total_posts + ' bai · ' + (u.followers || 0) + ' followers · 🔥' + (u.streak || 0) + '</div></div>'
          + '<div class="text-right"><div class="font-bold" style="color:var(--primary)">' + u.engagement_score + '</div><div class="text-xs">' + (tierIcons[u.tier] || '') + ' ' + SS.utils.esc(u.tier) + '</div></div></div>';
      }
      SS.ui.sheet({title: '🏆 Engagement Leaderboard', html: html});
    });
  },
  detail: function(userId) {
    SS.ui.closeSheet();
    SS.api.get('/engagement-score-v2.php?action=user&user_id=' + userId).then(function(d) {
      var data = d.data || {};
      var bd = data.breakdown || {};
      var html = '<div class="text-center mb-3"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.score || 0) + '</div><div class="text-xs text-muted">Engagement Score</div></div>';
      html += '<div class="text-sm font-bold mb-2">Phan tich</div>';
      var items = [{label: 'Posts (x3)', val: bd.posts || 0}, {label: 'Likes', val: bd.likes || 0}, {label: 'Followers (x5)', val: bd.followers || 0}, {label: 'Streak (x2)', val: bd.streak || 0}, {label: 'XP (x0.1)', val: bd.xp || 0}];
      for (var i = 0; i < items.length; i++) {
        html += '<div class="flex justify-between text-sm p-1" style="border-bottom:1px solid var(--border-light)"><span>' + items[i].label + '</span><span class="font-bold">+' + items[i].val + '</span></div>';
      }
      SS.ui.sheet({title: 'Chi tiet', html: html});
    });
  }
};
