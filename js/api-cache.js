// ShipperShop Client-Side API Cache
// Reduces redundant API calls by caching in sessionStorage
// TTL: 15-60s depending on endpoint
(function(){
    var CACHE_PREFIX = 'ss_ac_';
    
    window.cachedFetch = function(url, options, ttlSec) {
        ttlSec = ttlSec || 15;
        
        // Only cache GET without body
        if (options && (options.method === 'POST' || options.body)) {
            return fetch(url, options);
        }
        
        var cacheKey = CACHE_PREFIX + url;
        var cached = sessionStorage.getItem(cacheKey);
        
        if (cached) {
            try {
                var data = JSON.parse(cached);
                if (data._exp > Date.now()) {
                    // Return cached response as resolved promise
                    return Promise.resolve({
                        ok: true,
                        status: 200,
                        json: function() { return Promise.resolve(data._body); },
                        headers: { get: function() { return 'application/json'; } }
                    });
                }
            } catch(e) {}
            sessionStorage.removeItem(cacheKey);
        }
        
        // Fetch from network
        return fetch(url, options).then(function(resp) {
            if (resp.ok) {
                var cloned = resp.clone();
                cloned.json().then(function(body) {
                    try {
                        sessionStorage.setItem(cacheKey, JSON.stringify({
                            _body: body,
                            _exp: Date.now() + ttlSec * 1000
                        }));
                    } catch(e) {
                        // sessionStorage full — clear old entries
                        for (var i = sessionStorage.length - 1; i >= 0; i--) {
                            var k = sessionStorage.key(i);
                            if (k && k.indexOf(CACHE_PREFIX) === 0) sessionStorage.removeItem(k);
                        }
                    }
                });
            }
            return resp;
        });
    };
    
    // Invalidate cache on POST
    window.invalidateCache = function(pattern) {
        for (var i = sessionStorage.length - 1; i >= 0; i--) {
            var k = sessionStorage.key(i);
            if (k && k.indexOf(CACHE_PREFIX) === 0 && (!pattern || k.indexOf(pattern) > -1)) {
                sessionStorage.removeItem(k);
            }
        }
    };
})();
