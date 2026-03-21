/**
 * ShipperShop Component — Content Insights
 * Weekly summary with tips, trends, best post
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.Insights = {

  render: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/insights.php').then(function(d) {
      var data = d.data;
      if (!data) { el.innerHTML = ''; return; }

      var tw = data.this_week || {};
      var ch = data.changes || {};
      var fol = data.followers || {};

      var arrow = function(val) {
        if (val > 0) return '<span style="color:var(--success)">↑' + val + '</span>';
        if (val < 0) return '<span style="color:var(--danger)">↓' + Math.abs(val) + '</span>';
        return '<span style="color:var(--text-muted)">—</span>';
      };

      var html = '<div class="card mb-3"><div class="card-header flex justify-between items-center">Tuần này <span class="text-xs text-muted">vs tuần trước</span></div><div class="card-body">'
        + '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;text-align:center;margin-bottom:12px">'
        + '<div><div style="font-size:18px;font-weight:800;color:var(--primary)">' + (tw.posts || 0) + '</div><div class="text-xs text-muted">Bài ' + arrow(ch.posts) + '</div></div>'
        + '<div><div style="font-size:18px;font-weight:800;color:var(--success)">' + SS.utils.fN(tw.likes || 0) + '</div><div class="text-xs text-muted">TC ' + arrow(ch.likes) + '</div></div>'
        + '<div><div style="font-size:18px;font-weight:800;color:var(--info)">' + SS.utils.fN(tw.comments || 0) + '</div><div class="text-xs text-muted">GC</div></div>'
        + '<div><div style="font-size:18px;font-weight:800;color:var(--warning)">' + (fol.new || 0) + '</div><div class="text-xs text-muted">Follower mới</div></div>'
        + '</div>';

      // Best post
      if (data.best_post) {
        var bp = data.best_post;
        html += '<a href="/post-detail.html?id=' + bp.id + '" style="display:block;padding:8px 12px;background:var(--bg);border-radius:8px;text-decoration:none;color:var(--text);margin-bottom:8px">'
          + '<div class="text-xs text-muted mb-1">🏆 Bài nổi bật nhất</div>'
          + '<div class="text-sm truncate">' + SS.utils.esc((bp.content || '').substring(0, 80)) + '</div>'
          + '<div class="text-xs text-muted mt-1">' + SS.utils.fN(bp.likes_count) + ' TC · ' + SS.utils.fN(bp.comments_count) + ' GC</div>'
          + '</a>';
      }

      // Tips
      if (data.tips && data.tips.length) {
        for (var i = 0; i < data.tips.length; i++) {
          var tip = data.tips[i];
          html += '<div style="display:flex;align-items:flex-start;gap:8px;padding:6px 0"><span style="font-size:16px">' + tip.icon + '</span><div class="text-sm" style="color:var(--text-secondary);line-height:1.5">' + SS.utils.esc(tip.text) + '</div></div>';
        }
      }

      html += '</div></div>';
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = ''; });
  },

  // Open full insights sheet
  openFull: function() {
    SS.Insights.render('__insights_temp');
    // Also show trend
    SS.api.get('/insights.php?action=engagement_trend&weeks=8').then(function(d) {
      var trend = d.data || [];
      var html = '<div class="text-sm font-bold mb-2">Xu hướng 8 tuần</div>';
      for (var i = 0; i < trend.length; i++) {
        var w = trend[i];
        var maxLikes = Math.max.apply(null, trend.map(function(t) { return t.likes; })) || 1;
        var pct = Math.round(w.likes / maxLikes * 100);
        html += '<div class="flex items-center gap-2 mb-1"><span class="text-xs text-muted" style="width:55px">' + w.week_start.substring(5) + '</span>'
          + '<div style="flex:1;height:8px;background:var(--border);border-radius:4px;overflow:hidden"><div style="height:100%;width:' + pct + '%;background:var(--primary);border-radius:4px"></div></div>'
          + '<span class="text-xs" style="width:35px;text-align:right">' + SS.utils.fN(w.likes) + '</span></div>';
      }
      SS.ui.sheet({title: 'Xu hướng tương tác', html: html});
    });
  }
};
