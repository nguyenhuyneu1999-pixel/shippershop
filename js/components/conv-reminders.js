/**
 * ShipperShop Component — Conversation Reminders
 * Set/view follow-up reminders on conversations
 */
window.SS = window.SS || {};

SS.ConvReminders = {
  show: function() {
    SS.api.get('/conv-reminders.php').then(function(d) {
      var data = d.data || {};
      var reminders = data.reminders || [];

      if (!reminders.length) {
        SS.ui.sheet({title: 'Nhac nho', html: '<div class="empty-state p-4"><div class="empty-icon">⏰</div><div class="empty-text">Chua co nhac nho</div></div>'});
        return;
      }

      var html = '<div class="text-sm text-muted mb-2">' + (data.due || 0) + ' nhac nho den han</div>';
      for (var i = 0; i < reminders.length; i++) {
        var r = reminders[i];
        var isDue = r.is_due;
        var other = r.other_user || {};
        html += '<div class="card mb-2" style="padding:10px' + (isDue ? ';border-left:3px solid var(--warning)' : '') + (r.done ? ';opacity:0.5' : '') + '">'
          + '<div class="flex items-center gap-2"><img src="' + (other.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><div class="text-sm font-medium">' + SS.utils.esc(other.fullname || 'Cuoc tro chuyen') + '</div>'
          + '<div class="text-xs text-muted">' + (r.note ? SS.utils.esc(r.note) + ' · ' : '') + SS.utils.ago(r.remind_at) + (isDue ? ' <span style="color:var(--warning)">⏰ Den han!</span>' : '') + '</div></div>'
          + '<div class="flex gap-1">';
        if (!r.done) html += '<button class="btn btn-ghost btn-sm" onclick="SS.ConvReminders._done(' + r.id + ')"><i class="fa-solid fa-check text-success"></i></button>';
        html += '<button class="btn btn-ghost btn-sm" onclick="SS.ConvReminders._del(' + r.id + ')"><i class="fa-solid fa-trash text-danger" style="font-size:11px"></i></button></div></div>';
      }
      SS.ui.sheet({title: 'Nhac nho (' + reminders.length + ')', html: html});
    });
  },

  add: function(conversationId) {
    SS.ui.modal({
      title: 'Dat nhac nho',
      html: '<div class="form-group"><label class="form-label">Ghi chu</label><input id="cr-note" class="form-input" placeholder="VD: Hoi lai ve don hang"></div>'
        + '<div class="form-group"><label class="form-label">Nhac luc</label><select id="cr-time" class="form-select">'
        + '<option value="1">1 gio sau</option><option value="3">3 gio sau</option><option value="24" selected>Ngay mai</option><option value="72">3 ngay sau</option><option value="168">1 tuan sau</option></select></div>',
      confirmText: 'Dat nhac nho',
      onConfirm: function() {
        var hours = parseInt(document.getElementById('cr-time').value) || 24;
        var remindAt = new Date(Date.now() + hours * 3600000).toISOString().replace('T', ' ').substring(0, 19);
        SS.api.post('/conv-reminders.php', {
          conversation_id: conversationId,
          note: document.getElementById('cr-note').value,
          remind_at: remindAt
        }).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); });
      }
    });
  },

  _done: function(id) { SS.api.post('/conv-reminders.php?action=done', {reminder_id: id}).then(function() { SS.ConvReminders.show(); }); },
  _del: function(id) { SS.api.post('/conv-reminders.php?action=delete', {reminder_id: id}).then(function() { SS.ConvReminders.show(); }); }
};
