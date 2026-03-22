/**
 * ShipperShop Component — Color manipulation utilities
 */
window.SS = window.SS || {};

SS.ColorUtils = {
  hexToRgb: function(hex) { var r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16); return {r:r,g:g,b:b}; },
  withAlpha: function(hex, alpha) { var rgb = SS.ColorUtils.hexToRgb(hex); return 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')'; }
};
