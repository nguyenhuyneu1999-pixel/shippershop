/**
 * ShipperShop Component — Post Sentiment
 * Shows sentiment analysis for posts and platform overview
 */
window.SS = window.SS || {};

SS.PostSentiment = {
  analyze: function(postId) {
    SS.api.get('/post-sentiment.php?post_id=' + postId).then(function(d) {
      var data = d.data || {};
      var emoji = data.label === 'positive' ? '😊' : (data.label === 'negative' ? '😔' : '😐');
      var color = data.label === 'positive' ? 'var(--success)' : (data.label === 'negative' ? 'var(--danger)' : 'var(--text-muted)');
      var labels = {positive: 'Tich cuc', negative: 'Tieu cuc', neutral: 'Trung lap'};

      var html = '<div class="text-center mb-3"><div style="font-size:48px">' + emoji + '</div>'
        + '<div style="font-size:20px;font-weight:800;color:' + color + '">' + (labels[data.label] || data.label) + '</div>'
        + '<div class="text-sm text-muted">Diem: ' + (data.score || 0) + ' | ' + (data.text_length || 0) + ' ky tu</div></div>';

      var matches = data.matches || [];
      if (matches.length) {
        html += '<div class="text-sm font-bold mb-2">Tu khoa</div><div class="flex gap-2 flex-wrap">';
        for (var i = 0; i < matches.length; i++) {
          var m = matches[i];
          var mColor = m.type === 'positive' ? 'var(--success)' : 'var(--danger)';
          html += '<span class="chip" style="color:' + mColor + '">' + (m.type === 'positive' ? '👍' : '👎') + ' ' + SS.utils.esc(m.word) + '</span>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Phan tich cam xuc', html: html});
    });
  },

  overview: function() {
    SS.api.get('/post-sentiment.php?action=overview').then(function(d) {
      var data = d.data || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center;margin-bottom:16px">'
        + '<div class="card" style="padding:12px"><div style="font-size:24px">😊</div><div class="font-bold" style="color:var(--success)">' + (data.positive || 0) + '</div><div class="text-xs text-muted">Tich cuc</div></div>'
        + '<div class="card" style="padding:12px"><div style="font-size:24px">😐</div><div class="font-bold">' + (data.neutral || 0) + '</div><div class="text-xs text-muted">Trung lap</div></div>'
        + '<div class="card" style="padding:12px"><div style="font-size:24px">😔</div><div class="font-bold" style="color:var(--danger)">' + (data.negative || 0) + '</div><div class="text-xs text-muted">Tieu cuc</div></div></div>';

      // Score bar
      var pct = data.positive_pct || 0;
      html += '<div class="card" style="padding:12px"><div class="flex justify-between text-sm mb-1"><span>Diem trung binh</span><span class="font-bold">' + (data.avg_score || 0) + '</span></div>'
        + '<div style="height:8px;background:var(--border-light);border-radius:4px;overflow:hidden"><div style="width:' + pct + '%;height:100%;background:var(--success);border-radius:4px"></div></div>'
        + '<div class="text-xs text-muted text-center mt-1">' + pct + '% tich cuc (' + (data.total_posts || 0) + ' bai, 7 ngay)</div></div>';

      SS.ui.sheet({title: 'Cam xuc cong dong', html: html});
    });
  }
};
