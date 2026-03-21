/**
 * ShipperShop Component — Real-time SSE Client
 * Connects to Server-Sent Events stream for instant notifications + messages
 * Auto-reconnects, falls back to polling if SSE fails
 * Uses: SS.store, SS.NotifSound, SS.ui
 */
window.SS = window.SS || {};

SS.Realtime = {
  _es: null,
  _retries: 0,
  _maxRetries: 5,
  _listeners: {},

  start: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    if (SS.Realtime._es) SS.Realtime.stop();

    var token = localStorage.getItem('token');
    if (!token) return;

    try {
      SS.Realtime._es = new EventSource('/api/v2/sse.php?token=' + encodeURIComponent(token));

      SS.Realtime._es.addEventListener('connected', function(e) {
        SS.Realtime._retries = 0;
        console.log('[SSE] Connected');
      });

      SS.Realtime._es.addEventListener('notification', function(e) {
        try {
          var data = JSON.parse(e.data);
          // Play sound
          if (SS.NotifSound) SS.NotifSound.play('notification');
          // Show toast
          if (SS.ui) SS.ui.toast(data.title || 'Thông báo mới', 'info', 4000);
          // Update bell badge
          var bells = document.querySelectorAll('.ss-notif-badge');
          for (var i = 0; i < bells.length; i++) {
            var cur = parseInt(bells[i].textContent) || 0;
            bells[i].textContent = cur + 1;
            bells[i].style.display = 'flex';
          }
          // Dispatch custom event
          SS.Realtime._dispatch('notification', data);
        } catch(err) {}
      });

      SS.Realtime._es.addEventListener('message', function(e) {
        try {
          var data = JSON.parse(e.data);
          if (SS.NotifSound) SS.NotifSound.play('message');
          // Update message badge
          var msgBadges = document.querySelectorAll('.ss-msg-badge');
          for (var i = 0; i < msgBadges.length; i++) {
            var cur = parseInt(msgBadges[i].textContent) || 0;
            msgBadges[i].textContent = cur + 1;
            msgBadges[i].style.display = 'flex';
          }
          SS.Realtime._dispatch('message', data);
        } catch(err) {}
      });

      SS.Realtime._es.addEventListener('reconnect', function() {
        // Server asked us to reconnect
        SS.Realtime.stop();
        setTimeout(function() { SS.Realtime.start(); }, 2000);
      });

      SS.Realtime._es.addEventListener('error', function() {
        SS.Realtime._retries++;
        if (SS.Realtime._retries >= SS.Realtime._maxRetries) {
          console.log('[SSE] Max retries reached, falling back to polling');
          SS.Realtime.stop();
          return;
        }
        // EventSource auto-reconnects, but we track retries
      });

    } catch(err) {
      console.log('[SSE] Not supported, using polling');
    }
  },

  stop: function() {
    if (SS.Realtime._es) {
      SS.Realtime._es.close();
      SS.Realtime._es = null;
    }
  },

  // Subscribe to events
  on: function(event, callback) {
    if (!SS.Realtime._listeners[event]) SS.Realtime._listeners[event] = [];
    SS.Realtime._listeners[event].push(callback);
  },

  _dispatch: function(event, data) {
    var cbs = SS.Realtime._listeners[event] || [];
    for (var i = 0; i < cbs.length; i++) {
      try { cbs[i](data); } catch(e) {}
    }
  },

  isConnected: function() {
    return SS.Realtime._es && SS.Realtime._es.readyState === EventSource.OPEN;
  }
};
