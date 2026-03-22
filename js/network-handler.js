// ShipperShop Network Handler — Retry + Offline + Error handling
// Add to any page: <script src="/js/network-handler.js"></script>

(function() {
    var MAX_RETRIES = 2;
    var RETRY_DELAY = 2000;
    var OFFLINE_SHOWN = false;

    // Wrap fetch with retry + timeout
    var originalFetch = window.fetch;
    window.fetch = function(url, options) {
        options = options || {};
        var retries = options._retries || 0;
        var timeout = options.timeout || 15000;

        // Add timeout via AbortController
        var controller = new AbortController();
        var timeoutId = setTimeout(function() { controller.abort(); }, timeout);
        options.signal = controller.signal;

        return originalFetch(url, options)
            .then(function(response) {
                clearTimeout(timeoutId);
                hideOffline();
                if (!response.ok && response.status >= 500 && retries < MAX_RETRIES) {
                    return new Promise(function(resolve) {
                        setTimeout(function() {
                            options._retries = retries + 1;
                            resolve(window.fetch(url, options));
                        }, RETRY_DELAY * (retries + 1));
                    });
                }
                return response;
            })
            .catch(function(err) {
                clearTimeout(timeoutId);
                if (err.name === 'AbortError') {
                    console.warn('[Network] Timeout: ' + url);
                }
                if (!navigator.onLine) {
                    showOffline();
                }
                if (retries < MAX_RETRIES && err.name !== 'AbortError') {
                    return new Promise(function(resolve, reject) {
                        setTimeout(function() {
                            options._retries = retries + 1;
                            window.fetch(url, options).then(resolve).catch(reject);
                        }, RETRY_DELAY * (retries + 1));
                    });
                }
                throw err;
            });
    };

    // Offline banner
    function showOffline() {
        if (OFFLINE_SHOWN) return;
        OFFLINE_SHOWN = true;
        var d = document.createElement('div');
        d.id = 'ss-offline';
        d.style.cssText = 'position:fixed;top:0;left:0;right:0;background:#ef4444;color:#fff;text-align:center;padding:8px;font-size:13px;z-index:99999;font-family:sans-serif';
        d.textContent = 'Mất kết nối mạng. Đang thử lại...';
        document.body.appendChild(d);
    }
    function hideOffline() {
        OFFLINE_SHOWN = false;
        var d = document.getElementById('ss-offline');
        if (d) d.remove();
    }

    // Auto-detect online/offline
    window.addEventListener('online', hideOffline);
    window.addEventListener('offline', showOffline);

    // Prevent double submit on forms/buttons
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('button[type="submit"], .btn-submit');
        if (btn && !btn.disabled) {
            btn.disabled = true;
            btn.style.opacity = '0.6';
            setTimeout(function() {
                btn.disabled = false;
                btn.style.opacity = '1';
            }, 3000);
        }
    });
})();
