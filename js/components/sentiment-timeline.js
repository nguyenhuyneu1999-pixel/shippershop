window.SS = window.SS || {};
SS.SentimentTimeline = {
  show: function(days, userId) {
    days = days || 14;
    var url = '/sentiment-timeline.php?days=' + days + (userId ? '&user_id=' + userId : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var summary = data.summary || {};
      var timeline = data.timeline || [];
      var moodIcons = {positive: '😊', negative: '😟', neutral: '😐'};
      var html = '<div class="flex gap-2 mb-3">';
      [7, 14, 30].forEach(function(dd) { html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.SentimentTimeline.show(' + dd + ',' + (userId || 'null') + ')" style="cursor:pointer">' + dd + 'd</div>'; });
      html += '</div>';
      html += '<div class="text-center mb-3"><div style="font-size:36px">' + (moodIcons[summary.mood] || '😐') + '</div><div class="font-bold">' + (summary.positive_pct || 0) + '% tich cuc</div><div class="text-xs text-muted">' + (summary.total || 0) + ' bai phan tich</div></div>';
      html += '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:8px;flex:1;border-top:3px solid var(--success)"><div class="font-bold">' + (summary.positive || 0) + '</div><div class="text-xs text-muted">😊</div></div>'
        + '<div class="card" style="padding:8px;flex:1;border-top:3px solid var(--text-muted)"><div class="font-bold">' + (summary.neutral || 0) + '</div><div class="text-xs text-muted">😐</div></div>'
        + '<div class="card" style="padding:8px;flex:1;border-top:3px solid var(--danger)"><div class="font-bold">' + (summary.negative || 0) + '</div><div class="text-xs text-muted">😟</div></div></div>';
      if (timeline.length) {
        html += '<div class="text-sm font-bold mb-1">Timeline</div><div style="display:flex;align-items:flex-end;gap:2px;height:50px">';
        for (var i = 0; i < timeline.length; i++) {
          var t = timeline[i];
          var maxT = t.total || 1;
          var pH = Math.round(t.positive / maxT * 46);
          var nH = Math.round(t.negative / maxT * 46);
          html += '<div style="flex:1;display:flex;flex-direction:column;justify-content:flex-end;height:50px">'
            + '<div style="height:' + Math.max(2, pH) + 'px;background:var(--success);border-radius:2px 2px 0 0"></div>'
            + '<div style="height:' + Math.max(2, nH) + 'px;background:var(--danger);border-radius:0 0 2px 2px"></div></div>';
        }
        html += '</div>';
      }
      SS.ui.sheet({title: '📊 Sentiment Timeline', html: html});
    });
  }
};
