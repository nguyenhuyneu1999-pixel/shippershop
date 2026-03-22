/**
 * ShipperShop Component — Conversation Meeting Scheduler
 */
window.SS = window.SS || {};

SS.ConvMeeting = {
  show: function(conversationId) {
    SS.api.get('/conv-meeting.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var meetings = data.meetings || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvMeeting.create(' + conversationId + ')"><i class="fa-solid fa-calendar-plus"></i> Hen gap</button>';
      if (!meetings.length) { html += '<div class="empty-state p-3"><div class="empty-icon">📅</div><div class="empty-text">Chua co cuoc hen</div></div>'; }
      for (var i = 0; i < meetings.length; i++) {
        var m = meetings[i];
        var isPast = m.is_past;
        var isToday = m.is_today;
        var cancelled = m.status === 'cancelled';
        html += '<div class="card mb-2" style="padding:10px' + (isToday ? ';border-left:3px solid var(--warning)' : '') + (cancelled ? ';opacity:0.5;text-decoration:line-through' : '') + (isPast && !cancelled ? ';opacity:0.6' : '') + '">'
          + '<div class="flex justify-between"><div class="font-bold text-sm">' + SS.utils.esc(m.title) + (isToday ? ' 📌' : '') + '</div>'
          + (!cancelled && !isPast ? '<button class="btn btn-ghost btn-sm" onclick="SS.ConvMeeting.cancel(' + conversationId + ',' + m.id + ')"><i class="fa-solid fa-xmark text-danger" style="font-size:11px"></i></button>' : '') + '</div>'
          + '<div class="text-xs text-muted">📅 ' + SS.utils.esc(m.datetime || '') + (m.location ? ' · 📍 ' + SS.utils.esc(m.location) : '') + '</div>'
          + (m.note ? '<div class="text-xs mt-1">' + SS.utils.esc(m.note) + '</div>' : '')
          + '<div class="text-xs text-muted mt-1">Boi ' + SS.utils.esc(m.creator_name || '') + '</div></div>';
      }
      SS.ui.sheet({title: '📅 Cuoc hen (' + meetings.length + ')', html: html});
    });
  },
  create: function(conversationId) {
    SS.ui.modal({title: 'Tao cuoc hen', html: '<input id="cm-title" class="form-input mb-2" placeholder="Tieu de (VD: Giao hang Q7)">'
      + '<input id="cm-datetime" class="form-input mb-2" type="datetime-local">'
      + '<input id="cm-location" class="form-input mb-2" placeholder="Dia diem (tuy chon)">'
      + '<input id="cm-note" class="form-input" placeholder="Ghi chu">', confirmText: 'Tao',
      onConfirm: function() {
        SS.api.post('/conv-meeting.php', {conversation_id: conversationId, title: document.getElementById('cm-title').value, datetime: document.getElementById('cm-datetime').value.replace('T', ' '), location: document.getElementById('cm-location').value, note: document.getElementById('cm-note').value}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvMeeting.show(conversationId); });
      }
    });
  },
  cancel: function(convId, meetId) { SS.api.post('/conv-meeting.php?action=cancel', {conversation_id: convId, meeting_id: meetId}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvMeeting.show(convId); }); }
};
