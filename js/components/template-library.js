window.SS = window.SS || {};
SS.TemplateLibrary = {
  show: function(category, callback) {
    var url = '/template-library.php' + (category ? '?category=' + category : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var templates = data.templates || [];
      var categories = data.categories || [];
      SS.TemplateLibrary._cb = callback;
      var html = '<div class="flex gap-2 flex-wrap mb-3"><div class="chip ' + (!category ? 'chip-active' : '') + '" onclick="SS.TemplateLibrary.show(null,' + (callback ? 'SS.TemplateLibrary._cb' : 'null') + ')" style="cursor:pointer">Tat ca</div>';
      for (var c = 0; c < categories.length; c++) html += '<div class="chip ' + (category === categories[c].id ? 'chip-active' : '') + '" onclick="SS.TemplateLibrary.show(\'' + categories[c].id + '\',' + (callback ? 'SS.TemplateLibrary._cb' : 'null') + ')" style="cursor:pointer">' + categories[c].icon + '</div>';
      html += '</div>';
      for (var i = 0; i < templates.length; i++) {
        var t = templates[i];
        html += '<div class="card mb-2" style="padding:10px;cursor:pointer" onclick="SS.TemplateLibrary._use(' + t.id + ')">'
          + '<div class="font-bold text-sm">' + t.icon + ' ' + SS.utils.esc(t.title) + '</div>'
          + '<pre style="font-size:10px;white-space:pre-wrap;color:var(--text-muted);margin:4px 0;max-height:60px;overflow:hidden">' + SS.utils.esc(t.body.substring(0, 120)) + '...</pre></div>';
      }
      SS.ui.sheet({title: '📋 Mau bai dang (' + templates.length + ')', html: html});
      SS.TemplateLibrary._templates = templates;
    });
  },
  _cb: null, _templates: [],
  _use: function(id) {
    for (var i = 0; i < SS.TemplateLibrary._templates.length; i++) {
      if (SS.TemplateLibrary._templates[i].id === id) {
        SS.ui.closeSheet();
        if (SS.TemplateLibrary._cb) SS.TemplateLibrary._cb(SS.TemplateLibrary._templates[i].body);
        else SS.CopyText.copy(SS.TemplateLibrary._templates[i].body);
        break;
      }
    }
  }
};
