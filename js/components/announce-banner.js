/**
 * ShipperShop Component — Announcement Banner
 * Shows site-wide announcements at top of page
 * Auto-fetches active banners, dismissible per session
 */
window.SS = window.SS || {};

SS.AnnounceBanner = {
  _dismissed: {},

  init: function() {
    SS.api.get('/announcement-banner.php').then(function(d) {
      var banners = d.data || [];
      if (!banners.length) return;

      var container = document.getElementById('ss-banners');
      if (!container) {
        container = document.createElement('div');
        container.id = 'ss-banners';
        container.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:9999';
        document.body.prepend(container);
      }

      var colors = {
        info: {bg: '#EDE9FE', color: '#7C3AED', icon: 'fa-circle-info'},
        warning: {bg: '#FEF3C7', color: '#D97706', icon: 'fa-triangle-exclamation'},
        success: {bg: '#DCFCE7', color: '#16A34A', icon: 'fa-circle-check'},
        danger: {bg: '#FEE2E2', color: '#DC2626', icon: 'fa-circle-xmark'},
        promo: {bg: '#FFF3EF', color: '#EE4D2D', icon: 'fa-gift'}
      };

      var html = '';
      for (var i = 0; i < banners.length; i++) {
        var b = banners[i];
        if (SS.AnnounceBanner._dismissed[b.id]) continue;
        var c = colors[b.type] || colors.info;
        html += '<div id="banner-' + b.id + '" style="background:' + c.bg + ';color:' + c.color + ';padding:8px 16px;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;border-bottom:1px solid ' + c.color + '20">'
          + '<i class="fa-solid ' + c.icon + '"></i>'
          + '<div style="flex:1">' + SS.utils.esc(b.text) + (b.link ? ' <a href="' + SS.utils.esc(b.link) + '" style="color:' + c.color + ';text-decoration:underline;font-weight:700">Xem thêm</a>' : '') + '</div>'
          + '<button onclick="SS.AnnounceBanner.dismiss(' + b.id + ')" style="border:none;background:none;color:' + c.color + ';cursor:pointer;padding:4px;font-size:16px"><i class="fa-solid fa-xmark"></i></button>'
          + '</div>';
      }
      container.innerHTML = html;

      // Adjust body padding
      if (html) {
        setTimeout(function() {
          document.body.style.paddingTop = (parseInt(getComputedStyle(document.body).paddingTop) + container.offsetHeight) + 'px';
        }, 50);
      }
    }).catch(function() {});
  },

  dismiss: function(id) {
    SS.AnnounceBanner._dismissed[id] = true;
    var el = document.getElementById('banner-' + id);
    if (el) el.remove();
    var container = document.getElementById('ss-banners');
    if (container && !container.children.length) container.remove();
  }
};
