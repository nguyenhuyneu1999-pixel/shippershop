/**
 * ShipperShop Component — PWA Install Prompt
 * Shows install banner when app is installable, remembers dismissal
 */
window.SS = window.SS || {};

SS.PWAInstall = {
  _deferredPrompt: null,

  init: function() {
    // Already installed or dismissed recently
    if (window.matchMedia('(display-mode: standalone)').matches) return;
    var dismissed = localStorage.getItem('ss_pwa_dismissed');
    if (dismissed && Date.now() - parseInt(dismissed) < 7 * 86400000) return;

    window.addEventListener('beforeinstallprompt', function(e) {
      e.preventDefault();
      SS.PWAInstall._deferredPrompt = e;
      SS.PWAInstall._showBanner();
    });
  },

  _showBanner: function() {
    // Don't show on login/register
    if (window.location.pathname.indexOf('login') > -1 || window.location.pathname.indexOf('register') > -1) return;

    var banner = document.createElement('div');
    banner.id = 'ss-pwa-banner';
    banner.style.cssText = 'position:fixed;bottom:70px;left:50%;transform:translateX(-50%);z-index:999;background:var(--card);padding:12px 16px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.15);display:flex;align-items:center;gap:12px;max-width:360px;width:90%;animation:slideUp .3s';

    banner.innerHTML = '<img src="/icons/icon-48.png" style="width:40px;height:40px;border-radius:10px;flex-shrink:0" alt="">'
      + '<div style="flex:1;min-width:0">'
      + '<div style="font-weight:700;font-size:13px;color:var(--text)">Cài đặt ShipperShop</div>'
      + '<div style="font-size:11px;color:var(--text-muted)">Thêm vào màn hình chính</div></div>'
      + '<button onclick="SS.PWAInstall.install()" style="background:var(--primary);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-weight:700;font-size:12px;cursor:pointer;white-space:nowrap">Cài đặt</button>'
      + '<button onclick="SS.PWAInstall.dismiss()" style="background:none;border:none;color:var(--text-muted);font-size:18px;cursor:pointer;padding:4px">✕</button>';

    document.body.appendChild(banner);
  },

  install: function() {
    if (!SS.PWAInstall._deferredPrompt) return;
    SS.PWAInstall._deferredPrompt.prompt();
    SS.PWAInstall._deferredPrompt.userChoice.then(function(choice) {
      if (choice.outcome === 'accepted') {
        if (SS.ui) SS.ui.toast('Đã cài đặt!', 'success');
      }
      SS.PWAInstall._deferredPrompt = null;
      SS.PWAInstall.dismiss();
    });
  },

  dismiss: function() {
    var banner = document.getElementById('ss-pwa-banner');
    if (banner) banner.remove();
    localStorage.setItem('ss_pwa_dismissed', Date.now().toString());
  }
};

// Auto-init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { SS.PWAInstall.init(); });
} else {
  SS.PWAInstall.init();
}
