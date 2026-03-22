/**
 * ShipperShop Component — Intersection observer lazy image loader
 */
window.SS = window.SS || {};

SS.LazyImages = {
  init: function() {
    if (!window.IntersectionObserver) return;
    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          var img = entry.target;
          if (img.dataset.src) { img.src = img.dataset.src; img.removeAttribute('data-src'); }
          observer.unobserve(img);
        }
      });
    }, {rootMargin: '200px'});
    document.querySelectorAll('img[data-src]').forEach(function(img) { observer.observe(img); });
  }
};
