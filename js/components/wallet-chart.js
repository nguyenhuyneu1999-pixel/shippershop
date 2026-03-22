/**
 * ShipperShop Component — Wallet Chart
 * Balance/income/expense chart visualization
 */
window.SS = window.SS || {};

SS.WalletChart = {
  show: function(days) {
    days = days || 30;
    SS.api.get('/wallet-chart.php?days=' + days).then(function(d) {
      var data = d.data || {};
      var daily = data.daily || [];
      var sum = data.summary || {};

      var html = '<div class="flex gap-2 mb-3">';
      [7, 30, 90].forEach(function(dd) {
        html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.WalletChart.show(' + dd + ')" style="cursor:pointer">' + dd + 'd</div>';
      });
      html += '</div>';

      // Balance card
      html += '<div class="card mb-3" style="padding:16px;background:linear-gradient(135deg,var(--primary),#6d28d9);color:#fff;border-radius:12px;text-align:center">'
        + '<div class="text-xs" style="opacity:0.8">So du hien tai</div>'
        + '<div style="font-size:24px;font-weight:800">' + SS.utils.formatMoney(data.balance || 0) + 'd</div></div>';

      // Income/Expense summary
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--success)">+' + SS.utils.formatMoney(sum.income || 0) + '</div><div class="text-xs text-muted">Thu</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--danger)">-' + SS.utils.formatMoney(sum.expense || 0) + '</div><div class="text-xs text-muted">Chi</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--primary)">' + SS.utils.formatMoney(sum.net || 0) + '</div><div class="text-xs text-muted">Rong</div></div></div>';

      // Simple bar chart
      if (daily.length) {
        var maxVal = Math.max.apply(null, daily.map(function(dd) { return Math.max(parseInt(dd.income) || 0, parseInt(dd.expense) || 0); })) || 1;
        html += '<div class="text-sm font-bold mb-2">Bieu do ' + days + ' ngay</div><div style="display:flex;align-items:flex-end;gap:1px;height:50px">';
        for (var i = 0; i < daily.length; i++) {
          var inc = parseInt(daily[i].income) || 0;
          var h = Math.max(3, Math.round(inc / maxVal * 46));
          html += '<div style="flex:1;height:' + h + 'px;background:var(--success);border-radius:2px 2px 0 0" title="' + daily[i].day + ': +' + inc + '"></div>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Vi tien', html: html});
    });
  }
};
