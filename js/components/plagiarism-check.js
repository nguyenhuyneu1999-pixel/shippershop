/**
 * ShipperShop Component — Plagiarism Check
 */
window.SS = window.SS || {};

SS.PlagiarismCheck = {
  check: function(text, containerId) {
    if (!text || text.length < 20) return;
    SS.api.post('/plagiarism-check.php', {text: text}).then(function(d) {
      var data = d.data || {};
      var el = document.getElementById(containerId);
      if (!el) return;
      if (data.is_original) {
        el.innerHTML = '<span class="text-xs" style="color:var(--success)">✅ Noi dung goc (' + data.max_similarity + '% trung)</span>';
      } else {
        el.innerHTML = '<span class="text-xs" style="color:var(--danger)">⚠️ Trung ' + data.max_similarity + '% voi ' + (data.matches||[]).length + ' bai khac</span>';
      }
    }).catch(function() {});
  },
  showDetail: function(text) {
    SS.api.post('/plagiarism-check.php', {text: text}).then(function(d) {
      var data = d.data || {};
      var html = '<div class="text-center mb-3"><div style="font-size:36px">' + (data.is_original ? '✅' : '⚠️') + '</div>'
        + '<div class="font-bold">' + (data.is_original ? 'Noi dung goc' : 'Phat hien trung lap') + '</div>'
        + '<div class="text-sm text-muted">Do tuong dong cao nhat: ' + (data.max_similarity || 0) + '%</div></div>';
      var matches = data.matches || [];
      if (matches.length) {
        html += '<div class="text-sm font-bold mb-2">Bai viet tuong tu</div>';
        for (var i = 0; i < matches.length; i++) {
          var m = matches[i];
          html += '<div class="card mb-2" style="padding:8px;border-left:3px solid var(--warning)"><div class="text-sm">' + SS.utils.esc(m.preview) + '...</div>'
            + '<div class="text-xs mt-1"><span style="color:var(--danger)">' + m.similarity + '% trung</span> · Post #' + m.post_id + '</div></div>';
        }
      }
      SS.ui.sheet({title: 'Kiem tra trung lap', html: html});
    });
  }
};
