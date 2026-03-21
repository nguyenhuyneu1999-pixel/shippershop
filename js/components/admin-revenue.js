/**
 * ShipperShop Component — Admin Revenue Dashboard
 * Revenue overview with charts and trends
 */
window.SS = window.SS || {};

SS.AdminRevenue = {
  show: function() {
    SS.api.get('/admin-revenue.php').then(function(d) {
      var data = d.data || {};
      var rev = data.revenue || {};
      var subs = data.subscriptions || {};

      // Revenue cards
      var html = '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px">';
      var cards = [
        {label: 'Hom nay', value: rev.today || 0, color: 'var(--success)'},
        {label: 'Tuan nay', value: rev.week || 0, color: 'var(--primary)'},
        {label: 'Thang nay', value: rev.month || 0, color: 'var(--warning)'},
        {label: 'Tong cong', value: rev.total || 0, color: 'var(--text)'},
      ];
      for (var i = 0; i < cards.length; i++) {
        var c = cards[i];
        html += '<div class="card" style="padding:12px;text-align:center"><div class="text-xs text-muted">' + c.label + '</div>'
          + '<div style="font-size:18px;font-weight:800;color:' + c.color + '">' + SS.utils.formatMoney(c.value) + '</div></div>';
      }
      html += '</div>';

      // Active subscriptions
      html += '<div class="text-sm font-bold mb-2">Goi dang hoat dong: ' + (subs.active || 0) + '</div>';
      var breakdown = subs.breakdown || [];
      for (var j = 0; j < breakdown.length; j++) {
        var s = breakdown[j];
        html += '<div class="flex justify-between text-sm p-1" style="border-bottom:1px solid var(--border-light)">'
          + '<span>' + SS.utils.esc(s.name) + '</span><span class="font-bold">' + s.count + '</span></div>';
      }

      // Pending
      var pending = data.pending || {};
      if (pending.count > 0) {
        html += '<div class="card mt-3" style="padding:10px;border-left:3px solid var(--warning)">'
          + '<div class="font-bold text-sm">⏳ ' + pending.count + ' giao dich cho duyet</div>'
          + '<div class="text-xs text-muted">' + SS.utils.formatMoney(pending.amount) + '</div></div>';
      }

      // Monthly trend
      var monthly = data.monthly_trend || [];
      if (monthly.length > 1) {
        html += '<div class="text-sm font-bold mt-3 mb-2">Xu huong 6 thang</div><div class="flex items-end gap-2" style="height:80px">';
        var maxRev = 1;
        for (var k = 0; k < monthly.length; k++) { if (parseInt(monthly[k].revenue) > maxRev) maxRev = parseInt(monthly[k].revenue); }
        for (var m = 0; m < monthly.length; m++) {
          var h = Math.max(4, Math.round(parseInt(monthly[m].revenue || 0) / maxRev * 70));
          html += '<div style="flex:1;text-align:center"><div style="height:' + h + 'px;background:var(--primary);border-radius:4px 4px 0 0"></div>'
            + '<div class="text-xs text-muted">' + (monthly[m].month || '').substring(5) + '</div></div>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Doanh thu', html: html});
    });
  }
};
