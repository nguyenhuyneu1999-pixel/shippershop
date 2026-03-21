/**
 * ShipperShop Component — Maintenance Mode
 * Shows fullscreen maintenance overlay when admin enables it
 * Uses: SS.api
 */
window.SS = window.SS || {};

SS.Maintenance = {

  check: function() {
    SS.api.get('/maintenance.php').then(function(d) {
      var data = d.data || {};
      if (!data.active) return;

      // Show maintenance overlay
      var overlay = document.createElement('div');
      overlay.id = 'ss-maintenance';
      overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:var(--bg);display:flex;align-items:center;justify-content:center;flex-direction:column;padding:32px;text-align:center';
      overlay.innerHTML = '<div style="font-size:64px;margin-bottom:16px">🔧</div>'
        + '<div style="font-size:24px;font-weight:800;color:var(--text);margin-bottom:8px">Bao tri he thong</div>'
        + '<div style="font-size:14px;color:var(--text-muted);max-width:400px;line-height:1.6">' + SS.utils.esc(data.message || '') + '</div>'
        + (data.eta ? '<div style="margin-top:16px;padding:8px 16px;background:var(--primary-light);border-radius:8px;color:var(--primary);font-weight:600">Du kien hoan thanh: ' + SS.utils.esc(data.eta) + '</div>' : '')
        + '<button style="margin-top:24px;padding:8px 20px;background:var(--primary);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px" onclick="location.reload()">Thu lai</button>';
      document.body.appendChild(overlay);
    }).catch(function() {});
  }
};
