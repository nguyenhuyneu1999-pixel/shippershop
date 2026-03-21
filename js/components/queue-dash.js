/**
 * ShipperShop Component — Queue Dashboard
 * Shows scheduled/draft/published/failed content stats
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.QueueDash = {

  show: function() {
    SS.api.get('/queue-dashboard.php').then(function(d) {
      var data = d.data || {};
      var counts = data.counts || {};

      // Stats grid
      var html = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px">';
      var items = [
        {label: 'Đang chờ', count: counts.scheduled || 0, color: 'var(--primary)', icon: '⏰'},
        {label: 'Bản nháp', count: counts.drafts || 0, color: 'var(--warning)', icon: '📝'},
        {label: 'Đã đăng', count: counts.published || 0, color: 'var(--success)', icon: '✅'},
        {label: 'Lỗi', count: counts.failed || 0, color: 'var(--danger)', icon: '❌'},
      ];
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        html += '<div class="card" style="text-align:center;padding:10px">'
          + '<div style="font-size:16px">' + it.icon + '</div>'
          + '<div style="font-size:20px;font-weight:800;color:' + it.color + '">' + it.count + '</div>'
          + '<div class="text-xs text-muted">' + it.label + '</div></div>';
      }
      html += '</div>';

      // Next scheduled
      var next = data.next_scheduled;
      if (next) {
        html += '<div class="card mb-3" style="padding:10px 14px;border-left:3px solid var(--primary)">'
          + '<div class="text-xs text-muted">Bài tiếp theo</div>'
          + '<div class="text-sm font-medium mt-1">' + SS.utils.esc((next.content || '').substring(0, 80)) + '</div>'
          + '<div class="text-xs" style="color:var(--primary);margin-top:4px">⏰ ' + SS.utils.ago(next.scheduled_at) + '</div></div>';
      }

      // Recent activity
      var recent = data.recent || [];
      if (recent.length) {
        html += '<div class="text-sm font-bold mb-2">Gần đây</div>';
        for (var j = 0; j < recent.length; j++) {
          var r = recent[j];
          var statusColors = {scheduled: 'var(--primary)', draft: 'var(--warning)', published: 'var(--success)', failed: 'var(--danger)'};
          var statusLabels = {scheduled: 'Đang chờ', draft: 'Nháp', published: 'Đã đăng', failed: 'Lỗi'};
          html += '<div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border-light)">'
            + '<div style="width:8px;height:8px;border-radius:50%;background:' + (statusColors[r.status] || '#999') + ';flex-shrink:0"></div>'
            + '<div style="flex:1;min-width:0">'
            + '<div class="text-sm" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + SS.utils.esc((r.content || '').substring(0, 60)) + '</div>'
            + '<div class="text-xs text-muted">' + (statusLabels[r.status] || r.status) + ' · ' + SS.utils.ago(r.created_at) + '</div>'
            + '</div></div>';
        }
      }

      SS.ui.sheet({title: 'Lịch nội dung', html: html});
    });
  }
};
