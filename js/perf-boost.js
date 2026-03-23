// ShipperShop Performance Boost
// IntersectionObserver lazy load + prefetch + scroll optimization

(function() {
    // 1. Enhanced image lazy loading with blur-up placeholder
    if ('IntersectionObserver' in window) {
        var imgObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.style.opacity = '1';
                    }
                    imgObserver.unobserve(img);
                }
            });
        }, { rootMargin: '200px 0px' }); // Pre-load 200px before visible

        // Observe all lazy images
        function observeImages() {
            document.querySelectorAll('img[loading="lazy"]').forEach(function(img) {
                imgObserver.observe(img);
            });
        }
        observeImages();

        // Re-observe when new content added (infinite scroll)
        var feedObserver = new MutationObserver(function() { observeImages(); });
        var feed = document.getElementById('feedList') || document.querySelector('.feed');
        if (feed) feedObserver.observe(feed, { childList: true, subtree: true });
    }

    // 2. Prefetch next page on scroll near bottom
    var prefetched = {};
    function prefetchPage(url) {
        if (prefetched[url]) return;
        prefetched[url] = true;
        var link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
    }

    // 3. Debounced scroll handler
    var scrollTimer = null;
    window.addEventListener('scroll', function() {
        if (scrollTimer) return;
        scrollTimer = setTimeout(function() {
            scrollTimer = null;
            // Prefetch when 80% scrolled
            var scrollPct = (window.scrollY + window.innerHeight) / document.body.scrollHeight;
            if (scrollPct > 0.8) {
                // Prefetch common next pages
                var page = location.pathname;
                if (page === '/' || page === '/index.html') {
                    prefetchPage('/api/posts.php?limit=20&page=2');
                }
            }
        }, 150);
    }, { passive: true });

    // 4. Preconnect to API and CDN origins
    var origins = ['https://fonts.googleapis.com', 'https://cdnjs.cloudflare.com'];
    origins.forEach(function(origin) {
        if (!document.querySelector('link[href="' + origin + '"]')) {
            var link = document.createElement('link');
            link.rel = 'preconnect';
            link.href = origin;
            document.head.appendChild(link);
        }
    });

    // 5. Passive event listeners for touch
    if (typeof EventTarget !== 'undefined') {
        var origAdd = EventTarget.prototype.addEventListener;
        EventTarget.prototype.addEventListener = function(type, fn, opts) {
            if (type === 'touchstart' || type === 'touchmove' || type === 'wheel') {
                if (typeof opts === 'boolean') opts = { capture: opts, passive: true };
                else if (typeof opts === 'object') opts.passive = opts.passive !== false;
                else opts = { passive: true };
            }
            origAdd.call(this, type, fn, opts);
        };
    }
})();


    // 6. Save/restore scroll position for feed
    var page = location.pathname;
    if (page === '/' || page === '/index.html' || page.indexOf('index.html') > -1) {
        // Restore scroll position on back navigation
        var savedScroll = sessionStorage.getItem('ss_feedScroll');
        if (savedScroll && performance.navigation && performance.navigation.type === 2) {
            // Back/forward navigation
            setTimeout(function() { window.scrollTo(0, parseInt(savedScroll)); }, 300);
        }
        // Save scroll position before leaving
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('ss_feedScroll', window.scrollY);
        });
        // Also save on link clicks
        document.addEventListener('click', function(e) {
            var a = e.target.closest('a[href]');
            if (a && a.href && a.href.indexOf('index.html') < 0) {
                sessionStorage.setItem('ss_feedScroll', window.scrollY);
            }
        });
    }

// Enhanced lazy loading with IntersectionObserver
(function(){
  if(!('IntersectionObserver' in window))return;
  var observer=new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(entry.isIntersecting){
        var img=entry.target;
        if(img.dataset.src){img.src=img.dataset.src;img.removeAttribute('data-src');}
        observer.unobserve(img);
      }
    });
  },{rootMargin:'200px'});
  
  // Observe existing and future images
  function observeImages(){
    document.querySelectorAll('img[data-src]:not([src])').forEach(function(img){observer.observe(img);});
  }
  observeImages();
  
  // MutationObserver for dynamically added images
  var mo=new MutationObserver(function(){observeImages();});
  mo.observe(document.body,{childList:true,subtree:true});
})();
