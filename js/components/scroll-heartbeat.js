/**
 * ShipperShop Component — Scroll To Top + Online Heartbeat
 */
window.SS = window.SS || {};

// Scroll to top button
SS.ScrollTop = {
  _el: null,

  init: function() {
    var btn = document.createElement('button');
    btn.id = 'ss-scroll-top';
    btn.style.cssText = 'position:fixed;bottom:80px;right:16px;width:40px;height:40px;border-radius:50%;background:var(--primary);color:#fff;border:none;cursor:pointer;box-shadow:var(--shadow-md);z-index:100;display:none;align-items:center;justify-content:center;font-size:16px;transition:opacity .2s,transform .2s';
    btn.innerHTML = '<i class="fa-solid fa-chevron-up"></i>';
    btn.onclick = function() { window.scrollTo({top: 0, behavior: 'smooth'}); };
    document.body.appendChild(btn);
    SS.ScrollTop._el = btn;

    window.addEventListener('scroll', SS.utils ? SS.utils.throttle(function() {
      btn.style.display = window.scrollY > 400 ? 'flex' : 'none';
    }, 200) : function() {
      btn.style.display = window.scrollY > 400 ? 'flex' : 'none';
    });
  }
};

// Online heartbeat — update last_active every 2 min
SS.Heartbeat = {
  _timer: null,

  init: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    SS.Heartbeat._ping();
    SS.Heartbeat._timer = setInterval(SS.Heartbeat._ping, 120000);

    // Mark offline on page hide
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        // Beacon to mark offline
        if (navigator.sendBeacon) {
          navigator.sendBeacon('/api/v2/analytics.php', JSON.stringify({page: '_offline', referrer: ''}));
        }
      } else {
        SS.Heartbeat._ping();
      }
    });
  },

  _ping: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    var tk = localStorage.getItem('token');
    if (!tk) return;
    fetch('/api/v2/analytics.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer ' + tk},
      body: JSON.stringify({page: window.location.pathname.replace('/', '').replace('.html', '') || 'index'})
    }).catch(function() {});
  },

  stop: function() {
    if (SS.Heartbeat._timer) {
      clearInterval(SS.Heartbeat._timer);
      SS.Heartbeat._timer = null;
    }
  }
};

// Auto-init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    SS.ScrollTop.init();
    SS.Heartbeat.init();
  });
} else {
  SS.ScrollTop.init();
  SS.Heartbeat.init();
}
