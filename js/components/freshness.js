/**
 * ShipperShop Component — Freshness Indicator
 * Shows "Mới" badge on recent posts and "Đã sửa" on edited posts
 */
window.SS = window.SS || {};

SS.Freshness = {

  // Get badge HTML for a post
  badge: function(createdAt, editedAt) {
    if (!createdAt) return '';
    var now = Date.now();
    var created = new Date(createdAt).getTime();
    var ageMinutes = (now - created) / 60000;

    // Edited indicator
    if (editedAt) {
      return '<span style="font-size:10px;color:var(--text-muted);font-style:italic" title="Đã sửa ' + SS.utils.ago(editedAt) + '">· đã sửa</span>';
    }

    // New badge (< 30 minutes)
    if (ageMinutes < 30) {
      return '<span style="font-size:9px;font-weight:700;color:#fff;background:#22c55e;padding:1px 5px;border-radius:3px;margin-left:4px;animation:freshPulse 2s infinite">MỚI</span>';
    }

    // Hot badge (< 2 hours, lots of engagement)
    return '';
  },

  // Add CSS animation if not exists
  _ensureCSS: function() {
    if (document.getElementById('ss-fresh-css')) return;
    var style = document.createElement('style');
    style.id = 'ss-fresh-css';
    style.textContent = '@keyframes freshPulse{0%,100%{opacity:1}50%{opacity:.6}}';
    document.head.appendChild(style);
  }
};

SS.Freshness._ensureCSS();
