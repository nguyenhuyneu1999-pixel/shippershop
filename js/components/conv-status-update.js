/**
 * ShipperShop Component — Conversation Status Update
 */
window.SS = window.SS || {};

SS.ConvStatusUpdate = {
  show: function(conversationId) {
    SS.api.get('/conv-status-update.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var updates = data.updates || [];
      var statuses = data.statuses || [];

      // Quick status buttons
      var html = '<div class="flex gap-2 flex-wrap mb-3">';
      for (var s = 0; s < statuses.length; s++) {
        html += '<button class="chip" style="cursor:pointer" onclick="SS.ConvStatusUpdate.send(' + conversationId + ',\'' + statuses[s].id + '\')">' + statuses[s].icon + ' ' + SS.utils.esc(statuses[s].name) + '</button>';
      }
      html += '</div>';

      // Timeline
      if (!updates.length) html += '<div class="empty-state p-3"><div class="empty-icon">📦</div><div class="empty-text">Chua co cap nhat</div></div>';
      for (var i = 0; i < Math.min(updates.length, 15); i++) {
        var u = updates[i];
        var st = null;
        for (var j = 0; j < statuses.length; j++) { if (statuses[j].id === u.status) { st = statuses[j]; break; } }
        html += '<div class="flex gap-2 p-2" style="border-left:3px solid ' + ((st && st.color) || '#999') + ';margin-left:8px">'
          + '<div class="flex-1"><div class="text-sm">' + (st ? st.icon + ' ' + SS.utils.esc(st.name) : SS.utils.esc(u.status)) + '</div>'
          + (u.note ? '<div class="text-xs">' + SS.utils.esc(u.note) + '</div>' : '')
          + '<div class="text-xs text-muted">' + SS.utils.esc(u.fullname || '') + ' · ' + SS.utils.ago(u.created_at) + '</div></div></div>';
      }
      SS.ui.sheet({title: '📦 Trang thai giao hang (' + updates.length + ')', html: html});
    });
  },
  send: function(convId, statusId) {
    SS.api.post('/conv-status-update.php', {conversation_id: convId, status: statusId}).then(function(d) {
      SS.ui.toast(d.message, 'success');
      SS.ConvStatusUpdate.show(convId);
    });
  }
};
