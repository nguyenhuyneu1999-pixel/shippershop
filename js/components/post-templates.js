/**
 * ShipperShop Component — Post Templates
 * Template picker for quick post creation with pre-made formats
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.PostTemplates = {

  open: function(onSelect) {
    SS.api.get('/templates.php?action=list').then(function(d) {
      var templates = d.data || [];
      if (!templates.length) { SS.ui.toast('Không có template', 'info'); return; }

      var cats = {};
      for (var i = 0; i < templates.length; i++) {
        var t = templates[i];
        var cat = t.category || 'other';
        if (!cats[cat]) cats[cat] = [];
        cats[cat].push(t);
      }

      var catLabels = {work:'🛵 Công việc',knowledge:'📚 Kiến thức',community:'👥 Cộng đồng',personal:'🏆 Cá nhân'};
      var html = '';

      for (var c in cats) {
        html += '<div class="text-xs font-bold text-muted mb-2 mt-3" style="text-transform:uppercase">' + (catLabels[c] || c) + '</div>';
        for (var j = 0; j < cats[c].length; j++) {
          var tpl = cats[c][j];
          html += '<div class="list-item" style="cursor:pointer;padding:10px 0" onclick="SS.PostTemplates._fill(\'' + tpl.id + '\')">'
            + '<span style="font-size:24px;width:36px;text-align:center">' + tpl.icon + '</span>'
            + '<div class="flex-1">'
            + '<div class="text-sm font-medium">' + SS.utils.esc(tpl.name) + '</div>'
            + '<div class="text-xs text-muted">' + tpl.fields.length + ' trường</div>'
            + '</div>'
            + '<i class="fa-solid fa-chevron-right text-muted" style="font-size:11px"></i>'
            + '</div>';
        }
      }

      SS.PostTemplates._onSelect = onSelect;
      SS.ui.sheet({title: 'Chọn mẫu bài viết', html: html});
    });
  },

  _onSelect: null,

  _fill: function(templateId) {
    SS.ui.closeSheet();
    SS.api.get('/templates.php?action=get&id=' + templateId).then(function(d) {
      var tpl = d.data;
      if (!tpl) return;

      var html = '<div class="text-center mb-3"><span style="font-size:36px">' + tpl.icon + '</span><div class="font-bold mt-1">' + SS.utils.esc(tpl.name) + '</div></div>';
      for (var i = 0; i < tpl.fields.length; i++) {
        var f = tpl.fields[i];
        var labels = {company:'Hãng vận chuyển',area:'Khu vực',orders:'Số đơn',income:'Thu nhập',note:'Ghi chú',from:'Từ',to:'Đến',duration:'Thời gian',condition:'Tình trạng',tip:'Mẹo',topic:'Chủ đề',content:'Nội dung',tag:'Hashtag',question:'Câu hỏi',subject:'Chủ đề',rating:'Đánh giá (1-5)',pros:'Ưu điểm',cons:'Nhược điểm',summary:'Tổng kết',period:'Kỳ',total_orders:'Tổng đơn',total_income:'Tổng thu nhập',avg_per_order:'TB/đơn',hours:'Số giờ',avg_per_hour:'TB/giờ',location:'Vị trí',status:'Tình trạng',suggestion:'Gợi ý',title:'Tiêu đề',description:'Mô tả'};
        var label = labels[f] || f;
        if (f === 'content' || f === 'description' || f === 'note' || f === 'question') {
          html += '<div class="form-group"><label class="form-label">' + label + '</label><textarea class="form-input tpl-field" data-field="' + f + '" rows="3"></textarea></div>';
        } else {
          html += '<div class="form-group"><label class="form-label">' + label + '</label><input class="form-input tpl-field" data-field="' + f + '"></div>';
        }
      }

      SS.ui.modal({
        title: 'Điền thông tin',
        html: html,
        confirmText: 'Tạo bài',
        onConfirm: function() {
          var data = {};
          var inputs = document.querySelectorAll('.tpl-field');
          for (var j = 0; j < inputs.length; j++) {
            data[inputs[j].getAttribute('data-field')] = inputs[j].value;
          }
          SS.api.post('/templates.php?action=fill', {template_id: templateId, data: data}).then(function(r) {
            var content = r.data && r.data.content;
            if (content && SS.PostTemplates._onSelect) {
              SS.PostTemplates._onSelect(content, r.data.type);
            }
            SS.ui.closeModal();
          });
        }
      });
    });
  }
};
