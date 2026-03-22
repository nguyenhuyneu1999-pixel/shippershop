/**
 * ShipperShop Component — AI Content Suggest
 */
window.SS = window.SS || {};

SS.AiSuggest = {
  show: function(category) {
    var url = '/ai-suggest.php' + (category ? '?category=' + category : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var suggestions = data.suggestions || [];
      var categories = data.categories || [];

      var html = '<div class="flex gap-2 flex-wrap mb-3">';
      html += '<div class="chip ' + (!category ? 'chip-active' : '') + '" onclick="SS.AiSuggest.show()" style="cursor:pointer">Tat ca</div>';
      for (var c = 0; c < categories.length; c++) {
        html += '<div class="chip ' + (category === categories[c].id ? 'chip-active' : '') + '" onclick="SS.AiSuggest.show(\'' + categories[c].id + '\')" style="cursor:pointer">' + categories[c].icon + ' ' + SS.utils.esc(categories[c].name) + '</div>';
      }
      html += '</div>';

      for (var i = 0; i < suggestions.length; i++) {
        var s = suggestions[i];
        html += '<div class="card mb-2" style="padding:12px' + (s.is_relevant ? ';border-left:3px solid var(--success)' : '') + '">'
          + '<div class="flex justify-between"><span class="font-bold text-sm">' + SS.utils.esc(s.title) + (s.is_relevant ? ' 🟢' : '') + '</span><span class="text-xs text-muted">' + SS.utils.esc(s.time) + '</span></div>'
          + '<pre style="font-size:11px;white-space:pre-wrap;background:var(--border-light);padding:8px;border-radius:6px;margin:6px 0;max-height:120px;overflow:auto">' + SS.utils.esc(s.template) + '</pre>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.CopyText.copy(' + "'" + SS.utils.esc(s.template).replace(/'/g, "\\'") + "'" + ')"><i class="fa-solid fa-copy"></i> Copy</button></div>';
      }
      SS.ui.sheet({title: '✨ Goi y noi dung (' + suggestions.length + ')', html: html});
    });
  }
};
