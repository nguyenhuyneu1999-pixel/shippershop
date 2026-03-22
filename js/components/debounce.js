/**
 * ShipperShop Component — Debounce and throttle utility
 */
window.SS = window.SS || {};

SS.Debounce = {
  debounce: function(fn, delay) {
    var timer = null;
    return function() { var args = arguments; var ctx = this; clearTimeout(timer); timer = setTimeout(function() { fn.apply(ctx, args); }, delay || 300); };
  },
  throttle: function(fn, limit) {
    var last = 0;
    return function() { var now = Date.now(); if (now - last >= (limit || 300)) { last = now; fn.apply(this, arguments); } };
  }
};
