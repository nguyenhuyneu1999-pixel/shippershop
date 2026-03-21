/**
 * ShipperShop Component — Connection Status
 * Shows offline/reconnecting banner, detects network changes
 */
window.SS = window.SS || {};

SS.ConnectionStatus = {
  _el: null,
  _online: true,

  init: function() {
    SS.ConnectionStatus._online = navigator.onLine;

    window.addEventListener('online', function() {
      SS.ConnectionStatus._online = true;
      SS.ConnectionStatus._update();
      if (SS.ui) SS.ui.toast('Đã kết nối lại!', 'success', 2000);
    });

    window.addEventListener('offline', function() {
      SS.ConnectionStatus._online = false;
      SS.ConnectionStatus._update();
    });

    // Initial check
    if (!navigator.onLine) SS.ConnectionStatus._update();
  },

  _update: function() {
    if (!SS.ConnectionStatus._online) {
      if (!SS.ConnectionStatus._el) {
        var el = document.createElement('div');
        el.id = 'ss-offline-bar';
        el.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:10000;background:#ef4444;color:#fff;text-align:center;padding:6px 16px;font-size:12px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:6px';
        el.innerHTML = '<i class="fa-solid fa-wifi-slash" style="font-size:14px"></i> Mất kết nối mạng';
        document.body.appendChild(el);
        // Push body down
        document.body.style.paddingTop = (parseInt(getComputedStyle(document.body).paddingTop) + 32) + 'px';
        SS.ConnectionStatus._el = el;
      }
    } else {
      if (SS.ConnectionStatus._el) {
        document.body.style.paddingTop = (parseInt(getComputedStyle(document.body).paddingTop) - 32) + 'px';
        SS.ConnectionStatus._el.remove();
        SS.ConnectionStatus._el = null;
      }
    }
  },

  isOnline: function() {
    return SS.ConnectionStatus._online;
  }
};

// Auto-init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { SS.ConnectionStatus.init(); });
} else {
  SS.ConnectionStatus.init();
}
