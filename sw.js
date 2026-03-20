const CACHE_NAME = 'shippershop-v10';
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/profile.html',
  '/groups.html',
  '/group.html',
  '/messages.html',
  '/people.html',
  '/user.html',
  '/post-detail.html',
  '/marketplace.html',
  '/login.html',
  '/register.html',
  '/css/style.css',
  '/mobile.css',
  '/js/reddit-mkpost-1773927135.js',
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

// Push notifications
self.addEventListener('push', function(e) {
  var data = {title:'ShipperShop', body:'Bạn có thông báo mới', category:'general', url:'/', icon:'/icons/icon-192.png', badge:'/icons/icon-72.png'};
  if (e.data) {
    try { data = Object.assign(data, e.data.json()); } catch(x) { data.body = e.data.text(); }
  }
  
  // Category-based tag (groups same-type notifications)
  var tag = data.category || 'general';
  if (data.category === 'message') tag = 'msg-' + (data.conversationId || 'chat');
  else if (data.category === 'group') tag = 'grp-' + (data.groupId || 'community');
  else if (data.category === 'post') tag = 'post-' + (data.postId || 'feed');
  
  var options = {
    body: data.body,
    icon: data.icon || '/icons/icon-192.png',
    badge: data.badge || '/icons/icon-72.png',
    tag: tag,
    renotify: true,
    data: { url: data.url || '/', category: data.category },
    vibrate: [200, 100, 200],
    actions: []
  };
  
  // Category-specific actions
  if (data.category === 'message') {
    options.actions = [{action:'reply',title:'Trả lời'},{action:'open',title:'Mở'}];
  } else if (data.category === 'post') {
    options.actions = [{action:'open',title:'Xem bài viết'}];
  } else if (data.category === 'group') {
    options.actions = [{action:'open',title:'Xem cộng đồng'}];
  }
  
  e.waitUntil(self.registration.showNotification(data.title, options));
});

self.addEventListener('notificationclick', function(e) {
  e.notification.close();
  var url = '/';
  if (e.notification.data && e.notification.data.url) url = e.notification.data.url;
  
  e.waitUntil(
    clients.matchAll({type:'window', includeUncontrolled:true}).then(function(windowClients) {
      // Try to focus existing window
      for (var i = 0; i < windowClients.length; i++) {
        var client = windowClients[i];
        if (client.url.indexOf(self.location.origin) !== -1 && 'focus' in client) {
          client.focus();
          client.navigate(url);
          return;
        }
      }
      // Open new window
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});