window.SS = window.SS || {};
SS.ConvWeatherShare = {
  show: function(conversationId) {
    SS.api.get('/conv-weather-share.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var shares = data.shares || [];
      var conditions = data.conditions || [];
      var html = '<div class="flex gap-2 flex-wrap mb-3">';
      for (var c = 0; c < conditions.length; c++) {
        var cd = conditions[c];
        html += '<button class="chip" style="cursor:pointer" onclick="SS.ConvWeatherShare.send(' + conversationId + ',\'' + cd.id + '\')">' + cd.icon + '</button>';
      }
      html += '</div>';
      if (!shares.length) html += '<div class="empty-state p-2"><div class="empty-icon">🌤️</div><div class="empty-text">Chia se thoi tiet khu vuc ban</div></div>';
      for (var i = 0; i < Math.min(shares.length, 10); i++) {
        var s = shares[i];
        var cond = null;
        for (var j = 0; j < conditions.length; j++) { if (conditions[j].id === s.condition) { cond = conditions[j]; break; } }
        var riskColors = {low: 'var(--success)', medium: 'var(--warning)', high: 'var(--danger)', critical: '#dc2626'};
        html += '<div class="card mb-2" style="padding:10px"><div class="flex items-center gap-2"><span style="font-size:24px">' + (cond ? cond.icon : '🌤️') + '</span><div class="flex-1"><div class="font-bold text-sm">' + (cond ? SS.utils.esc(cond.name) : '') + (s.location ? ' · ' + SS.utils.esc(s.location) : '') + '</div>'
          + (cond ? '<div class="text-xs" style="color:' + (riskColors[cond.risk] || '') + '">' + SS.utils.esc(cond.tip) + '</div>' : '')
          + '<div class="text-xs text-muted">' + SS.utils.esc(s.user_name || '') + ' · ' + SS.utils.ago(s.created_at) + '</div></div></div></div>';
      }
      SS.ui.sheet({title: '🌤️ Thoi tiet', html: html});
    });
  },
  send: function(convId, conditionId) {
    SS.api.post('/conv-weather-share.php', {conversation_id: convId, condition: conditionId, location: ''}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvWeatherShare.show(convId); });
  }
};
