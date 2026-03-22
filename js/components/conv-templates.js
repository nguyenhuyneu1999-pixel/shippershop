/**
 * ShipperShop Component — Conversation Templates
 */
window.SS = window.SS || {};

SS.ConvTemplates = {
  show: function(category, callback) {
    var url = '/conv-templates.php' + (category ? '?category=' + category : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var templates = data.templates || [];
      var categories = data.categories || [];
      SS.ConvTemplates._cb = callback;

      var html = '<div class="flex gap-2 flex-wrap mb-3">';
      html += '<div class="chip ' + (!category ? 'chip-active' : '') + '" onclick="SS.ConvTemplates.show(null,' + (callback ? 'SS.ConvTemplates._cb' : 'null') + ')" style="cursor:pointer">Tat ca</div>';
      for (var c = 0; c < categories.length; c++) {
        html += '<div class="chip ' + (category === categories[c].id ? 'chip-active' : '') + '" onclick="SS.ConvTemplates.show(\'' + categories[c].id + '\',' + (callback ? 'SS.ConvTemplates._cb' : 'null') + ')" style="cursor:pointer">' + categories[c].icon + '</div>';
      }
      html += '</div>';

      for (var i = 0; i < templates.length; i++) {
        var t = templates[i];
        html += '<div class="card mb-2" style="padding:10px;cursor:pointer" onclick="SS.ConvTemplates._use(' + t.id + ')">'
          + '<div class="font-bold text-sm">' + t.icon + ' ' + SS.utils.esc(t.title) + '</div>'
          + '<div class="text-xs text-muted mt-1">' + SS.utils.esc(t.msg.substring(0, 80)) + '...</div></div>';
      }
      SS.ui.sheet({title: '💬 Mau tin nhan (' + templates.length + ')', html: html});
      SS.ConvTemplates._templates = templates;
    });
  },
  _cb: null, _templates: [],
  _use: function(id) {
    for (var i = 0; i < SS.ConvTemplates._templates.length; i++) {
      if (SS.ConvTemplates._templates[i].id === id) {
        SS.ui.closeSheet();
        if (SS.ConvTemplates._cb) SS.ConvTemplates._cb(SS.ConvTemplates._templates[i].msg);
        else SS.CopyText.copy(SS.ConvTemplates._templates[i].msg);
        break;
      }
    }
  }
};
