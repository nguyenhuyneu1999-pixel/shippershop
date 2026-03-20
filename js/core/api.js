/**
 * ShipperShop Core — API Module
 * Auto Bearer token, error handling, redirect on 401
 * Usage: SS.api.get('/posts.php?limit=5').then(function(d){ ... })
 * KHÔNG dùng template literal. KHÔNG arrow functions.
 */
window.SS = window.SS || {};

SS.api = {
  base: '/api/v2',

  _headers: function(isJson) {
    var h = {};
    var tk = localStorage.getItem('token');
    if (tk) h['Authorization'] = 'Bearer ' + tk;
    if (isJson) h['Content-Type'] = 'application/json';
    return h;
  },

  _handleResponse: function(resp) {
    if (resp.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      if (window.location.pathname.indexOf('login') === -1) {
        window.location.href = '/login.html';
      }
      return Promise.reject({ message: 'Vui lòng đăng nhập' });
    }
    if (resp.status === 429) {
      if (SS.ui && SS.ui.toast) SS.ui.toast('Quá nhiều yêu cầu. Thử lại sau.', 'warning');
      return Promise.reject({ message: 'Rate limited' });
    }
    return resp.json().then(function(data) {
      if (!data.success && resp.status >= 400) {
        return Promise.reject(data);
      }
      return data;
    });
  },

  _handleError: function(err) {
    var msg = (err && err.message) ? err.message : 'Lỗi kết nối';
    if (SS.ui && SS.ui.toast && msg !== 'Vui lòng đăng nhập') {
      SS.ui.toast(msg, 'error');
    }
    return Promise.reject(err);
  },

  get: function(endpoint, params) {
    var url = SS.api.base + endpoint;
    if (params) {
      var qs = [];
      for (var k in params) {
        if (params[k] !== null && params[k] !== undefined && params[k] !== '') {
          qs.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
        }
      }
      if (qs.length) url += (url.indexOf('?') > -1 ? '&' : '?') + qs.join('&');
    }
    return fetch(url, { headers: SS.api._headers(false) })
      .then(SS.api._handleResponse)
      .catch(SS.api._handleError);
  },

  post: function(endpoint, data) {
    return fetch(SS.api.base + endpoint, {
      method: 'POST',
      headers: SS.api._headers(true),
      body: JSON.stringify(data || {})
    })
      .then(SS.api._handleResponse)
      .catch(SS.api._handleError);
  },

  upload: function(endpoint, formData) {
    var h = {};
    var tk = localStorage.getItem('token');
    if (tk) h['Authorization'] = 'Bearer ' + tk;
    return fetch(SS.api.base + endpoint, {
      method: 'POST',
      headers: h,
      body: formData
    })
      .then(SS.api._handleResponse)
      .catch(SS.api._handleError);
  },

  // Legacy v1 endpoints
  v1: {
    get: function(endpoint) {
      return fetch('/api' + endpoint, { headers: SS.api._headers(false) })
        .then(SS.api._handleResponse)
        .catch(SS.api._handleError);
    },
    post: function(endpoint, data) {
      return fetch('/api' + endpoint, {
        method: 'POST',
        headers: SS.api._headers(true),
        body: JSON.stringify(data || {})
      })
        .then(SS.api._handleResponse)
        .catch(SS.api._handleError);
    }
  }
};
