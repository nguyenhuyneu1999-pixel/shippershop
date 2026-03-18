const CACHE_NAME = 'shippershop-v6';
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/profile.html',
  '/groups.html',
  '/messages.html',
  '/people.html',
  '/user.html',
  '/post-detail.html',
  '/marketplace.html',
  '/login.html',
  '/register.html',
  '/css/style.css',
  '/mobile.css',
  '/js/reddit-mkpost-1773841101.js',
  '/js/mobile.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
];

// Install: cache static assets
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS).catch(err => {
        console.log('Cache addAll partial fail:', err);
        // Cache what we can
        return Promise.allSettled(STATIC_ASSETS.map(url => cache.add(url).catch(() => {})));
      });
    })
  );
  self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
    ))
  );
  self.clients.claim();
});

// Fetch: network-first for API, cache-first for static
self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);
  
  // Skip non-GET
  if (e.request.method !== 'GET') return;
  
  // API calls: network only (fresh data)
  if (url.pathname.startsWith('/api/')) {
    e.respondWith(fetch(e.request).catch(() => new Response(JSON.stringify({success:false,message:'Offline'}), {headers:{'Content-Type':'application/json'}})));
    return;
  }
  
  // Static assets: cache-first, fallback network
  e.respondWith(
    caches.match(e.request).then(cached => {
      if (cached) {
        // Return cache, update in background
        fetch(e.request).then(res => {
          if (res.ok) caches.open(CACHE_NAME).then(c => c.put(e.request, res));
        }).catch(() => {});
        return cached;
      }
      return fetch(e.request).then(res => {
        if (res.ok && url.origin === location.origin) {
          const clone = res.clone();
          caches.open(CACHE_NAME).then(c => c.put(e.request, clone));
        }
        return res;
      }).catch(() => caches.match('/index.html'));
    })
  );
});

// Push notifications (future)
self.addEventListener('push', e => {
  const data = e.data ? e.data.json() : {title:'ShipperShop',body:'Bạn có thông báo mới'};
  e.waitUntil(self.registration.showNotification(data.title, {
    body: data.body,
    icon: '/icons/icon-192.png',
    badge: '/icons/icon-72.png',
    data: data.url || '/',
    vibrate: [200, 100, 200],
  }));
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  e.waitUntil(clients.openWindow(e.notification.data || '/'));
});