/**
 * ShipperShop Component — Content Analyzer
 */
window.SS = window.SS || {};

SS.ContentAnalyzer = {
  analyze: function(text) {
    if (!text || text.length < 10) { SS.ui.toast('Nhap it nhat 10 ky tu', 'warning'); return; }
    SS.api.post('/content-analyzer.php', {text: text}).then(function(d) {
      var data = d.data || {};
      var sentColors = {positive: 'var(--success)', negative: 'var(--danger)', neutral: 'var(--text-muted)'};
      var readLabels = {de_doc: 'De doc', trung_binh: 'Trung binh', kho_doc: 'Kho doc'};

      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center;margin-bottom:16px">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.word_count || 0) + '</div><div class="text-xs text-muted">Tu</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.sentence_count || 0) + '</div><div class="text-xs text-muted">Cau</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.emoji_count || 0) + '</div><div class="text-xs text-muted">Emoji</div></div></div>';

      // Readability + Sentiment
      html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px">'
        + '<div class="card" style="padding:10px;text-align:center"><div class="font-bold" style="color:' + (data.read_score >= 60 ? 'var(--success)' : 'var(--warning)') + '">' + (data.read_score || 0) + '/100</div><div class="text-xs text-muted">' + (readLabels[data.readability] || '') + '</div></div>'
        + '<div class="card" style="padding:10px;text-align:center"><div class="font-bold" style="color:' + (sentColors[data.sentiment] || '') + '">' + (data.sentiment_score || 50) + '/100</div><div class="text-xs text-muted">' + SS.utils.esc(data.sentiment || '') + ' (+' + (data.positive_words || 0) + '/-' + (data.negative_words || 0) + ')</div></div></div>';

      // Keywords
      var kw = data.keywords || [];
      if (kw.length) {
        html += '<div class="text-sm font-bold mb-2">Tu khoa</div><div class="flex gap-2 flex-wrap mb-3">';
        for (var i = 0; i < kw.length; i++) html += '<span class="chip">' + SS.utils.esc(kw[i].word) + ' <sup>' + kw[i].count + '</sup></span>';
        html += '</div>';
      }

      // Hashtags
      if ((data.hashtags || []).length) {
        html += '<div class="text-xs text-muted">' + data.hashtags.join(' ') + '</div>';
      }

      SS.ui.sheet({title: '📊 Phan tich noi dung', html: html});
    });
  }
};
