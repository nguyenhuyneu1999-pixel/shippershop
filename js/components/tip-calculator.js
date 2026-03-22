/**
 * ShipperShop Component — Tip Calculator
 */
window.SS = window.SS || {};

SS.TipCalculator = {
  show: function() {
    var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">'
      + '<div><label class="text-xs text-muted">Gia tri don (d)</label><input id="tc-val" class="form-input" type="number" value="200000"></div>'
      + '<div><label class="text-xs text-muted">Khoang cach (km)</label><input id="tc-km" class="form-input" type="number" value="3" step="0.5"></div>'
      + '<div><label class="text-xs text-muted">Thoi tiet</label><select id="tc-wt" class="form-select"><option value="normal">Binh thuong</option><option value="rain">Mua</option><option value="hot">Nong</option><option value="storm">Bao</option></select></div>'
      + '<div class="flex items-end gap-2"><label class="text-xs"><input type="checkbox" id="tc-rush"> Gio cao diem</label></div></div>'
      + '<button class="btn btn-primary mt-3" onclick="SS.TipCalculator.calc()" style="width:100%">💰 Tinh tip</button><div id="tc-results" class="mt-3"></div>';
    SS.ui.sheet({title: '💰 Tinh tip cho shipper', html: html});
  },
  calc: function() {
    var v = document.getElementById('tc-val').value;
    var k = document.getElementById('tc-km').value;
    var w = document.getElementById('tc-wt').value;
    var r = document.getElementById('tc-rush').checked ? '1' : '';
    SS.api.get('/tip-calculator.php?order_value=' + v + '&distance=' + k + '&weather=' + w + '&rush_hour=' + r).then(function(d) {
      var tips = (d.data || {}).tips || [];
      var quick = (d.data || {}).quick_tips || [];
      var html = '<div class="text-xs text-muted mb-2">He so: x' + ((d.data || {}).multiplier || 1) + '</div>';
      for (var i = 0; i < tips.length; i++) {
        var t = tips[i];
        html += '<div class="flex justify-between items-center p-2" style="border-bottom:1px solid var(--border-light)"><span class="text-sm">' + t.icon + ' ' + SS.utils.esc(t.label) + ' (' + t.percentage + '%)</span><span class="font-bold" style="color:var(--primary)">' + SS.utils.formatMoney(t.amount) + 'd</span></div>';
      }
      html += '<div class="flex gap-2 flex-wrap mt-3">';
      for (var q = 0; q < quick.length; q++) html += '<button class="chip" onclick="SS.CopyText.copy(\'' + quick[q] + 'd tip\')">' + SS.utils.formatMoney(quick[q]) + 'd</button>';
      html += '</div>';
      document.getElementById('tc-results').innerHTML = html;
    });
  }
};
