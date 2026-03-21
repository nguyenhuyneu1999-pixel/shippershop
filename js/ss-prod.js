/**
 * ShipperShop Production Loader
 * Loads minified bundle + page-specific modules
 * Usage: <script src="/js/ss-prod.js" data-page="feed"></script>
 */
(function() {
  var script = document.currentScript;
  var page = script ? script.getAttribute('data-page') : '';

  // Page module map
  var pageMap = {
    'feed': '/js/pages/feed.js',
    'user-profile': '/js/pages/user-profile.js',
    'messages': '/js/pages/messages.js',
    'post-detail': '/js/pages/post-detail.js',
    'groups': '/js/pages/groups.js',
    'people': '/js/pages/people.js',
    'wallet': '/js/pages/wallet.js',
    'traffic': '/js/pages/traffic.js',
    'marketplace': '/js/pages/marketplace.js',
    'leaderboard': '/js/pages/leaderboard.js',
    'activity-log': '/js/pages/activity-log.js',
    'group-detail': '/js/pages/group-detail.js',
    'profile-settings': '/js/pages/profile-settings.js',
    'listing-detail': '/js/pages/listing-detail.js',
    'map': '/js/pages/map-page.js',
    'admin': '/js/pages/admin.js',
    'scheduled': '/js/pages/scheduled.js',
    'bookmarks': '/js/pages/bookmarks.js',
    'auth': '/js/pages/auth.js',
    'system-config': '/js/pages/system-config.js',
    'content-stats': '/js/pages/content-stats.js',
    'preferences': '/js/pages/preferences.js',
    'admin-logs': '/js/pages/admin-logs.js',
    'admin-mod': '/js/pages/admin-mod.js',
    'settings': '/js/pages/settings.js',
    'account-settings': '/js/pages/account-settings.js'
  };

  // Init common components after bundle loads
  function initCommon() {
    if (typeof SS === 'undefined') return;
    // Notification bell
    if (SS.NotifBell && SS.store && SS.store.isLoggedIn()) {
      var nb = document.getElementById('ss-notif-bell');
      if (nb) SS.NotifBell.init('ss-notif-bell');
    }
    // Video autoplay
    if (SS.VideoPlayer) SS.VideoPlayer.init();
    // Toast bridge
    if (typeof window.toast === 'undefined' && SS.ui) {
      window.toast = function(m, dur) { SS.ui.toast(m, 'info', dur || 3000); };
    }
    // Image viewer bridge
    if (SS.ImageViewer) {
      window.openLightbox = function(src, gallery) { SS.ImageViewer.open(src, gallery); };
    }
    // FAB bridge for post create
    if (SS.PostCreate && SS.store && SS.store.isLoggedIn()) {
      var fab = document.querySelector('.fab-btn,[onclick*="openPostModal"]');
      if (fab) {
        fab.onclick = function(e) { e.preventDefault(); SS.PostCreate.open(); };
      }
    }
  }

  function loadScript(src, cb) {
    var s = document.createElement('script');
    s.src = src;
    s.onload = cb || function() {};
    s.onerror = cb || function() {};
    document.body.appendChild(s);
  }

  // Load page module after common init
  function loadPage() {
    initCommon();
    if (page && pageMap[page]) {
      loadScript(pageMap[page]);
    }
  }

  // Check if bundle already loaded
  if (typeof SS !== 'undefined' && SS.api) {
    loadPage();
  } else {
    loadScript('/js/ss-bundle.min.js', loadPage);
  }
})();
