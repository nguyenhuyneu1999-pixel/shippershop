/**
 * ShipperShop Component — User Compare
 * Side-by-side comparison of two shipper profiles
 */
window.SS = window.SS || {};

SS.UserCompare = {
  open: function(user1, user2) {
    if (!user1 || !user2) {
      SS.ui.modal({
        title: 'So sanh Shipper',
        html: '<div class="form-group"><label class="form-label">Shipper 1 (ID)</label><input id="uc-u1" class="form-input" type="number" placeholder="VD: 2"></div>'
          + '<div class="form-group"><label class="form-label">Shipper 2 (ID)</label><input id="uc-u2" class="form-input" type="number" placeholder="VD: 3"></div>',
        confirmText: 'So sanh',
        onConfirm: function() {
          var u1 = document.getElementById('uc-u1').value;
          var u2 = document.getElementById('uc-u2').value;
          if (u1 && u2) SS.UserCompare.open(u1, u2);
        }
      });
      return;
    }

    SS.api.get('/user-compare.php?user1=' + user1 + '&user2=' + user2).then(function(d) {
      var data = d.data || {};
      var s1 = data.user1 || {};
      var s2 = data.user2 || {};
      var comp = data.comparison || {};
      var wins = data.wins || {};

      var u1 = s1.user || {};
      var u2 = s2.user || {};

      // Header
      var html = '<div class="flex items-center justify-between mb-3">'
        + '<div class="text-center" style="width:40%"><img src="' + (u1.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-md" loading="lazy"><div class="font-bold text-sm mt-1">' + SS.utils.esc(u1.fullname || '') + '</div><div class="text-xs text-muted">' + SS.utils.esc(u1.company || '') + '</div></div>'
        + '<div class="text-center" style="width:20%"><div style="font-size:24px">⚔️</div><div class="text-xs font-bold" style="color:var(--primary)">' + (wins.user1 || 0) + ' - ' + (wins.user2 || 0) + '</div></div>'
        + '<div class="text-center" style="width:40%"><img src="' + (u2.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-md" loading="lazy"><div class="font-bold text-sm mt-1">' + SS.utils.esc(u2.fullname || '') + '</div><div class="text-xs text-muted">' + SS.utils.esc(u2.company || '') + '</div></div></div>';

      // Comparison rows
      var labels = {posts:'Bai viet',deliveries:'Don giao',comments:'Ghi chu',followers:'Nguoi theo doi',following:'Dang theo doi',groups:'Nhom',xp:'XP',streak:'Streak',avg_posts_per_day:'TB/ngay'};
      var keys = Object.keys(labels);
      for (var i = 0; i < keys.length; i++) {
        var k = keys[i];
        var c = comp[k] || {};
        var v1 = c.user1 || 0;
        var v2 = c.user2 || 0;
        var maxV = Math.max(v1, v2, 1);
        var w1 = Math.round(v1 / maxV * 100);
        var w2 = Math.round(v2 / maxV * 100);
        var c1 = c.winner === 'user1' ? 'var(--success)' : 'var(--border)';
        var c2 = c.winner === 'user2' ? 'var(--success)' : 'var(--border)';

        html += '<div style="margin-bottom:8px"><div class="flex justify-between text-xs text-muted mb-1"><span>' + v1 + '</span><span class="font-bold">' + labels[k] + '</span><span>' + v2 + '</span></div>'
          + '<div class="flex gap-1" style="height:6px"><div style="flex:1;display:flex;justify-content:flex-end"><div style="width:' + w1 + '%;background:' + c1 + ';border-radius:3px;min-width:4px"></div></div>'
          + '<div style="flex:1"><div style="width:' + w2 + '%;background:' + c2 + ';border-radius:3px;min-width:4px"></div></div></div></div>';
      }

      // Winner
      var winner = wins.user1 > wins.user2 ? u1.fullname : (wins.user2 > wins.user1 ? u2.fullname : 'Hoa');
      html += '<div class="text-center mt-3 p-2" style="background:var(--primary-light);border-radius:8px"><span style="font-size:16px">🏆</span> <span class="font-bold text-sm" style="color:var(--primary)">' + SS.utils.esc(winner) + '</span></div>';

      SS.ui.sheet({title: 'So sanh Shipper', html: html});
    });
  }
};
