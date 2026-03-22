/**
 * ShipperShop Component — Engagement Predictor
 * Predict post performance before publishing
 */
window.SS = window.SS || {};

SS.EngagementPredict = {
  analyze: function(text, containerId) {
    if (!text || text.length < 10) return;
    SS.api.post('/engagement-predict.php', {text: text}).then(function(d) {
      var data = d.data || {};
      var el = document.getElementById(containerId);
      if (!el) return;

      var score = data.score || 50;
      var color = score >= 70 ? 'var(--success)' : (score >= 40 ? 'var(--primary)' : 'var(--warning)');
      var labels = {'rat cao': '🔥', 'cao': '⭐', 'trung binh': '📊', 'thap': '📉'};

      var html = '<div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--bg);border-radius:8px">'
        + '<div style="width:36px;height:36px;border-radius:50%;background:' + color + '20;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:' + color + '">' + score + '</div>'
        + '<div style="flex:1"><div class="text-xs font-medium">' + (labels[data.prediction] || '') + ' Du doan: ' + SS.utils.esc(data.prediction || '') + '</div>'
        + '<div class="text-xs text-muted">~' + (data.expected_likes || 0) + ' likes, ~' + (data.expected_comments || 0) + ' comments</div></div></div>';

      // Factors
      var factors = data.factors || [];
      if (factors.length) {
        html += '<div style="margin-top:4px;font-size:11px;color:var(--text-muted)">';
        for (var i = 0; i < Math.min(factors.length, 3); i++) {
          var f = factors[i];
          var fColor = f.impact.indexOf('+') === 0 ? 'var(--success)' : 'var(--danger)';
          html += '<span style="color:' + fColor + '">' + f.impact + '</span> ' + SS.utils.esc(f.name) + ' · ';
        }
        html += '</div>';
      }

      el.innerHTML = html;
    }).catch(function() {});
  },

  // Debounced for textarea
  _timer: null,
  onInput: function(text, containerId) {
    clearTimeout(SS.EngagementPredict._timer);
    SS.EngagementPredict._timer = setTimeout(function() {
      SS.EngagementPredict.analyze(text, containerId);
    }, 1000);
  }
};
