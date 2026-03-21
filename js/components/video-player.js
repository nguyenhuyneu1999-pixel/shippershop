/**
 * ShipperShop Component — Video Player
 * Autoplay muted when scrolled into view, pause when out
 */
window.SS = window.SS || {};

SS.VideoPlayer = {
  _observer: null,

  init: function() {
    if (!('IntersectionObserver' in window)) return;
    if (SS.VideoPlayer._observer) SS.VideoPlayer._observer.disconnect();

    SS.VideoPlayer._observer = new IntersectionObserver(function(entries) {
      for (var i = 0; i < entries.length; i++) {
        var entry = entries[i];
        var video = entry.target;
        if (entry.isIntersecting) {
          video.muted = true;
          video.play().catch(function() {});
        } else {
          video.pause();
        }
      }
    }, {threshold: 0.5});

    SS.VideoPlayer.observe();
  },

  observe: function() {
    if (!SS.VideoPlayer._observer) return;
    var videos = document.querySelectorAll('video[data-autoplay]');
    for (var i = 0; i < videos.length; i++) {
      SS.VideoPlayer._observer.observe(videos[i]);
    }
  },

  // Call after new content loaded (infinite scroll)
  refresh: function() {
    SS.VideoPlayer.observe();
  }
};
