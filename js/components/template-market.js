/**
 * ShipperShop Component — Template Marketplace
 * Browse and use community post templates
 */
window.SS = window.SS || {};

SS.TemplateMarket = {
  show: function(category) {
    var url = '/template-market.php' + (category ? '?category=' + category : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var templates = data.templates || [];
      var categories = data.categories || [];

      // Category tabs
      var html = '<div class="flex gap-2 mb-3" style="overflow-x:auto">'
        + '<div class="chip ' + (!category ? 'chip-active' : '') + '" onclick="SS.TemplateMarket.show()" style="cursor:pointer">Tat ca</div>';
      for (var c = 0; c < categories.length; c++) {
        var active = category === categories[c].id ? 'chip-active' : '';
        html += '<div class="chip ' + active + '" onclick="SS.TemplateMarket.show(\'' + categories[c].id + '\')" style="cursor:pointer;white-space:nowrap">' + categories[c].icon + ' ' + SS.utils.esc(categories[c].name) + '</div>';
      }
      html += '</div>';

      // Templates
      for (var i = 0; i < templates.length; i++) {
        var t = templates[i];
        html += '<div class="card mb-2" style="padding:12px">'
          + '<div class="flex justify-between items-start mb-1"><div class="font-bold text-sm">' + SS.utils.esc(t.name) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.fN(t.uses) + ' luot dung</div></div>'
          + '<div class="text-xs" style="white-space:pre-wrap;color:var(--text-muted);max-height:60px;overflow:hidden">' + SS.utils.esc(t.content.substring(0, 120)) + '</div>'
          + '<div class="flex justify-between items-center mt-2">'
          + '<div class="text-xs">⭐ ' + t.rating + '/5</div>'
          + '<button class="btn btn-primary btn-sm" onclick="SS.TemplateMarket._use(' + i + ')"><i class="fa-solid fa-copy"></i> Dung mau</button></div></div>';
      }

      SS.ui.sheet({title: 'Mau bai viet (' + templates.length + ')', html: html});
      SS.TemplateMarket._templates = templates;
    });
  },

  _templates: [],

  _use: function(idx) {
    var t = SS.TemplateMarket._templates[idx];
    if (!t) return;
    SS.ui.closeSheet();
    SS.utils.copyText(t.content);
    SS.ui.toast('Da copy mau "' + t.name + '"!', 'success');
  }
};
