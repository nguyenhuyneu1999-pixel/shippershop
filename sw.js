// ShipperShop Service Worker v13
// Cache strategies: Cache First (static), Network First (API), Stale While Revalidate (images)
var CACHE = 'shippershop-v20';
var STATIC_ASSETS = [
  '/',
  '/index.html',
  '/css/design-system.min.css',
  '/css/design-system.css',
  '/css/style-v2.css',
  '/js/admin-deposits.js',
  '/css/dark-mode.css',
  '/js/dark-mode.js',
  '/js/lightbox.js',
  '/css/index-page.css',
  '/css/profile-page.css',
  '/css/activity-log-page.css',
  '/css/listing-page.css',
  '/css/people-page.css',
  '/css/user-page.css',
  '/css/post-detail-page.css',
  '/js/perf-boost.js',
  '/js/activity-log-page.js',
  '/js/listing-page.js',
  '/js/people-page.js',
  '/js/user-page.js',
  '/js/post-detail-page.js',
  '/js/realtime-client.js',
  '/css/wallet-page.css',
  '/css/map-page.css',
  '/css/group-page.css',
  '/css/groups-page.css',
  '/css/marketplace-page.css',
  '/css/traffic-page.css',
  '/css/messages-page.css',
  '/js/profile-page.js',
  '/js/wallet-page.js',
  '/js/map-page.js',
  '/js/marketplace-page.js',
  '/js/traffic-page.js',
  '/js/group-detail.js',
  '/js/groups-page.js',
  '/js/messages-options.js',
  '/js/messages-filter.js',
  '/js/messages-core.js',
  '/js/network-handler.js',
  '/js/feed-search.js',
  '/js/feed-notifications.js',
  '/js/feed-comments.js',
  '/js/feed-data.js',
  '/mobile.css',
    '/js/ss-smart-loader.js',
  '/js/ss-error-tracker.js',
    '/js/ss-prod.js',
  '/assets/img/defaults/avatar.svg',
  '/assets/img/defaults/no-posts.svg',
  '/assets/img/defaults/no-messages.svg',
  '/offline.html',
  '/manifest.json'
];

// Install: cache static assets
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE).then(function(cache) {
      return cache.addAll(STATIC_ASSETS);
    }).then(function() {
      return self.skipWaiting();
    })
  );
});

// Activate: clean old caches
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(names) {
      return Promise.all(
        names.filter(function(n) { return n !== CACHE; })
             .map(function(n) { return caches.delete(n); })
      );
    }).then(function() {
      return self.clients.claim();
    })
  );
});

// Fetch strategies
self.addEventListener('fetch', function(event) {
  var url = new URL(event.request.url);

  // Skip non-GET
  if (event.request.method !== 'GET') return;

  // API: Network First (always fresh data)
  if (url.pathname.indexOf('/api/') === 0) {
    event.respondWith(
      fetch(event.request).catch(function() {
        return new Response(JSON.stringify({success: false, message: 'Ngoại tuyến'}), {
          headers: {'Content-Type': 'application/json'}
        });
      })
    );
    return;
  }

  // Static assets (CSS, JS, fonts): Cache First
  if (url.pathname.match(/\.(css|js|woff|woff2|ttf|eot)$/) || STATIC_ASSETS.indexOf(url.pathname) > -1) {
    event.respondWith(
      caches.match(event.request).then(function(cached) {
        if (cached) return cached;
        return fetch(event.request).then(function(response) {
          if (response.ok) {
            var clone = response.clone();
            caches.open(CACHE).then(function(cache) { cache.put(event.request, clone); });
          }
          return response;
        });
      }).catch(function() {
        // Offline fallback for HTML pages
        if (event.request.headers.get('accept').indexOf('text/html') > -1) {
          return caches.match('/offline.html');
        }
      })
    );
    return;
  }

  // Images: Stale While Revalidate (serve cached, update in background)
  if (url.pathname.match(/\.(png|jpg|jpeg|gif|webp|svg|ico)$/)) {
    event.respondWith(
      caches.match(event.request).then(function(cached) {
        var fetchPromise = fetch(event.request).then(function(response) {
          if (response.ok) {
            var clone = response.clone();
            caches.open(CACHE).then(function(cache) { cache.put(event.request, clone); });
          }
          return response;
        }).catch(function() { return cached; });
        return cached || fetchPromise;
      })
    );
    return;
  }

  // HTML pages: Network First with offline fallback
  if (event.request.headers.get('accept').indexOf('text/html') > -1) {
    event.respondWith(
      fetch(event.request).then(function(response) {
        if (response.ok) {
          var clone = response.clone();
          caches.open(CACHE).then(function(cache) { cache.put(event.request, clone); });
        }
        return response;
      }).catch(function() {
        return caches.match(event.request).then(function(cached) {
          return cached || caches.match('/offline.html');
        });
      })
    );
    return;
  }

  // Default: Network First
  event.respondWith(
    fetch(event.request).catch(function() {
      return caches.match(event.request);
    })
  );
});

// Push notifications
self.addEventListener('push', function(event) {
  var data = {};
  try { data = event.data.json(); } catch(e) { data = {title: 'ShipperShop', body: event.data ? event.data.text() : 'Thông báo mới'}; }
  event.waitUntil(
    self.registration.showNotification(data.title || 'ShipperShop', {
      body: data.body || '',
      icon: '/icons/icon-192.png',
      badge: '/icons/icon-96.png',
      data: {url: data.url || '/'},
      vibrate: [100, 50, 100]
    })
  );
});

// Notification click
self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  var url = event.notification.data && event.notification.data.url ? event.notification.data.url : '/';
  event.waitUntil(clients.openWindow(url));
});
