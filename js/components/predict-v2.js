/**
 * ShipperShop Component — Engagement Predictor V2
 */
window.SS = window.SS || {};

SS.PredictV2 = {
  show: function(text, hasImage) {
    if (!text) {
      SS.ui.modal({title: 'Du doan tuong tac', html: '<textarea id="pv2-text" class="form-textarea" rows="4" placeholder="Nhap noi dung bai viet..."></textarea><label class="text-xs mt-2"><input type="checkbox" id="pv2-img"> Co hinh anh</label>', confirmText: 'Du doan',
        onConfirm: function() { SS.PredictV2.predict(document.getElementById('pv2-text').value, document.getElementById('pv2-img').checked); }
      });
      return;
    }
    SS.PredictV2.predict(text, hasImage);
  },
  predict: function(text, hasImage) {
    SS.api.get('/predict-v2.php?text=' + encodeURIComponent(text.substring(0, 500)) + '&has_image=' + (hasImage ? '1' : '') + '&hour=' + new Date().getHours()).then(function(d) {
      var data = d.data || {};
      var color = data.score >= 70 ? 'var(--success)' : (data.score >= 40 ? 'var(--primary)' : 'var(--warning)');
      var html = '<div class="text-center mb-3"><div style="width:80px;height:80px;border-radius:50%;border:5px solid ' + color + ';display:inline-flex;align-items:center;justify-content:center;flex-direction:column"><div style="font-size:24px;font-weight:800;color:' + color + '">' + (data.score || 0) + '</div><div style="font-size:10px">' + SS.utils.esc(data.prediction || '') + '</div></div>'
        + '<div class="text-sm mt-2">~' + (data.estimated_likes || 0) + ' likes · ~' + (data.estimated_comments || 0) + ' comments</div></div>';
      var factors = data.factors || [];
      if (factors.length) {
        html += '<div class="text-sm font-bold mb-1">Yeu to</div>';
        for (var i = 0; i < factors.length; i++) html += '<div class="flex justify-between text-xs p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(factors[i].factor) + '</span><span class="font-bold" style="color:var(--success)">' + factors[i].impact + '</span></div>';
      }
      var tips = data.tips || [];
      if (tips.length) {
        html += '<div class="text-sm font-bold mb-1 mt-2">Goi y cai thien</div><div class="flex gap-2 flex-wrap">';
        for (var t = 0; t < tips.length; t++) html += '<span class="chip" style="font-size:11px">💡 ' + SS.utils.esc(tips[t]) + '</span>';
        html += '</div>';
      }
      SS.ui.sheet({title: 'Du doan tuong tac v2', html: html});
    });
  }
};
