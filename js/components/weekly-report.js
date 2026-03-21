/**
 * ShipperShop Component — Weekly Report
 * Visual weekly summary with stats comparison and highlights
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.WeeklyReport = {

  show: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    SS.api.get('/weekly-report.php').then(function(d) {
      var data = d.data || {};
      var tw = data.this_week || {};
      var changes = data.changes || {};

      var arrow = function(val) {
        if (val > 0) return '<span style="color:var(--success)">+' + val + '%↑</span>';
        if (val < 0) return '<span style="color:var(--danger)">' + val + '%↓</span>';
        return '<span class="text-muted">0%</span>';
      };

      var html = '<div class="card mb-3" style="background:linear-gradient(135deg,var(--primary),#a855f7);color:#fff;padding:16px;border-radius:12px;text-align:center">'
        + '<div style="font-size:13px;opacity:0.9">Tuan ' + SS.utils.esc(data.week_start || '') + ' - ' + SS.utils.esc(data.week_end || '') + '</div>'
        + '<div style="font-size:24px;font-weight:800;margin:8px 0">Bao cao hang tuan</div></div>';

      // Stats grid
      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px">';
      var items = [
        {label: 'Bai viet', value: tw.posts || 0, change: changes.posts, icon: '📝'},
        {label: 'Luot thich', value: tw.likes || 0, change: changes.likes, icon: '❤️'},
        {label: 'Ghi chu', value: tw.comments || 0, change: changes.comments, icon: '💬'},
        {label: 'Nguoi theo doi moi', value: tw.new_followers || 0, change: null, icon: '👥'},
      ];
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        html += '<div class="card" style="padding:12px;text-align:center">'
          + '<div style="font-size:18px">' + it.icon + '</div>'
          + '<div style="font-size:22px;font-weight:800;color:var(--primary)">' + it.value + '</div>'
          + '<div class="text-xs text-muted">' + it.label + '</div>';
        if (it.change !== null && it.change !== undefined) html += '<div class="text-xs" style="margin-top:2px">' + arrow(it.change) + '</div>';
        html += '</div>';
      }
      html += '</div>';

      // Top post
      if (data.top_post) {
        html += '<div class="text-sm font-bold mb-2">Bai noi bat nhat</div>'
          + '<div class="card mb-3" style="padding:10px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + data.top_post.id + '\'">'
          + '<div class="text-sm">' + SS.utils.esc((data.top_post.content || '').substring(0, 100)) + '</div>'
          + '<div class="text-xs text-muted mt-1">❤️ ' + (data.top_post.likes_count || 0) + ' 💬 ' + (data.top_post.comments_count || 0) + '</div></div>';
      }

      // Streak
      if (data.streak) {
        html += '<div class="flex gap-3 text-center">'
          + '<div class="card flex-1" style="padding:10px"><div style="font-size:18px">🔥</div><div class="font-bold">' + (data.streak.current || 0) + ' ngay</div><div class="text-xs text-muted">Streak hien tai</div></div>'
          + '<div class="card flex-1" style="padding:10px"><div style="font-size:18px">🏆</div><div class="font-bold">' + (data.streak.longest || 0) + ' ngay</div><div class="text-xs text-muted">Ky luc</div></div></div>';
      }

      SS.ui.sheet({title: 'Bao cao tuan', html: html});
    });
  }
};
