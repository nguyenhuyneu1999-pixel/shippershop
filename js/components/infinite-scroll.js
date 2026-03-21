/**
 * ShipperShop Component — Infinite Scroll
 * Reusable IntersectionObserver-based infinite loader
 * Usage: SS.InfiniteScroll.attach('container-id', loadMoreFn)
 */
window.SS = window.SS || {};

SS.InfiniteScroll = {
  _observers: {},

  // Attach infinite scroll to container
  attach: function(containerId, loadMore, opts) {
    opts = opts || {};
    var container = document.getElementById(containerId);
    if (!container) return;

    // Create sentinel element
    var sentinel = document.createElement('div');
    sentinel.id = containerId + '-sentinel';
    sentinel.style.cssText = 'height:1px;width:100%';
    container.appendChild(sentinel);

    var loading = false;
    var done = false;

    var observer = new IntersectionObserver(function(entries) {
      if (entries[0].isIntersecting && !loading && !done) {
        loading = true;
        // Show loading indicator
        var loader = document.createElement('div');
        loader.id = containerId + '-loader';
        loader.style.cssText = 'text-align:center;padding:16px';
        loader.innerHTML = '<div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div>';
        container.insertBefore(loader, sentinel);

        loadMore(function(hasMore) {
          loading = false;
          var ldr = document.getElementById(containerId + '-loader');
          if (ldr) ldr.remove();
          if (!hasMore) {
            done = true;
            observer.disconnect();
            sentinel.remove();
            // Show end message
            if (opts.endMessage !== false) {
              var end = document.createElement('div');
              end.style.cssText = 'text-align:center;padding:16px;color:var(--text-muted);font-size:13px';
              end.textContent = opts.endMessage || 'Đã hết nội dung';
              container.appendChild(end);
            }
          }
        });
      }
    }, {rootMargin: opts.rootMargin || '200px'});

    observer.observe(sentinel);
    SS.InfiniteScroll._observers[containerId] = observer;

    return {
      destroy: function() { observer.disconnect(); sentinel.remove(); delete SS.InfiniteScroll._observers[containerId]; },
      reset: function() { done = false; loading = false; if (!sentinel.parentNode) container.appendChild(sentinel); observer.observe(sentinel); }
    };
  },

  // Destroy observer
  destroy: function(containerId) {
    if (SS.InfiniteScroll._observers[containerId]) {
      SS.InfiniteScroll._observers[containerId].disconnect();
      delete SS.InfiniteScroll._observers[containerId];
    }
    var sentinel = document.getElementById(containerId + '-sentinel');
    if (sentinel) sentinel.remove();
  }
};
