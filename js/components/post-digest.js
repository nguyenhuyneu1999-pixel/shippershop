/**
 * ShipperShop Component — Post Digest
 * Curated daily/weekly best content view
 */
window.SS = window.SS || {};

SS.PostDigest = {
  show: function(period) {
    period = period || 'daily';
    SS.api.get('/post-digest.php?period=' + period).then(function(d) {
      var data = d.data || {};
      var stats = data.stats || {};

      var html = '<div class="flex gap-2 mb-3">'
        + '<div class="chip ' + (period === 'daily' ? 'chip-active' : '') + '" onclick="SS.PostDigest.show(\'daily\')" style="cursor:pointer">Hom nay</div>'
        + '<div class="chip ' + (period === 'weekly' ? 'chip-active' : '') + '" onclick="SS.PostDigest.show(\'weekly\')" style="cursor:pointer">Tuan nay</div></div>';

      // Stats
      html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:16px;text-align:center;font-size:12px">'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--primary)">' + (stats.posts || 0) + '</div><div class="text-muted">Bai</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--success)">' + (stats.comments || 0) + '</div><div class="text-muted">Ghi chu</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold" style="color:var(--warning)">' + (stats.likes || 0) + '</div><div class="text-muted">Likes</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (stats.new_users || 0) + '</div><div class="text-muted">User moi</div></div></div>';

      // Top posts
      var top = data.top_posts || [];
      if (top.length) {
        html += '<div class="text-sm font-bold mb-2">🏆 Bai viet hay nhat</div>';
        for (var i = 0; i < top.length; i++) {
          var p = top[i];
          html += '<div class="card mb-2" style="padding:10px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'">'
            + '<div class="flex items-center gap-2 mb-1"><img src="' + (p.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy"><span class="text-xs font-medium">' + SS.utils.esc(p.fullname) + '</span><span class="text-xs text-muted">' + SS.utils.esc(p.shipping_company || '') + '</span></div>'
            + '<div class="text-sm">' + SS.utils.esc((p.content || '').substring(0, 100)) + '</div>'
            + '<div class="text-xs text-muted mt-1">❤️ ' + (p.likes_count || 0) + ' 💬 ' + (p.comments_count || 0) + '</div></div>';
        }
      }

      SS.ui.sheet({title: (period === 'daily' ? 'Tin noi bat hom nay' : 'Tin noi bat tuan nay'), html: html});
    });
  }
};
