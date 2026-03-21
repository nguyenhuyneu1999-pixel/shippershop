/**
 * ShipperShop Component — Swipe Gestures
 * Detect swipe directions on touch devices for navigation
 * Usage: SS.Swipe.on(element, {left: fn, right: fn, up: fn, down: fn})
 */
window.SS = window.SS || {};

SS.Swipe = {

  on: function(el, handlers, opts) {
    if (typeof el === 'string') el = document.getElementById(el);
    if (!el) return;

    opts = opts || {};
    var threshold = opts.threshold || 50;
    var startX, startY, startTime;

    el.addEventListener('touchstart', function(e) {
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
      startTime = Date.now();
    }, {passive: true});

    el.addEventListener('touchend', function(e) {
      if (!startX || !startY) return;
      var dx = e.changedTouches[0].clientX - startX;
      var dy = e.changedTouches[0].clientY - startY;
      var dt = Date.now() - startTime;

      // Must be fast enough (< 500ms) and far enough
      if (dt > 500) return;
      var absDx = Math.abs(dx);
      var absDy = Math.abs(dy);

      if (absDx < threshold && absDy < threshold) return;

      if (absDx > absDy) {
        // Horizontal swipe
        if (dx > 0 && handlers.right) handlers.right(dx);
        else if (dx < 0 && handlers.left) handlers.left(dx);
      } else {
        // Vertical swipe
        if (dy > 0 && handlers.down) handlers.down(dy);
        else if (dy < 0 && handlers.up) handlers.up(dy);
      }

      startX = null;
      startY = null;
    }, {passive: true});
  },

  // Common: swipe right to go back
  enableBackSwipe: function() {
    SS.Swipe.on(document.body, {
      right: function(dx) {
        if (dx > 80 && window.history.length > 1) {
          window.history.back();
        }
      }
    }, {threshold: 80});
  }
};
