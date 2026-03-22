/**
 * ShipperShop Component — Network Status
 * Detect online/offline and show banner + auto-retry
 */
window.SS = window.SS || {};

SS.NetworkStatus = {
  init: function() {
    window.addEventListener('online', function() { SS.ui.toast('Da ket noi lai!', 'success', 2000); SS.NetworkStatus._hide(); });
    window.addEventListener('offline', function() { SS.NetworkStatus._showBanner(); });
    if (!navigator.onLine) SS.NetworkStatus._showBanner();
  },
  _showBanner: function() {
    var el = document.getElementById('ss-offline-banner');
    if (el) { el.style.display = 'flex'; return; }
    var div = document.createElement('div');
    div.id = 'ss-offline-banner';
    div.style.cssText = 'position:fixed;top:0;left:0;right:0;padding:8px;background:var(--danger);color:#fff;text-align:center;z-index:9999;font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px';
    div.innerHTML = '📡 Mat ket noi mang';
    document.body.appendChild(div);
  },
  _hide: function() {
    var el = document.getElementById('ss-offline-banner');
    if (el) el.style.display = 'none';
  },
  isOnline: function() { return navigator.onLine; }
};
