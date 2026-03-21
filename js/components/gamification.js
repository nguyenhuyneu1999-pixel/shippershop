/**
 * ShipperShop Component — Gamification Widget
 * XP progress bar, level badge, streak indicator, leaderboard
 */
window.SS = window.SS || {};

SS.Gamification = {

  renderXPCard: function(data) {
    if (!data) return '';
    var level = data.level || {};
    var streak = data.streak || {};
    var progress = level.progress || 0;

    return '<div class="card mb-3">'
      + '<div class="card-body" style="text-align:center">'
      + '<div style="font-size:36px;margin-bottom:4px">' + SS.utils.esc(level.icon || '🌱') + '</div>'
      + '<div style="font-size:16px;font-weight:700;color:var(--primary)">' + SS.utils.esc(level.level || 'Tân binh') + '</div>'
      + '<div style="font-size:24px;font-weight:800;margin:8px 0">' + SS.utils.fN(data.total_xp || 0) + ' XP</div>'
      + (level.next_level ? '<div class="progress-bar" style="margin:12px auto;max-width:200px"><div class="progress-fill" style="width:' + progress + '%"></div></div>'
        + '<div class="text-sm text-muted">' + progress + '% tới ' + SS.utils.esc(level.next_level) + ' (' + SS.utils.fN(level.next_xp) + ' XP)</div>' : '<div class="text-sm text-success">MAX LEVEL!</div>')
      + '<div style="display:flex;justify-content:center;gap:24px;margin-top:16px">'
      + '<div class="stat-card"><div class="stat-number">' + (streak.current || 0) + '</div><div class="stat-label">Streak</div></div>'
      + '<div class="stat-card"><div class="stat-number">' + (data.today_xp || 0) + '</div><div class="stat-label">XP hôm nay</div></div>'
      + '<div class="stat-card"><div class="stat-number">' + (streak.longest || 0) + '</div><div class="stat-label">Kỷ lục</div></div>'
      + '</div>'
      + (data.checked_in ? '' : '<button class="btn btn-primary btn-block mt-4" onclick="SS.Gamification.checkin()"><i class="fa-solid fa-fire"></i> Check-in +5 XP</button>')
      + '</div></div>';
  },

  renderLeaderboard: function(leaders, myRank) {
    if (!leaders || !leaders.length) return '<div class="empty-state"><div class="empty-text">Chưa có dữ liệu</div></div>';
    var html = '<div class="card">';

    for (var i = 0; i < leaders.length; i++) {
      var l = leaders[i];
      var rank = i + 1;
      var medal = rank === 1 ? '🥇' : (rank === 2 ? '🥈' : (rank === 3 ? '🥉' : '#' + rank));
      var isSelf = SS.store && SS.store.userId() === parseInt(l.id);

      html += '<div class="list-item' + (isSelf ? '" style="background:var(--primary-50)' : '') + '">'
        + '<div style="width:32px;text-align:center;font-weight:700;font-size:' + (rank <= 3 ? '18' : '13') + 'px">' + medal + '</div>'
        + '<img class="avatar avatar-sm" src="' + (l.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
        + '<div class="flex-1 min-width:0">'
        + '<div class="list-title">' + SS.utils.esc(l.fullname) + (isSelf ? ' <span class="badge badge-primary">Bạn</span>' : '') + '</div>'
        + '<div class="list-subtitle">' + SS.utils.esc(l.shipping_company || '') + ' · ' + (l.level ? l.level.icon + ' ' + l.level.level : '') + '</div>'
        + '</div>'
        + '<div style="text-align:right"><div style="font-weight:700;color:var(--primary)">' + SS.utils.fN(l.total_xp) + '</div><div class="text-sm text-muted">XP</div></div>'
        + '</div>';
    }

    html += '</div>';
    if (myRank) html += '<div class="text-center text-sm text-muted mt-2">Thứ hạng của bạn: #' + myRank + '</div>';
    return html;
  },

  renderBadges: function(badges) {
    if (!badges || !badges.length) return '<div class="text-center text-muted text-sm p-4">Chưa có huy hiệu</div>';
    var html = '<div style="display:flex;flex-wrap:wrap;gap:12px;padding:12px">';
    for (var i = 0; i < badges.length; i++) {
      var b = badges[i];
      html += '<div style="text-align:center;width:64px">'
        + '<div style="font-size:28px">' + SS.utils.esc(b.badge_icon || '🏅') + '</div>'
        + '<div class="text-xs font-medium mt-1">' + SS.utils.esc(b.badge_name || '') + '</div>'
        + '</div>';
    }
    html += '</div>';
    return html;
  },

  checkin: function() {
    SS.api.post('/gamification.php?action=checkin', {}).then(function(d) {
      var data = d.data || {};
      SS.ui.toast(data.message || '+5 XP!', 'success');
      // Reload gamification card if exists
      if (document.getElementById('ss-xp-card')) {
        SS.Gamification.loadProfile(SS.store.userId(), 'ss-xp-card');
      }
    });
  },

  loadProfile: function(userId, containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    SS.api.get('/gamification.php?action=profile&user_id=' + userId).then(function(d) {
      el.innerHTML = SS.Gamification.renderXPCard(d.data);
    }).catch(function() {
      el.innerHTML = '';
    });
  },

  loadLeaderboard: function(containerId, period) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '<div class="flex justify-center p-4"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%"></div></div>';
    SS.api.get('/gamification.php?action=leaders&period=' + (period || 'all')).then(function(d) {
      var data = d.data || {};
      el.innerHTML = SS.Gamification.renderLeaderboard(data.leaders, data.my_rank);
    }).catch(function() {
      el.innerHTML = '<div class="text-center text-muted p-4">Lỗi tải bảng xếp hạng</div>';
    });
  }
};
