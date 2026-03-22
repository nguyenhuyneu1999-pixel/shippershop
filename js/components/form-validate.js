/**
 * ShipperShop Component — Simple form validation helpers
 */
window.SS = window.SS || {};

SS.FormValidate = {
  required: function(val) { return val !== null && val !== undefined && String(val).trim().length > 0; },
  email: function(val) { return /^[^@]+@[^@]+\.[^@]+$/.test(val || ''); },
  phone: function(val) { return /^0[0-9]{9,10}$/.test((val || '').replace(/ /g, '')); },
  minLen: function(val, min) { return (val || '').length >= (min || 1); }
};
