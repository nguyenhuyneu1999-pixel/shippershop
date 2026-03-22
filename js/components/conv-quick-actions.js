/**
 * ShipperShop Component — Conversation Quick Actions
 * Mark urgent, mute, label, auto-reply
 */
window.SS = window.SS || {};

SS.ConvQuickActions = {
  show: function(conversationId) {
    SS.api.get('/conv-quick-actions.php?conversation_id=' + conversationId).then(function(d) {
      var a = d.data || {};
      var actions = [
        {action: 'urgent', icon: a.urgent ? '🔴' : '⚪', label: a.urgent ? 'Bo khan cap' : 'Danh dau khan cap', active: a.urgent},
        {action: 'mute', icon: a.muted ? '🔇' : '🔔', label: a.muted ? 'Bat thong bao' : 'Tat thong bao', active: a.muted},
      ];

      var html = '';
      for (var i = 0; i < actions.length; i++) {
        var act = actions[i];
        html += '<div class="list-item" style="cursor:pointer" onclick="SS.ConvQuickActions._do(' + conversationId + ',\'' + act.action + '\')">'
          + '<span style="font-size:20px;width:28px;text-align:center">' + act.icon + '</span>'
          + '<span class="text-sm">' + act.label + '</span></div>';
      }

      // Label
      html += '<div class="list-item" style="cursor:pointer" onclick="SS.ConvQuickActions._setLabel(' + conversationId + ')">'
        + '<span style="font-size:20px;width:28px;text-align:center">🏷️</span>'
        + '<span class="text-sm">Gan nhan' + (a.label ? ': ' + SS.utils.esc(a.label) : '') + '</span></div>';

      // Auto-reply
      html += '<div class="list-item" style="cursor:pointer" onclick="SS.ConvQuickActions._setAutoReply(' + conversationId + ')">'
        + '<span style="font-size:20px;width:28px;text-align:center">🤖</span>'
        + '<span class="text-sm">Tu dong tra loi' + (a.auto_reply ? ' (dang bat)' : '') + '</span></div>';

      html += '<div class="divider"></div>'
        + '<div class="list-item" style="cursor:pointer;color:var(--danger)" onclick="SS.ConvQuickActions._do(' + conversationId + ',\'clear\')">'
        + '<span style="font-size:20px;width:28px;text-align:center">🗑️</span><span class="text-sm">Xoa tat ca thiet lap</span></div>';

      SS.ui.sheet({title: 'Thao tac nhanh', html: html});
    });
  },

  _do: function(convId, action) {
    SS.ui.closeSheet();
    SS.api.post('/conv-quick-actions.php?action=' + action, {conversation_id: convId}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success', 2000);
    });
  },

  _setLabel: function(convId) {
    SS.ui.closeSheet();
    var labels = ['Quan trong', 'Cong viec', 'Ca nhan', 'Don hang', 'Khieu nai'];
    var html = '<div class="flex gap-2 flex-wrap">';
    for (var i = 0; i < labels.length; i++) {
      html += '<div class="chip" style="cursor:pointer" onclick="SS.ConvQuickActions._applyLabel(' + convId + ',\'' + labels[i] + '\')">' + labels[i] + '</div>';
    }
    html += '</div>';
    SS.ui.sheet({title: 'Chon nhan', html: html});
  },

  _applyLabel: function(convId, label) {
    SS.ui.closeSheet();
    SS.api.post('/conv-quick-actions.php?action=label', {conversation_id: convId, label: label}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success');
    });
  },

  _setAutoReply: function(convId) {
    SS.ui.closeSheet();
    SS.ui.modal({
      title: 'Tu dong tra loi',
      html: '<textarea id="cqa-reply" class="form-textarea" rows="3" placeholder="VD: Hien tai toi dang ban, se tra loi sau..."></textarea>',
      confirmText: 'Luu',
      onConfirm: function() {
        SS.api.post('/conv-quick-actions.php?action=auto_reply', {
          conversation_id: convId,
          message: document.getElementById('cqa-reply').value
        }).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); });
      }
    });
  }
};
