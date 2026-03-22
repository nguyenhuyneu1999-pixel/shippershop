window.SS = window.SS || {};
SS.ContentInsightsAI = {
  analyze: function(text) {
    if (!text) { SS.ui.modal({title: 'Phan tich AI', html: '<textarea id="cia-text" class="form-textarea" rows="4" placeholder="Nhap noi dung bai viet..."></textarea>', confirmText: 'Phan tich', onConfirm: function() { SS.ContentInsightsAI.analyze(document.getElementById('cia-text').value); }}); return; }
    SS.api.get('/content-insights-ai.php?text=' + encodeURIComponent(text.substring(0, 500))).then(function(d) {
      var data = d.data || {};
      var topicColors = {giao_hang: '#7c3aed', kinh_nghiem: '#f59e0b', giao_thong: '#ef4444', thu_nhap: '#22c55e', cong_dong: '#3b82f6', phan_hoi: '#ec4899', general: '#6b7280'};
      var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:12px;border-top:3px solid ' + (topicColors[data.topic] || '#999') + '"><div class="font-bold text-sm">' + SS.utils.esc(data.topic_label || '') + '</div><div class="text-xs text-muted">Chu de</div></div>'
        + '<div class="card" style="padding:12px"><div class="font-bold text-sm">' + SS.utils.esc(data.audience_label || '') + '</div><div class="text-xs text-muted">Doi tuong</div></div></div>';
      // Suggestions
      var suggestions = data.suggestions || [];
      if (suggestions.length) {
        html += '<div class="text-sm font-bold mb-2">💡 Goi y cai thien</div>';
        for (var i = 0; i < suggestions.length; i++) html += '<div class="card mb-1" style="padding:8px;border-left:3px solid var(--warning)"><div class="text-xs">' + SS.utils.esc(suggestions[i]) + '</div></div>';
      } else {
        html += '<div class="card mb-2" style="padding:8px;border-left:3px solid var(--success);text-align:center"><div class="text-xs">✅ Noi dung tot! Khong can cai thien</div></div>';
      }
      // Similar posts
      var similar = data.similar_posts || [];
      if (similar.length) {
        html += '<div class="text-sm font-bold mb-2 mt-3">Bai tuong tu</div>';
        for (var s = 0; s < similar.length; s++) html += '<div class="text-xs p-1" style="border-bottom:1px solid var(--border-light)">#' + similar[s].id + ' ' + SS.utils.esc(similar[s].preview) + ' · ❤️' + similar[s].likes_count + '</div>';
      }
      html += '<div class="text-xs text-muted mt-2">' + (data.word_count || 0) + ' tu</div>';
      SS.ui.sheet({title: '🤖 AI Insights', html: html});
    });
  }
};
