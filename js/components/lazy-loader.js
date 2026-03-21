/**
 * ShipperShop Component — Lazy Component Loader
 * Defers loading heavy JS components until first interaction
 * Reduces initial page load by ~40KB
 */
window.SS = window.SS || {};

SS.LazyLoad = {
  _loaded: {},
  _pending: {},

  // Define components that can be lazy-loaded
  _map: {
    'EmojiPicker': '/js/components/emoji-picker.js',
    'PostCreate': '/js/components/post-create.js',
    'ImageViewer': '/js/components/image-viewer.js',
    'CommentSheet': '/js/components/comment-sheet.js',
    'ShareSheet': '/js/components/share-sheet.js',
    'SearchOverlay': '/js/components/search-overlay.js',
    'LocationPicker': '/js/components/location-picker.js',
    'MentionPicker': '/js/components/mention-picker.js',
    'Charts': '/js/components/charts.js',
    'TwoFactor': '/js/components/two-factor.js',
    'PostAnalytics': '/js/components/post-analytics.js',
  },

  // Load a component on demand
  load: function(name, callback) {
    // Already loaded
    if (SS[name] && SS.LazyLoad._loaded[name]) {
      if (callback) callback(SS[name]);
      return Promise.resolve(SS[name]);
    }

    // Already loading
    if (SS.LazyLoad._pending[name]) {
      return SS.LazyLoad._pending[name].then(function() {
        if (callback) callback(SS[name]);
        return SS[name];
      });
    }

    var src = SS.LazyLoad._map[name];
    if (!src) {
      console.warn('SS.LazyLoad: Unknown component:', name);
      return Promise.resolve(null);
    }

    SS.LazyLoad._pending[name] = new Promise(function(resolve) {
      var s = document.createElement('script');
      s.src = src;
      s.onload = function() {
        SS.LazyLoad._loaded[name] = true;
        delete SS.LazyLoad._pending[name];
        if (callback) callback(SS[name]);
        resolve(SS[name]);
      };
      s.onerror = function() {
        delete SS.LazyLoad._pending[name];
        resolve(null);
      };
      document.body.appendChild(s);
    });

    return SS.LazyLoad._pending[name];
  },

  // Preload (fetch but don't execute yet — browser cache)
  preload: function(name) {
    var src = SS.LazyLoad._map[name];
    if (!src || SS.LazyLoad._loaded[name]) return;
    var link = document.createElement('link');
    link.rel = 'prefetch';
    link.href = src;
    document.head.appendChild(link);
  },

  // Preload multiple on idle
  preloadOnIdle: function(names) {
    if (window.requestIdleCallback) {
      window.requestIdleCallback(function() {
        for (var i = 0; i < names.length; i++) {
          SS.LazyLoad.preload(names[i]);
        }
      });
    } else {
      setTimeout(function() {
        for (var i = 0; i < names.length; i++) {
          SS.LazyLoad.preload(names[i]);
        }
      }, 3000);
    }
  }
};
