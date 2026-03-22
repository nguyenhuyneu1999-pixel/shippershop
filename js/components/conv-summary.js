window.SS = window.SS || {};
SS.ConvSummary = {
  show: function(conversationId) {
    SS.api.get('/conv-summary.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      if (!data.message_count) { SS.ui.sheet({title: 'Tom tat', html: '<div class="empty-state p-3"><div class="empty-text">Chua co tin nhan</div></div>'}); return; }
      var sentIcons = {positive: '😊', negative: '😔', neutral: '😐'};
      var html = '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:8px;flex:1"><div class="font-bold">' + data.message_count + '</div><div class="text-xs text-muted">Tin nhan</div></div>'
        + '<div class="card" style="padding:8px;flex:1"><div class="font-bold">' + (data.participants || []).length + '</div><div class="text-xs text-muted">Nguoi</div></div>'
        + '<div class="card" style="padding:8px;flex:1"><div style="font-size:24px">' + (sentIcons[data.sentiment] || '😐') + '</div><div class="text-xs text-muted">' + SS.utils.esc(data.sentiment || '') + '</div></div></div>';
      var actions = data.action_items || [];
      if (actions.length) {
        html += '<div class="text-sm font-bold mb-1">Hanh dong</div>';
        for (var i = 0; i < actions.length; i++) {
          html += '<div class="text-xs p-1" style="border-left:2px solid var(--primary);padding-left:8px;margin-bottom:4px">' + SS.utils.esc(actions[i].text.substring(0, 80)) + ' — <em>' + SS.utils.esc(actions[i].by) + '</em></div>';
        }
      }
      SS.ui.sheet({title: 'Tom tat cuoc tro chuyen', html: html});
    });
  }
};
