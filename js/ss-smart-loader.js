/**
 * ShipperShop Smart Loader v2
 * Loads critical bundle immediately (38KB), lazy bundle on idle (90KB)
 * Total: same as full bundle, but initial load is 70% smaller
 * Usage: <script src="/js/ss-smart-loader.js" data-page="feed"></script>
 */
(function() {
  var script = document.currentScript;
  var page = script ? script.getAttribute('data-page') : '';
  var lazyLoaded = false;

  var pageMap = {
    'feed': '/js/pages/feed.js',
    'user-profile': '/js/pages/user-profile.js',
    'messages': '/js/pages/messages.js',
    'post-detail': '/js/pages/post-detail.js',
    'groups': '/js/pages/groups.js',
    'group-detail': '/js/pages/group-detail.js',
    'people': '/js/pages/people.js',
    'wallet': '/js/pages/wallet.js',
    'traffic': '/js/pages/traffic.js',
    'marketplace': '/js/pages/marketplace.js',
    'leaderboard': '/js/pages/leaderboard.js',
    'activity-log': '/js/pages/activity-log.js',
    'profile-settings': '/js/pages/profile-settings.js',
    'listing-detail': '/js/pages/listing-detail.js',
    'map': '/js/pages/map-page.js',
    'admin': '/js/pages/admin.js',
    'scheduled': '/js/pages/scheduled.js',
    'bookmarks': '/js/pages/bookmarks.js',
    'auth': '/js/pages/auth.js'
  };

  function loadScript(src, cb) {
    var s = document.createElement('script');
    s.src = src;
    s.onload = cb || function() {};
    s.onerror = cb || function() {};
    document.body.appendChild(s);
  }

  function initCommon() {
    if (typeof SS === 'undefined') return;
    if (SS.NotifBell && SS.store && SS.store.isLoggedIn()) {
      var nb = document.getElementById('ss-notif-bell');
      if (nb) SS.NotifBell.init('ss-notif-bell');
    }
    if (SS.VideoPlayer) SS.VideoPlayer.init();
    if (typeof window.toast === 'undefined' && SS.ui) {
      window.toast = function(m, dur) { SS.ui.toast(m, 'info', dur || 3000); };
    }
  }

  function loadPage() {
    initCommon();
    if (page && pageMap[page]) {
      loadScript(pageMap[page]);
    }
  }

  function loadLazy() {
    if (lazyLoaded) return;
    lazyLoaded = true;
    loadScript('/js/ss-lazy.min.js');
  }

  // Load critical bundle immediately
  loadScript('/js/ss-critical.min.js', function() {
    loadPage();

    // Load lazy bundle on idle or after 3s
    if (window.requestIdleCallback) {
      requestIdleCallback(loadLazy, {timeout: 3000});
    } else {
      setTimeout(loadLazy, 2000);
    }
  });

  // Also load lazy on first user interaction
  var interactionEvents = ['scroll', 'click', 'touchstart', 'mousemove'];
  function onInteraction() {
    loadLazy();
    for (var i = 0; i < interactionEvents.length; i++) {
      document.removeEventListener(interactionEvents[i], onInteraction);
    }
  }
  for (var i = 0; i < interactionEvents.length; i++) {
    document.addEventListener(interactionEvents[i], onInteraction, {once: true, passive: true});
  }
})();
