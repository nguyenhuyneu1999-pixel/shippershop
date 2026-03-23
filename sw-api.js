// ShipperShop API Cache Service Worker
// Strategy: stale-while-revalidate (instant from cache, refresh in background)
var API_CACHE = 'ss-api-v1';
var STATIC_FEED_URLS = [
    '/api/static/feed-new.json',
    '/api/static/feed-hot.json',
    '/api/static/feed-top.json',
    '/api/static/trending.json',
    '/api/static/groups-discover.json',
];

// Cache-first for static feeds, network-first for dynamic APIs
self.addEventListener('fetch', function(event) {
    var url = new URL(event.request.url);
    
    // Only handle GET API requests
    if (event.request.method !== 'GET') return;
    
    // Static feed files: stale-while-revalidate (instant + background refresh)
    if (url.pathname.indexOf('/api/static/') === 0) {
        event.respondWith(
            caches.open(API_CACHE).then(function(cache) {
                return cache.match(event.request).then(function(cached) {
                    var fetchPromise = fetch(event.request).then(function(response) {
                        if (response.ok) cache.put(event.request, response.clone());
                        return response;
                    }).catch(function() { return cached; });
                    
                    return cached || fetchPromise;
                });
            })
        );
        return;
    }
    
    // Dynamic API (posts, groups, etc): network-first, fallback to cache
    if (url.pathname.indexOf('/api/') === 0 && !url.pathname.indexOf('/api/auth') === 0) {
        event.respondWith(
            fetch(event.request).then(function(response) {
                if (response.ok) {
                    var clone = response.clone();
                    caches.open(API_CACHE).then(function(cache) { cache.put(event.request, clone); });
                }
                return response;
            }).catch(function() {
                return caches.match(event.request);
            })
        );
        return;
    }
});

// Pre-cache static feeds on install
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(API_CACHE).then(function(cache) {
            return cache.addAll(STATIC_FEED_URLS).catch(function() {});
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(names) {
            return Promise.all(
                names.filter(function(n) { return n !== API_CACHE && n.indexOf('ss-api') === 0; })
                     .map(function(n) { return caches.delete(n); })
            );
        })
    );
    self.clients.claim();
});
