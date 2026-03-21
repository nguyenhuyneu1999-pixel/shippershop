/**
 * ShipperShop Component — Schedule Templates
 * Pre-built posting schedule picker
 */
window.SS = window.SS || {};

SS.ScheduleTemplates = {
  show: function() {
    SS.api.get('/schedule-templates.php').then(function(d) {
      var templates = (d.data || {}).templates || [];
      var html = '<div class="text-sm text-muted mb-3">Chon lich dang bai tu dong</div>';
      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">';
      for (var i = 0; i < templates.length; i++) {
        var t = templates[i];
        html += '<div class="card card-hover" style="padding:12px;cursor:pointer;text-align:center" onclick="SS.ScheduleTemplates._apply(\'' + t.id + '\')">'
          + '<div style="font-size:24px;margin-bottom:4px">' + t.icon + '</div>'
          + '<div class="font-bold text-sm">' + SS.utils.esc(t.name) + '</div>'
          + '<div class="text-xs text-muted mt-1">' + SS.utils.esc(t.desc) + '</div>'
          + '<div class="text-xs mt-1" style="color:var(--primary)">' + SS.utils.esc(t.schedule) + '</div></div>';
      }
      html += '</div>';
      SS.ui.sheet({title: 'Mau lich dang bai', html: html});
    });
  },

  _apply: function(templateId) {
    SS.ui.closeSheet();
    SS.api.post('/schedule-templates.php', {template_id: templateId}).then(function(d) {
      SS.ui.toast(d.message || 'Da ap dung!', 'success');
    });
  }
};
