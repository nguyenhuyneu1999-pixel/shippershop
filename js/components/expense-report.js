/**
 * ShipperShop Component — Expense Report
 */
window.SS = window.SS || {};

SS.ExpenseReport = {
  show: function(month, year) {
    month = month || new Date().getMonth() + 1;
    year = year || new Date().getFullYear();
    SS.api.get('/expense-report.php?month=' + month + '&year=' + year).then(function(d) {
      var data = d.data || {};
      var profitColor = (data.net_profit || 0) >= 0 ? 'var(--success)' : 'var(--danger)';

      var html = '<div class="flex justify-between items-center mb-3">'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.ExpenseReport.show(' + (month === 1 ? 12 : month - 1) + ',' + (month === 1 ? year - 1 : year) + ')">←</button>'
        + '<span class="font-bold">' + SS.utils.esc(data.month_name || '') + '</span>'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.ExpenseReport.show(' + (month === 12 ? 1 : month + 1) + ',' + (month === 12 ? year + 1 : year) + ')">→</button></div>';

      // Big net profit
      html += '<div class="card mb-3" style="padding:16px;text-align:center;background:linear-gradient(135deg,' + profitColor + '15,transparent);border-radius:12px">'
        + '<div class="text-xs text-muted">Loi nhuan rong</div>'
        + '<div style="font-size:28px;font-weight:800;color:' + profitColor + '">' + SS.utils.formatMoney(data.net_profit || 0) + 'd</div>'
        + '<div class="text-xs text-muted">' + (data.profit_margin || 0) + '% margin</div></div>';

      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--success)">+' + SS.utils.formatMoney(data.income || 0) + 'd</div><div class="text-xs text-muted">Thu nhap</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--danger)">-' + SS.utils.formatMoney(data.fuel || 0) + 'd</div><div class="text-xs text-muted">Xang</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold">' + (data.deliveries || 0) + '</div><div class="text-xs text-muted">Don giao</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold">' + SS.utils.formatMoney(data.avg_per_delivery || 0) + 'd</div><div class="text-xs text-muted">TB/don</div></div></div>';

      html += '<div class="text-xs text-muted text-center">' + (data.total_km || 0) + ' km · ' + (data.cost_per_km || 0) + 'd/km</div>';
      SS.ui.sheet({title: '📊 Bao cao chi phi', html: html});
    });
  }
};
