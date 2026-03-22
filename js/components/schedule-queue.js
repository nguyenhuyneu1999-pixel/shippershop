/**
 * ShipperShop Component — Schedule Queue
 */
window.SS = window.SS || {};

SS.ScheduleQueue = {
  show: function() {
    SS.api.get('/schedule-queue.php').then(function(d) {
      var data = d.data || {};
      var items = data.items || [];
      var counts = data.counts || {};

      var html = '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:6px;flex:1"><div class="font-bold" style="color:var(--primary)">' + (counts.scheduled || 0) + '</div><div class="text-xs text-muted">Hen gio</div></div>'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold">' + (counts.draft || 0) + '</div><div class="text-xs text-muted">Nhap</div></div>'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold" style="color:var(--success)">' + (counts.published || 0) + '</div><div class="text-xs text-muted">Da dang</div></div>'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold" style="color:var(--danger)">' + (counts.failed || 0) + '</div><div class="text-xs text-muted">Loi</div></div></div>';

      if (!items.length) html += '<div class="empty-state p-3"><div class="empty-text">Khong co noi dung hen gio</div></div>';
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        var statusColors = {scheduled: 'var(--primary)', draft: 'var(--text-muted)', failed: 'var(--danger)'};
        var statusLabels = {scheduled: '⏰ Hen gio', draft: '📝 Nhap', failed: '❌ Loi'};
        html += '<div class="card mb-2" style="padding:10px;border-left:3px solid ' + (statusColors[it.status] || '#999') + '">'
          + '<div class="text-sm">' + SS.utils.esc((it.content || '').substring(0, 80)) + '</div>'
          + '<div class="flex justify-between items-center mt-1"><span class="text-xs">' + (statusLabels[it.status] || it.status) + ' · ' + SS.utils.ago(it.scheduled_at || it.created_at) + '</span>'
          + '<div class="flex gap-1">';
        if (it.status === 'scheduled') html += '<button class="btn btn-ghost btn-sm" onclick="SS.ScheduleQueue.cancel(' + it.id + ')">Huy</button>';
        if (it.status === 'failed') html += '<button class="btn btn-ghost btn-sm" onclick="SS.ScheduleQueue.retry(' + it.id + ')">Thu lai</button>';
        html += '</div></div></div>';
      }
      SS.ui.sheet({title: 'Hang doi (' + items.length + ')', html: html});
    });
  },
  cancel: function(id) { SS.api.post('/schedule-queue.php?action=cancel', {item_id: id}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ScheduleQueue.show(); }); },
  retry: function(id) { SS.api.post('/schedule-queue.php?action=retry', {item_id: id}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ScheduleQueue.show(); }); }
};
