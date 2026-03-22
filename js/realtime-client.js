// ShipperShop Realtime Client
// Auto-detect: WebSocket (VPS) or Polling (shared hosting)
// Usage: SSRealtime.connect() → auto-chooses best method

var SSRealtime = (function() {
    var ws = null;
    var connected = false;
    var useWS = false;
    var pollTimer = null;
    var handlers = {};
    var lastPollTime = '';

    function getToken() {
        return localStorage.getItem('token') || '';
    }

    // Try WebSocket first, fallback to polling
    function connect() {
        var wsUrl = location.protocol === 'https:' 
            ? 'wss://' + location.host + ':8080'
            : 'ws://' + location.host + ':8080';

        try {
            ws = new WebSocket(wsUrl);
            ws.onopen = function() {
                useWS = true;
                connected = true;
                ws.send(JSON.stringify({type: 'auth', token: getToken()}));
                console.log('[RT] WebSocket connected');
            };
            ws.onmessage = function(e) {
                var data = JSON.parse(e.data);
                fire(data.type, data);
            };
            ws.onclose = function() {
                connected = false;
                useWS = false;
                console.log('[RT] WebSocket closed, falling back to polling');
                startPolling();
            };
            ws.onerror = function() {
                useWS = false;
                startPolling();
            };

            // Timeout: if not connected in 3s, use polling
            setTimeout(function() {
                if (!connected) {
                    if (ws) ws.close();
                    startPolling();
                }
            }, 3000);
        } catch (e) {
            startPolling();
        }
    }

    // Polling fallback (shared hosting)
    function startPolling() {
        if (pollTimer) return;
        console.log('[RT] Using polling (3s interval)');
        pollTimer = setInterval(poll, 3000);
    }

    function poll() {
        var convId = window._currentConversationId;
        if (!convId) return;

        var url = '/api/messages-api.php?action=poll&conversation_id=' + convId;
        if (lastPollTime) url += '&since=' + encodeURIComponent(lastPollTime);

        fetch(url, {headers: {'Authorization': 'Bearer ' + getToken()}})
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success && d.data) {
                    var msgs = d.data.new_messages || [];
                    if (msgs.length > 0) {
                        lastPollTime = msgs[msgs.length - 1].created_at;
                        fire('new_messages', {messages: msgs});
                    }
                    var typing = d.data.typing || [];
                    if (typing.length > 0) {
                        fire('typing', {users: typing, conversation_id: convId});
                    }
                }
            })
            .catch(function() {});
    }

    // Send message via WS or API
    function send(convId, content) {
        if (useWS && ws && ws.readyState === 1) {
            ws.send(JSON.stringify({type: 'message', conversation_id: convId, content: content}));
        } else {
            // Fallback to HTTP POST
            fetch('/api/messages-api.php?action=send', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer ' + getToken()},
                body: JSON.stringify({conversation_id: convId, content: content})
            });
        }
    }

    function sendTyping(convId) {
        if (useWS && ws && ws.readyState === 1) {
            ws.send(JSON.stringify({type: 'typing', conversation_id: convId}));
        } else {
            fetch('/api/messages-api.php?action=typing', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer ' + getToken()},
                body: JSON.stringify({conversation_id: convId})
            });
        }
    }

    function markRead(convId) {
        if (useWS && ws && ws.readyState === 1) {
            ws.send(JSON.stringify({type: 'read', conversation_id: convId}));
        } else {
            fetch('/api/messages-api.php?action=read', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer ' + getToken()},
                body: JSON.stringify({conversation_id: convId})
            });
        }
    }

    // Event system
    function on(event, callback) {
        if (!handlers[event]) handlers[event] = [];
        handlers[event].push(callback);
    }
    function fire(event, data) {
        (handlers[event] || []).forEach(function(cb) { cb(data); });
    }

    function disconnect() {
        if (ws) ws.close();
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = null;
        connected = false;
    }

    function info() {
        return {mode: useWS ? 'websocket' : 'polling', connected: connected};
    }

    return {connect: connect, send: send, sendTyping: sendTyping, markRead: markRead, on: on, disconnect: disconnect, info: info};
})();
