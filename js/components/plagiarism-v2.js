window.SS = window.SS || {};
SS.PlagiarismV2 = {
  check: function(text) {
    if (!text) { SS.ui.modal({title: 'Kiem tra trung lap', html: '<textarea id="pv2-text" class="form-textarea" rows="4" placeholder="Dan noi dung can kiem tra..."></textarea>', confirmText: 'Kiem tra', onConfirm: function() { SS.PlagiarismV2.check(document.getElementById('pv2-text').value); }}); return; }
    SS.api.get('/plagiarism-v2.php?text=' + encodeURIComponent(text.substring(0, 500))).then(function(d) {
      var data = d.data || {};
      var color = data.original ? 'var(--success)' : 'var(--danger)';
      var html = '<div class="text-center mb-3"><div style="width:80px;height:80px;border-radius:50%;border:5px solid ' + color + ';display:inline-flex;align-items:center;justify-content:center;flex-direction:column"><div style="font-size:20px;font-weight:800;color:' + color + '">' + (data.score || 0) + '%</div><div style="font-size:10px">' + (data.original ? 'Doc dao' : 'Trung lap') + '</div></div>'
        + '<div class="text-xs text-muted mt-1">' + (data.ngrams_checked || 0) + ' cum tu · ' + (data.posts_scanned || 0) + ' bai quet</div></div>';
      var matches = data.matches || [];
      if (matches.length) {
        html += '<div class="text-sm font-bold mb-2" style="color:var(--danger)">Trung lap</div>';
        for (var i = 0; i < matches.length; i++) {
          var m = matches[i];
          html += '<div class="card mb-1" style="padding:8px;border-left:3px solid var(--danger)"><div class="text-xs">' + SS.utils.esc(m.preview) + '</div><div class="text-xs text-muted">Post #' + m.post_id + ' · ' + m.similarity + '% giong · ' + m.matched_phrases + ' cum trung</div></div>';
        }
      }
      SS.ui.sheet({title: '🔍 Kiem tra trung lap', html: html});
    });
  }
};
