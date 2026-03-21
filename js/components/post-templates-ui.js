/**
 * ShipperShop Component — Post Template Chooser
 * Shows pre-made templates for common shipper posts
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.PostTemplates = {

  open: function(onSelect) {
    SS.api.get('/templates.php?action=categories').then(function(d) {
      var cats = d.data || [];
      var html = '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">';
      for (var i = 0; i < cats.length; i++) {
        var c = cats[i];
        html += '<div class="card card-hover" style="cursor:pointer;text-align:center;padding:16px" onclick="SS.PostTemplates._loadCategory(\'' + c.id + '\')">'
          + '<div style="font-size:28px;margin-bottom:4px">' + c.icon + '</div>'
          + '<div class="text-sm font-medium">' + SS.utils.esc(c.name) + '</div></div>';
      }
      html += '</div><div id="pt-templates" style="margin-top:16px"></div>';

      SS.ui.sheet({title: 'Mẫu bài viết', html: html});
      SS.PostTemplates._onSelect = onSelect;
    });
  },

  _onSelect: null,

  _loadCategory: function(category) {
    var el = document.getElementById('pt-templates');
    if (!el) return;
    el.innerHTML = '<div class="p-3 text-center"><div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/templates.php?category=' + category).then(function(d) {
      var templates = d.data || [];
      var html = '';
      for (var i = 0; i < templates.length; i++) {
        var t = templates[i];
        html += '<div class="card mb-2 card-hover" style="cursor:pointer" onclick="SS.PostTemplates._select(' + t.id + ')">'
          + '<div class="card-body" style="padding:12px">'
          + '<div class="flex items-center gap-2 mb-2"><span style="font-size:18px">' + t.icon + '</span><span class="font-bold text-sm">' + SS.utils.esc(t.title) + '</span></div>'
          + '<pre style="font-size:12px;color:var(--text-muted);white-space:pre-wrap;margin:0;line-height:1.5;max-height:80px;overflow:hidden">' + SS.utils.esc(t.template) + '</pre>'
          + '</div></div>';
      }
      el.innerHTML = html || '<div class="text-center text-muted text-sm p-3">Không có mẫu</div>';
    });
  },

  _select: function(templateId) {
    SS.api.get('/templates.php').then(function(d) {
      var templates = d.data || [];
      for (var i = 0; i < templates.length; i++) {
        if (templates[i].id === templateId) {
          var content = templates[i].template;
          // Replace placeholders with empty brackets for user to fill
          content = content.replace(/\{(\w+)\}/g, '[$1]');
          SS.ui.closeSheet();
          if (SS.PostTemplates._onSelect) {
            SS.PostTemplates._onSelect(content);
          }
          return;
        }
      }
    });
  }
};
