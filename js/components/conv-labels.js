/**
 * ShipperShop Component — Conversation Labels
 * Tag chats with colored labels (work, personal, urgent, etc.)
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.ConvLabels = {
  _labels: null,

  // Show label picker for a conversation
  open: function(conversationId) {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    Promise.all([
      SS.api.get('/conv-labels.php?action=labels'),
      SS.api.get('/conv-labels.php?action=get&conversation_id=' + conversationId)
    ]).then(function(results) {
      var allLabels = results[0].data || [];
      var current = results[1].data || [];

      var html = '';
      for (var i = 0; i < allLabels.length; i++) {
        var l = allLabels[i];
        var isActive = current.indexOf(l.id) !== -1;
        html += '<div class="list-item" style="cursor:pointer;padding:10px 0" onclick="SS.ConvLabels._toggle(\'' + l.id + '\',' + conversationId + ',this)">'
          + '<span style="font-size:20px;width:30px;text-align:center">' + l.icon + '</span>'
          + '<div class="flex-1"><div class="text-sm font-medium">' + SS.utils.esc(l.name) + '</div></div>'
          + '<div style="width:24px;height:24px;border-radius:6px;border:2px solid ' + (isActive ? l.color : 'var(--border)') + ';background:' + (isActive ? l.color : 'transparent') + ';display:flex;align-items:center;justify-content:center;transition:all .2s" data-label="' + l.id + '">'
          + (isActive ? '<i class="fa-solid fa-check" style="color:#fff;font-size:11px"></i>' : '')
          + '</div></div>';
      }
      SS.ui.sheet({title: 'Nhãn hội thoại', html: html});
    });
  },

  _toggle: function(labelId, convId, el) {
    var checkbox = el.querySelector('[data-label]');
    var isActive = checkbox.style.background !== 'transparent';

    // Get current labels from all checkboxes
    var checks = document.querySelectorAll('[data-label]');
    var labels = [];
    for (var i = 0; i < checks.length; i++) {
      var lid = checks[i].getAttribute('data-label');
      var active = checks[i].style.background !== 'transparent';
      if (lid === labelId) active = !isActive;
      if (active) labels.push(lid);
    }

    // Update UI
    if (isActive) {
      checkbox.style.background = 'transparent';
      checkbox.style.borderColor = 'var(--border)';
      checkbox.innerHTML = '';
    } else {
      var color = checkbox.style.borderColor;
      checkbox.style.background = color || 'var(--primary)';
      checkbox.innerHTML = '<i class="fa-solid fa-check" style="color:#fff;font-size:11px"></i>';
    }

    SS.api.post('/conv-labels.php', {conversation_id: convId, label_ids: labels});
  },

  // Render label badges inline
  renderBadges: function(labelIds) {
    if (!labelIds || !labelIds.length) return '';
    var allLabels = {work:{color:'#3b82f6',icon:'💼'},personal:{color:'#22c55e',icon:'👤'},group_buy:{color:'#f59e0b',icon:'🛒'},urgent:{color:'#ef4444',icon:'🔥'},important:{color:'#8b5cf6',icon:'⭐'},spam:{color:'#94a3b8',icon:'🚫'}};
    var html = '';
    for (var i = 0; i < Math.min(labelIds.length, 3); i++) {
      var l = allLabels[labelIds[i]];
      if (l) html += '<span style="font-size:10px" title="' + labelIds[i] + '">' + l.icon + '</span>';
    }
    return html;
  }
};
