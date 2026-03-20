/**
 * ShipperShop Core — Store (State Management)
 * localStorage wrapper for user, token, preferences
 */
window.SS = window.SS || {};

SS.store = {
  _mem: {},

  user: function() {
    try { return JSON.parse(localStorage.getItem('user') || 'null'); }
    catch(e) { return null; }
  },

  token: function() {
    return localStorage.getItem('token') || null;
  },

  isLoggedIn: function() {
    return !!SS.store.token() && !!SS.store.user();
  },

  isAdmin: function() {
    var u = SS.store.user();
    return u && u.role === 'admin';
  },

  userId: function() {
    var u = SS.store.user();
    return u ? parseInt(u.id) : 0;
  },

  login: function(token, userData) {
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(userData));
  },

  logout: function() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '/login.html';
  },

  updateUser: function(fields) {
    var u = SS.store.user();
    if (!u) return;
    for (var k in fields) {
      u[k] = fields[k];
    }
    localStorage.setItem('user', JSON.stringify(u));
  },

  set: function(key, value) {
    SS.store._mem[key] = value;
    try { localStorage.setItem('ss_' + key, JSON.stringify(value)); }
    catch(e) {}
  },

  get: function(key, fallback) {
    if (SS.store._mem[key] !== undefined) return SS.store._mem[key];
    try {
      var v = localStorage.getItem('ss_' + key);
      if (v !== null) {
        SS.store._mem[key] = JSON.parse(v);
        return SS.store._mem[key];
      }
    } catch(e) {}
    return fallback !== undefined ? fallback : null;
  },

  remove: function(key) {
    delete SS.store._mem[key];
    localStorage.removeItem('ss_' + key);
  }
};
