/**
 * ShipperShop Component — Earnings Calculator
 */
window.SS = window.SS || {};

SS.EarningsCalc = {
  show: function() {
    var html = '<div class="text-sm text-muted mb-3">Tinh thu nhap du kien theo hang van chuyen</div>'
      + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">'
      + '<div><label class="text-xs text-muted">Don/ngay</label><input id="ec-del" class="form-input" type="number" value="15"></div>'
      + '<div><label class="text-xs text-muted">TB km/don</label><input id="ec-km" class="form-input" type="number" value="5" step="0.5"></div>'
      + '<div><label class="text-xs text-muted">TB COD (d)</label><input id="ec-cod" class="form-input" type="number" value="200000"></div>'
      + '<div><label class="text-xs text-muted">Gio lam</label><input id="ec-hrs" class="form-input" type="number" value="8"></div></div>'
      + '<button class="btn btn-primary mt-3" onclick="SS.EarningsCalc.calc()" style="width:100%"><i class="fa-solid fa-calculator"></i> Tinh</button>'
      + '<div id="ec-results" class="mt-3"></div>';
    SS.ui.sheet({title: '💰 Tinh thu nhap', html: html});
  },
  calc: function() {
    var d = document.getElementById('ec-del').value;
    var k = document.getElementById('ec-km').value;
    var c = document.getElementById('ec-cod').value;
    var h = document.getElementById('ec-hrs').value;
    SS.api.get('/earnings-calc.php?action=calculate&deliveries=' + d + '&avg_km=' + k + '&avg_cod=' + c + '&hours=' + h).then(function(res) {
      var results = (res.data || {}).results || [];
      var html = '';
      for (var i = 0; i < results.length; i++) {
        var r = results[i];
        var isTop = i === 0;
        html += '<div class="card mb-2" style="padding:10px' + (isTop ? ';border:2px solid var(--success)' : '') + '">'
          + '<div class="flex justify-between items-center"><span class="font-bold text-sm">' + r.icon + ' ' + SS.utils.esc(r.company) + (isTop ? ' 🏆' : '') + '</span>'
          + '<span class="font-bold" style="color:var(--success)">' + SS.utils.formatMoney(r.net) + 'd</span></div>'
          + '<div class="text-xs text-muted mt-1">Gross: ' + SS.utils.formatMoney(r.gross) + 'd · Xang: -' + SS.utils.formatMoney(r.fuel) + 'd · ' + SS.utils.formatMoney(r.per_hour) + 'd/h</div></div>';
      }
      document.getElementById('ec-results').innerHTML = html;
    });
  }
};
