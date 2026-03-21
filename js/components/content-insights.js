/**
 * ShipperShop Component — Content Insights
 * Author-facing analytics: top posts, best times, engagement trends
 * Uses: SS.api, SS.ui, SS.Charts
 */
window.SS = window.SS || {};

SS.ContentInsights = {

  show: function(days) {
    days = days || 30;
    SS.api.get('/content-insights.php?days=' + days).then(function(d) {
      var data = d.data || {};
      var totals = data.totals || {};

      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center;margin-bottom:16px">'
        + '<div class="card" style="padding:12px"><div style="font-size:22px;font-weight:800;color:var(--primary)">' + SS.utils.fN(totals.posts || 0) + '</div><div class="text-xs text-muted">Bài viết</div></div>'
        + '<div class="card" style="padding:12px"><div style="font-size:22px;font-weight:800;color:var(--success)">' + SS.utils.fN(totals.likes || 0) + '</div><div class="text-xs text-muted">Lượt thích</div></div>'
        + '<div class="card" style="padding:12px"><div style="font-size:22px;font-weight:800;color:var(--info)">' + SS.utils.fN(totals.comments || 0) + '</div><div class="text-xs text-muted">Ghi chú</div></div>'
        + '</div>';

      // Recommendation
      if (data.recommendation) {
        html += '<div class="card mb-3" style="padding:10px 14px;border-left:3px solid var(--primary);display:flex;align-items:center;gap:8px">'
          + '<span style="font-size:18px">💡</span>'
          + '<div class="text-sm">' + SS.utils.esc(data.recommendation) + '</div></div>';
      }

      // Best hours
      var hours = data.best_hours || [];
      if (hours.length) {
        html += '<div class="text-sm font-bold mb-2">Giờ vàng đăng bài</div><div class="flex gap-2 mb-3" style="overflow-x:auto">';
        for (var h = 0; h < Math.min(hours.length, 4); h++) {
          var hr = hours[h];
          html += '<div class="card text-center" style="padding:8px 12px;min-width:60px">'
            + '<div class="font-bold text-sm">' + String(hr.hour).padStart(2, '0') + ':00</div>'
            + '<div class="text-xs text-muted">' + Math.round(parseFloat(hr.avg_likes || 0)) + ' likes/bài</div></div>';
        }
        html += '</div>';
      }

      // Best days
      var bestDays = data.best_days || [];
      if (bestDays.length) {
        html += '<div class="text-sm font-bold mb-2">Ngày tốt nhất</div><div class="flex gap-2 mb-3" style="overflow-x:auto">';
        for (var bd = 0; bd < bestDays.length; bd++) {
          var dayItem = bestDays[bd];
          html += '<div class="chip">' + (dayItem.day_name || '') + ' · ' + Math.round(parseFloat(dayItem.avg_likes || 0)) + ' likes</div>';
        }
        html += '</div>';
      }

      // Top posts
      var top = data.top_posts || [];
      if (top.length) {
        html += '<div class="text-sm font-bold mb-2">Bài nổi bật</div>';
        for (var t = 0; t < top.length; t++) {
          var p = top[t];
          html += '<div class="card mb-2" style="padding:10px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'">'
            + '<div class="text-sm" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + SS.utils.esc((p.content || '').substring(0, 80)) + '</div>'
            + '<div class="flex gap-3 text-xs text-muted mt-1">'
            + '<span>❤️ ' + (p.likes_count || 0) + '</span>'
            + '<span>💬 ' + (p.comments_count || 0) + '</span>'
            + '<span>' + SS.utils.ago(p.created_at) + '</span></div></div>';
        }
      }

      // Type breakdown
      var types = data.type_breakdown || [];
      if (types.length > 1) {
        html += '<div class="text-sm font-bold mb-2 mt-3">Loại nội dung</div>';
        for (var tb = 0; tb < types.length; tb++) {
          var tp = types[tb];
          html += '<div class="flex justify-between text-xs mb-1"><span>' + SS.utils.esc(tp.type) + '</span><span class="font-bold">' + tp.count + ' bài · ' + Math.round(parseFloat(tp.avg_likes || 0)) + ' avg likes</span></div>';
        }
      }

      SS.ui.sheet({title: 'Phân tích nội dung (' + days + ' ngày)', html: html});
    });
  }
};
