/**
 * ShipperShop Component — FAB Menu
 * Floating action button with expandable menu
 */
window.SS = window.SS || {};

SS.FABMenu = {
  _open: false,
  _el: null,

  init: function(actions) {
    // Default actions
    if (!actions) {
      actions = [
        {icon: 'fa-pen', label: 'Đăng bài', color: '#7C3AED', onClick: function() { if (SS.PostCreate) SS.PostCreate.open(); else window.location.href = '/index.html'; }},
        {icon: 'fa-store', label: 'Đăng bán', color: '#EE4D2D', onClick: function() { if (SS.MarketplacePage) SS.MarketplacePage.openCreate(); else window.location.href = '/marketplace.html'; }},
        {icon: 'fa-triangle-exclamation', label: 'Giao thông', color: '#f59e0b', onClick: function() { if (SS.TrafficPage) SS.TrafficPage.openCreateAlert(); else window.location.href = '/traffic.html'; }}
      ];
    }

    // Don't show on login/register/admin
    var path = window.location.pathname;
    if (path.indexOf('login') > -1 || path.indexOf('register') > -1 || path.indexOf('admin') > -1) return;
    if (!SS.store || !SS.store.isLoggedIn()) return;

    var container = document.createElement('div');
    container.id = 'ss-fab-container';
    container.style.cssText = 'position:fixed;bottom:76px;right:16px;z-index:200;display:flex;flex-direction:column;align-items:flex-end;gap:8px';

    // Menu items (hidden by default)
    var menuHtml = '<div id="ss-fab-menu" style="display:none;flex-direction:column;gap:8px;margin-bottom:8px">';
    for (var i = actions.length - 1; i >= 0; i--) {
      var a = actions[i];
      var itemId = 'fab-item-' + i;
      menuHtml += '<div id="' + itemId + '" style="display:flex;align-items:center;gap:8px;justify-content:flex-end;animation:slideRight .2s;cursor:pointer">'
        + '<span style="background:var(--card);padding:4px 10px;border-radius:6px;font-size:12px;font-weight:500;box-shadow:var(--shadow-sm);white-space:nowrap">' + a.label + '</span>'
        + '<div style="width:44px;height:44px;border-radius:50%;background:' + a.color + ';color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-md)">'
        + '<i class="fa-solid ' + a.icon + '"></i></div></div>';
    }
    menuHtml += '</div>';

    // Main FAB button
    menuHtml += '<button id="ss-fab-btn" style="width:56px;height:56px;border-radius:50%;background:var(--primary);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 16px rgba(124,58,237,.4);font-size:22px;transition:transform .3s;display:flex;align-items:center;justify-content:center">'
      + '<i class="fa-solid fa-plus"></i></button>';

    container.innerHTML = menuHtml;
    document.body.appendChild(container);
    SS.FABMenu._el = container;

    // Bind main button
    document.getElementById('ss-fab-btn').onclick = function() { SS.FABMenu.toggle(); };

    // Bind menu items
    for (var j = 0; j < actions.length; j++) {
      (function(idx) {
        var item = document.getElementById('fab-item-' + idx);
        if (item) {
          item.onclick = function() {
            SS.FABMenu.close();
            actions[idx].onClick();
          };
        }
      })(j);
    }
  },

  toggle: function() {
    SS.FABMenu._open = !SS.FABMenu._open;
    var menu = document.getElementById('ss-fab-menu');
    var btn = document.getElementById('ss-fab-btn');
    if (menu) menu.style.display = SS.FABMenu._open ? 'flex' : 'none';
    if (btn) btn.style.transform = SS.FABMenu._open ? 'rotate(45deg)' : 'rotate(0)';

    // Close on outside click
    if (SS.FABMenu._open) {
      setTimeout(function() {
        var handler = function(e) {
          if (!SS.FABMenu._el.contains(e.target)) {
            SS.FABMenu.close();
            document.removeEventListener('click', handler);
          }
        };
        document.addEventListener('click', handler);
      }, 0);
    }
  },

  close: function() {
    SS.FABMenu._open = false;
    var menu = document.getElementById('ss-fab-menu');
    var btn = document.getElementById('ss-fab-btn');
    if (menu) menu.style.display = 'none';
    if (btn) btn.style.transform = 'rotate(0)';
  }
};
