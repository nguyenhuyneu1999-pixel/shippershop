/**
 * ShipperShop Component — Content Rewriter
 */
window.SS = window.SS || {};

SS.ContentRewriter = {
  show: function(text) {
    if (!text) {
      SS.ui.modal({title: 'Viet lai noi dung', html: '<textarea id="cr-text" class="form-textarea" rows="4" placeholder="Nhap noi dung can viet lai..."></textarea>', confirmText: 'Viet lai',
        onConfirm: function() { SS.ContentRewriter.rewrite(document.getElementById('cr-text').value); }
      });
      return;
    }
    SS.ContentRewriter.rewrite(text);
  },
  rewrite: function(text) {
    SS.api.post('/content-rewriter.php', {text: text}).then(function(d) {
      var data = d.data || {};
      var results = data.rewritten || [];
      var html = '<div class="text-xs text-muted mb-3">Goc: "' + SS.utils.esc((data.original || '').substring(0, 60)) + '..."</div>';
      for (var i = 0; i < results.length; i++) {
        var r = results[i];
        html += '<div class="card mb-2" style="padding:10px"><div class="flex justify-between mb-1"><span class="font-bold text-sm">' + r.icon + ' ' + SS.utils.esc(r.name) + '</span><span class="text-xs text-muted">' + r.char_count + ' ky tu</span></div>'
          + '<pre style="font-size:11px;white-space:pre-wrap;background:var(--border-light);padding:8px;border-radius:6px;max-height:100px;overflow:auto">' + SS.utils.esc(r.text) + '</pre>'
          + '<button class="btn btn-ghost btn-sm mt-1" onclick="SS.CopyText.copy(document.querySelectorAll(\'.cr-pre\')[' + i + '].textContent)"><i class="fa-solid fa-copy"></i> Copy</button></div>';
      }
      SS.ui.sheet({title: '✏️ Viet lai (' + results.length + ' phien ban)', html: html});
    });
  }
};
