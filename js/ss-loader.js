/**
 * ShipperShop JS Loader
 * Load all core + component modules via single script tag
 * Usage: <script src="/js/ss-loader.js" data-components="post-card,comment-sheet" data-pages="feed"></script>
 */
(function() {
  var script = document.currentScript;
  var components = (script.getAttribute('data-components') || '').split(',').filter(Boolean);
  var pages = (script.getAttribute('data-pages') || '').split(',').filter(Boolean);

  // Always load core
  var core = ['/js/core/utils.js', '/js/core/store.js', '/js/core/ui.js', '/js/core/api.js'];

  // Component map
  var compMap = {
    'post-card': '/js/components/post-card.js',
    'comment-sheet': '/js/components/comment-sheet.js',
    'image-viewer': '/js/components/image-viewer.js',
    'notification-bell': '/js/components/notification-bell.js',
    'search-overlay': '/js/components/search-overlay.js',
    'upload': '/js/components/upload.js',
    'video-player': '/js/components/video-player.js',
    'location-picker': '/js/components/location-picker.js',
    'gamification': '/js/components/gamification.js',
    'emoji-picker': '/js/components/emoji-picker.js',
    'post-create': '/js/components/post-create.js',
    'user-card': '/js/components/user-card.js'
  };

  // Page map
  var pageMap = {
    'feed': '/js/pages/feed.js',
    'user-profile': '/js/pages/user-profile.js',
    'messages': '/js/pages/messages.js'
  };

  var queue = core.slice();
  for (var i = 0; i < components.length; i++) {
    if (compMap[components[i]]) queue.push(compMap[components[i]]);
  }
  for (var j = 0; j < pages.length; j++) {
    if (pageMap[pages[j]]) queue.push(pageMap[pages[j]]);
  }

  // Load scripts sequentially (order matters)
  var idx = 0;
  function loadNext() {
    if (idx >= queue.length) {
      // All loaded — init common components
      if (typeof SS !== 'undefined' && SS.store && SS.store.isLoggedIn() && SS.NotifBell) {
        var nb = document.getElementById('ss-notif-bell');
        if (nb) SS.NotifBell.init('ss-notif-bell');
      }
      if (typeof SS !== 'undefined' && SS.VideoPlayer) SS.VideoPlayer.init();
      if (typeof window.toast === 'undefined' && typeof SS !== 'undefined' && SS.ui) {
        window.toast = function(m, dur) { SS.ui.toast(m, 'info', dur || 3000); };
      }
      return;
    }
    var s = document.createElement('script');
    s.src = queue[idx];
    s.onload = function() { idx++; loadNext(); };
    s.onerror = function() { idx++; loadNext(); };
    document.body.appendChild(s);
  }
  loadNext();
})();
