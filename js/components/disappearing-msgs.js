/**
 * ShipperShop Component — Disappearing Messages
 */
window.SS = window.SS || {};

SS.DisappearingMsgs = {
  show: function(conversationId) {
    SS.api.get('/disappearing-msgs.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var s = data.settings || {};
      var msgs = data.messages || [];

      var html = '<div class="card mb-3" style="padding:12px"><div class="flex justify-between items-center"><span class="text-sm font-bold">⏱️ Tin tu huy</span>'
        + '<div style="width:40px;height:22px;border-radius:11px;background:' + (s.enabled ? 'var(--primary)' : 'var(--border)') + ';cursor:pointer;position:relative" onclick="SS.DisappearingMsgs.toggle(' + conversationId + ')">'
        + '<div style="width:18px;height:18px;border-radius:50%;background:#fff;position:absolute;top:2px;' + (s.enabled ? 'right:2px' : 'left:2px') + '"></div></div></div>'
        + '<div class="text-xs text-muted">Tin nhan tu dong xoa sau ' + (s.ttl_hours || 24) + ' gio</div></div>';

      if (msgs.length) {
        html += '<div class="text-sm font-bold mb-2">' + msgs.length + ' tin dang hoat dong</div>';
        for (var i = 0; i < msgs.length; i++) {
          var m = msgs[i];
          html += '<div class="card mb-1" style="padding:8px;opacity:0.8"><div class="text-sm">' + SS.utils.esc((m.content || '').substring(0, 60)) + '</div><div class="text-xs text-muted">Het han: ' + SS.utils.ago(m.expires_at) + '</div></div>';
        }
      }

      html += '<div class="mt-3"><button class="btn btn-primary btn-sm" onclick="SS.DisappearingMsgs.send(' + conversationId + ')"><i class="fa-solid fa-ghost"></i> Gui tin tu huy</button></div>';
      SS.ui.sheet({title: '👻 Tin tu huy', html: html});
    });
  },
  toggle: function(convId) {
    SS.api.get('/disappearing-msgs.php?conversation_id=' + convId).then(function(d) {
      var s = (d.data || {}).settings || {};
      SS.api.post('/disappearing-msgs.php', {conversation_id: convId, enabled: !s.enabled, ttl_hours: s.ttl_hours || 24}).then(function() { SS.DisappearingMsgs.show(convId); });
    });
  },
  send: function(convId) {
    SS.ui.closeSheet();
    SS.ui.modal({title: 'Tin tu huy', html: '<textarea id="dm-content" class="form-textarea mb-2" rows="3" placeholder="Noi dung..."></textarea>'
      + '<select id="dm-ttl" class="form-select"><option value="1">1 gio</option><option value="6">6 gio</option><option value="24" selected>24 gio</option><option value="72">3 ngay</option></select>', confirmText: 'Gui',
      onConfirm: function() {
        SS.api.post('/disappearing-msgs.php?action=send', {conversation_id: convId, content: document.getElementById('dm-content').value, ttl_hours: parseInt(document.getElementById('dm-ttl').value)}).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); });
      }
    });
  }
};
