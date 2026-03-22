/**
 * ShipperShop Component — Leaderboard Seasons
 * Competitive weekly/monthly leaderboards
 */
window.SS = window.SS || {};

SS.LeaderboardSeasons = {
  show: function(period, metric) {
    period = period || 'monthly';
    metric = metric || 'posts';
    SS.api.get('/leaderboard-seasons.php?period=' + period + '&metric=' + metric).then(function(d) {
      var data = d.data || {};
      var board = data.leaderboard || [];
      var rewards = data.rewards || [];

      // Period + metric tabs
      var html = '<div class="flex gap-2 mb-2">'
        + '<div class="chip ' + (period === 'weekly' ? 'chip-active' : '') + '" onclick="SS.LeaderboardSeasons.show(\'weekly\',\'' + metric + '\')" style="cursor:pointer">Tuan</div>'
        + '<div class="chip ' + (period === 'monthly' ? 'chip-active' : '') + '" onclick="SS.LeaderboardSeasons.show(\'monthly\',\'' + metric + '\')" style="cursor:pointer">Thang</div></div>';

      var metrics = [['posts','📝'],['likes','❤️'],['deliveries','📦'],['xp','⭐'],['comments','💬']];
      html += '<div class="flex gap-1 mb-3" style="overflow-x:auto">';
      for (var m = 0; m < metrics.length; m++) {
        var active = metric === metrics[m][0] ? 'chip-active' : '';
        html += '<div class="chip ' + active + '" onclick="SS.LeaderboardSeasons.show(\'' + period + '\',\'' + metrics[m][0] + '\')" style="cursor:pointer;white-space:nowrap">' + metrics[m][1] + '</div>';
      }
      html += '</div>';

      html += '<div class="text-center text-xs text-muted mb-3">' + SS.utils.esc(data.season || '') + ' · Ket thuc: ' + SS.utils.esc(data.ends_in || '') + '</div>';

      // Rewards
      for (var r = 0; r < rewards.length; r++) {
        html += '<div class="text-xs text-center" style="color:' + rewards[r].color + '">' + SS.utils.esc(rewards[r].reward) + '</div>';
      }

      // Leaderboard
      for (var i = 0; i < board.length; i++) {
        var u = board[i];
        var medal = i === 0 ? '🥇' : (i === 1 ? '🥈' : (i === 2 ? '🥉' : '#' + (i + 1)));
        var bg = i < 3 ? 'linear-gradient(135deg,' + (rewards[i] || {}).color + '10,transparent)' : 'transparent';
        html += '<div class="flex items-center gap-3 p-2" style="border-bottom:1px solid var(--border-light);background:' + bg + '">'
          + '<span style="width:28px;text-align:center;font-weight:700;font-size:' + (i < 3 ? '16px' : '12px') + '">' + medal + '</span>'
          + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-sm" loading="lazy">'
          + '<div class="flex-1"><div class="font-medium text-sm">' + SS.utils.esc(u.fullname) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(u.shipping_company || '') + '</div></div>'
          + '<div class="font-bold" style="color:var(--primary)">' + SS.utils.fN(u.season_score || 0) + '</div></div>';
      }

      if (!board.length) html += '<div class="empty-state p-4"><div class="empty-text">Chua co du lieu mua nay</div></div>';
      SS.ui.sheet({title: '🏆 Bang xep hang', html: html});
    });
  }
};
