/**
 * ShipperShop Component — Vietnamese number formatting utility
 */
window.SS = window.SS || {};

SS.NumberFormat = {
  vnd: function(n) { return new Intl.NumberFormat('vi-VN').format(n || 0) + 'd'; },
  compact: function(n) { if (n >= 1e9) return (n/1e9).toFixed(1) + 'T'; if (n >= 1e6) return (n/1e6).toFixed(1) + 'Tr'; if (n >= 1e3) return (n/1e3).toFixed(1) + 'K'; return String(n); },
  percent: function(n, total) { return total > 0 ? Math.round(n / total * 100) + '%' : '0%'; }
};
