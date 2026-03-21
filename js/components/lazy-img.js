/**
 * ShipperShop Component — Lazy Image Loader
 * Progressive loading: tiny blur placeholder → full image on viewport entry
 * Falls back to loading="lazy" if IntersectionObserver not available
 */
window.SS = window.SS || {};

SS.LazyImg = {
  _observer: null,

  init: function() {
    if (!window.IntersectionObserver) return; // Fallback: browser native lazy

    SS.LazyImg._observer = new IntersectionObserver(function(entries) {
      for (var i = 0; i < entries.length; i++) {
        if (entries[i].isIntersecting) {
          var img = entries[i].target;
          SS.LazyImg._load(img);
          SS.LazyImg._observer.unobserve(img);
        }
      }
    }, {rootMargin: '200px'});

    SS.LazyImg.scan();
  },

  // Scan DOM for lazy images
  scan: function() {
    if (!SS.LazyImg._observer) return;
    var imgs = document.querySelectorAll('img[data-src]:not([data-loaded])');
    for (var i = 0; i < imgs.length; i++) {
      SS.LazyImg._observer.observe(imgs[i]);
    }
  },

  _load: function(img) {
    var src = img.getAttribute('data-src');
    if (!src) return;

    // Create temp image to preload
    var tmp = new Image();
    tmp.onload = function() {
      img.src = src;
      img.removeAttribute('data-src');
      img.setAttribute('data-loaded', 'true');
      img.style.opacity = '1';
      img.style.filter = 'none';
    };
    tmp.onerror = function() {
      img.src = '/assets/img/defaults/no-posts.svg';
      img.setAttribute('data-loaded', 'error');
    };
    tmp.src = src;
  },

  // Create image HTML with lazy loading
  html: function(src, opts) {
    opts = opts || {};
    var cls = opts.class || '';
    var style = opts.style || '';
    var alt = opts.alt || '';
    return '<img data-src="' + SS.utils.esc(src) + '" class="' + cls + '" style="opacity:.6;filter:blur(4px);transition:opacity .3s,filter .3s;' + style + '" alt="' + SS.utils.esc(alt) + '" loading="lazy">';
  }
};

// Auto-init + rescan on DOM changes
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { SS.LazyImg.init(); });
} else {
  SS.LazyImg.init();
}

// MutationObserver to auto-scan new images
if (window.MutationObserver) {
  var mo = new MutationObserver(function() { SS.LazyImg.scan(); });
  if (document.body) {
    mo.observe(document.body, {childList: true, subtree: true});
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      mo.observe(document.body, {childList: true, subtree: true});
    });
  }
}
