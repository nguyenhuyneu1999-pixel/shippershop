/**
 * ShipperShop Core — Utilities
 * esc, ago, fN, debounce, throttle, formatDate, formatMoney, etc.
 */
window.SS = window.SS || {};

SS.utils = {

  esc: function(s) {
    if (!s) return '';
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
  },

  ago: function(date) {
    if (!date) return '';
    var now = Date.now();
    var d = new Date(date).getTime();
    var diff = Math.floor((now - d) / 1000);
    if (diff < 0) return 'vừa xong';
    if (diff < 60) return 'vừa xong';
    if (diff < 3600) return Math.floor(diff / 60) + ' phút';
    if (diff < 86400) return Math.floor(diff / 3600) + ' giờ';
    if (diff < 604800) return Math.floor(diff / 86400) + ' ngày';
    if (diff < 2592000) return Math.floor(diff / 604800) + ' tuần';
    if (diff < 31536000) return Math.floor(diff / 2592000) + ' tháng';
    return Math.floor(diff / 31536000) + ' năm';
  },

  fN: function(n) {
    n = parseInt(n) || 0;
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
    return n.toString();
  },

  debounce: function(fn, ms) {
    var timer;
    return function() {
      var args = arguments;
      var ctx = this;
      clearTimeout(timer);
      timer = setTimeout(function() { fn.apply(ctx, args); }, ms);
    };
  },

  throttle: function(fn, ms) {
    var last = 0;
    return function() {
      var now = Date.now();
      if (now - last >= ms) {
        last = now;
        fn.apply(this, arguments);
      }
    };
  },

  formatDate: function(d) {
    if (!d) return '';
    var dt = new Date(d);
    var dd = String(dt.getDate()).padStart(2, '0');
    var mm = String(dt.getMonth() + 1).padStart(2, '0');
    var yy = dt.getFullYear();
    return dd + '/' + mm + '/' + yy;
  },

  formatDateTime: function(d) {
    if (!d) return '';
    var dt = new Date(d);
    var hh = String(dt.getHours()).padStart(2, '0');
    var mi = String(dt.getMinutes()).padStart(2, '0');
    return SS.utils.formatDate(d) + ' ' + hh + ':' + mi;
  },

  formatMoney: function(n) {
    n = parseInt(n) || 0;
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + 'đ';
  },

  copyText: function(text) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function() {
        if (SS.ui) SS.ui.toast('Đã sao chép', 'success');
      });
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      if (SS.ui) SS.ui.toast('Đã sao chép', 'success');
    }
  },

  isMobile: function() {
    return window.innerWidth <= 768;
  },

  randomId: function() {
    return 'ss_' + Math.random().toString(36).substr(2, 9);
  },

  parseQuery: function() {
    var params = {};
    var qs = window.location.search.substring(1);
    if (!qs) return params;
    var pairs = qs.split('&');
    for (var i = 0; i < pairs.length; i++) {
      var pair = pairs[i].split('=');
      params[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
    }
    return params;
  },

  truncate: function(str, len) {
    if (!str) return '';
    len = len || 150;
    if (str.length <= len) return str;
    return str.substring(0, len) + '...';
  },

  slugify: function(str) {
    return str.toLowerCase().replace(/[^\w\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');
  },

  isEmail: function(s) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s);
  },

  // Page view tracking
  trackPage: function() {
    var page = window.location.pathname.replace('/', '').replace('.html', '') || 'index';
    fetch('/api/v2/analytics.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ page: page, referrer: document.referrer })
    }).catch(function() {});
  }
};

// Auto track page view on load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', SS.utils.trackPage);
} else {
  SS.utils.trackPage();
}
