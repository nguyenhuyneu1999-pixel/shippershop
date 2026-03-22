/**
 * ShipperShop Component — User Summary Card
 * Hover/popup user preview
 */
window.SS = window.SS || {};

SS.UserSummaryCard = {
  show: function(userId) {
    SS.api.get('/user-summary-card.php?user_id=' + userId).then(function(d) {
      var u = d.data;
      if (!u) return;
      var html = '<div class="text-center">'
        + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:56px;height:56px;border-radius:50%;object-fit:cover" loading="lazy">'
        + '<div class="font-bold mt-2">' + SS.utils.esc(u.fullname) + (u.verified ? ' ✓' : '') + (u.online ? ' 🟢' : '') + '</div>'
        + '<div class="text-xs text-muted">' + SS.utils.esc(u.company || '') + ' · Lv.' + u.level + ' · ' + u.days + ' ngay</div>'
        + (u.bio ? '<div class="text-xs mt-1">' + SS.utils.esc(u.bio) + '</div>' : '') + '</div>';

      html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px;margin-top:12px;text-align:center;font-size:11px">';
      var stats = [{v: u.posts, l: 'Bai'}, {v: u.deliveries, l: 'Don'}, {v: u.followers, l: 'Follow'}, {v: u.badges, l: 'Badge'}];
      for (var i = 0; i < stats.length; i++) {
        html += '<div><div class="font-bold" style="color:var(--primary)">' + SS.utils.fN(stats[i].v || 0) + '</div><div class="text-muted">' + stats[i].l + '</div></div>';
      }
      html += '</div>';

      html += '<div class="flex gap-2 mt-3 justify-center">'
        + '<button class="btn btn-primary btn-sm" onclick="window.location.href=\'/user.html?id=' + u.id + '\'">Xem trang</button>'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.UserPortfolio.show(' + u.id + ')">Portfolio</button></div>';

      SS.ui.sheet({title: '', html: html});
    });
  }
};
