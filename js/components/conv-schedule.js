/**
 * ShipperShop Component — Conversation Schedule
 * Schedule messages to be sent later
 */
window.SS = window.SS || {};

SS.ConvSchedule = {
  show: function() {
    SS.api.get('/conv-schedule.php').then(function(d) {
      var data = d.data || {};
      var msgs = data.messages || [];
      if (!msgs.length) {
        SS.ui.sheet({title: 'Tin nhan hen gio', html: '<div class="empty-state p-4"><div class="empty-icon">⏰</div><div class="empty-text">Chua co tin nhan hen gio</div></div>'});
        return;
      }
      var html = '<div class="text-sm text-muted mb-2">' + (data.due || 0) + ' tin sap gui</div>';
      for (var i = 0; i < msgs.length; i++) {
        var m = msgs[i];
        var isDue = m.is_due;
        html += '<div class="card mb-2" style="padding:10px' + (isDue ? ';border-left:3px solid var(--warning)' : '') + '">'
          + '<div class="flex justify-between items-start"><div class="flex-1"><div class="text-sm">' + SS.utils.esc((m.content || '').substring(0, 80)) + '</div>'
          + '<div class="text-xs text-muted mt-1">' + (m.to ? 'Gui cho: ' + SS.utils.esc(m.to) + ' · ' : '') + SS.utils.ago(m.send_at) + (isDue ? ' <span style="color:var(--warning)">⏰ Den gio!</span>' : '') + '</div></div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.ConvSchedule.cancel(' + m.id + ')"><i class="fa-solid fa-xmark text-danger"></i></button></div></div>';
      }
      SS.ui.sheet({title: 'Hen gio gui (' + msgs.length + ')', html: html});
    });
  },

  schedule: function(conversationId) {
    SS.ui.modal({
      title: 'Hen gio gui tin nhan',
      html: '<textarea id="cs-content" class="form-textarea mb-2" rows="3" placeholder="Noi dung tin nhan..."></textarea>'
        + '<div class="form-group"><label class="form-label">Gui luc</label>'
        + '<input id="cs-time" class="form-input" type="datetime-local"></div>',
      confirmText: 'Hen gio',
      onConfirm: function() {
        SS.api.post('/conv-schedule.php', {
          conversation_id: conversationId,
          content: document.getElementById('cs-content').value,
          send_at: document.getElementById('cs-time').value.replace('T', ' ')
        }).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); });
      }
    });
  },

  cancel: function(msgId) {
    SS.api.post('/conv-schedule.php?action=cancel', {message_id: msgId}).then(function() {
      SS.ui.toast('Da huy', 'success'); SS.ConvSchedule.show();
    });
  }
};
