/**
 * ShipperShop Component — Auto-Save Drafts
 * Saves post/comment content to localStorage while typing
 * Restores on page reload, clears on submit
 */
window.SS = window.SS || {};

SS.AutoDraft = {
  _timers: {},
  _prefix: 'ss_draft_',

  // Attach auto-save to a textarea
  attach: function(textarea, key) {
    if (!textarea || !key) return;
    var storageKey = SS.AutoDraft._prefix + key;

    // Restore existing draft
    var saved = localStorage.getItem(storageKey);
    if (saved) {
      try {
        var data = JSON.parse(saved);
        if (data.content && data.timestamp) {
          var age = Date.now() - data.timestamp;
          if (age < 86400000) { // < 24 hours
            textarea.value = data.content;
            SS.AutoDraft._showIndicator(textarea, data.timestamp);
          } else {
            localStorage.removeItem(storageKey);
          }
        }
      } catch (e) {
        localStorage.removeItem(storageKey);
      }
    }

    // Auto-save on input (debounced 1s)
    textarea.addEventListener('input', function() {
      clearTimeout(SS.AutoDraft._timers[key]);
      SS.AutoDraft._timers[key] = setTimeout(function() {
        var content = textarea.value.trim();
        if (content.length > 2) {
          localStorage.setItem(storageKey, JSON.stringify({
            content: content,
            timestamp: Date.now()
          }));
          SS.AutoDraft._showSaved(textarea);
        } else {
          localStorage.removeItem(storageKey);
        }
      }, 1000);
    });
  },

  // Clear draft after successful submit
  clear: function(key) {
    localStorage.removeItem(SS.AutoDraft._prefix + key);
    clearTimeout(SS.AutoDraft._timers[key]);
  },

  // Check if draft exists
  has: function(key) {
    var saved = localStorage.getItem(SS.AutoDraft._prefix + key);
    if (!saved) return false;
    try {
      var data = JSON.parse(saved);
      return data.content && (Date.now() - data.timestamp < 86400000);
    } catch (e) { return false; }
  },

  // Get all drafts
  listAll: function() {
    var drafts = [];
    for (var i = 0; i < localStorage.length; i++) {
      var k = localStorage.key(i);
      if (k && k.startsWith(SS.AutoDraft._prefix)) {
        try {
          var data = JSON.parse(localStorage.getItem(k));
          if (data.content) {
            drafts.push({
              key: k.replace(SS.AutoDraft._prefix, ''),
              content: data.content.substring(0, 100),
              timestamp: data.timestamp
            });
          }
        } catch (e) {}
      }
    }
    return drafts.sort(function(a, b) { return b.timestamp - a.timestamp; });
  },

  _showIndicator: function(textarea, timestamp) {
    var parent = textarea.parentElement;
    if (!parent) return;
    var existing = parent.querySelector('.draft-indicator');
    if (existing) existing.remove();
    var div = document.createElement('div');
    div.className = 'draft-indicator';
    div.style.cssText = 'font-size:11px;color:var(--warning);padding:2px 0;display:flex;align-items:center;gap:4px';
    div.innerHTML = '<i class="fa-solid fa-clock-rotate-left" style="font-size:10px"></i> Bản nháp từ ' + SS.utils.ago(new Date(timestamp).toISOString());
    parent.insertBefore(div, textarea.nextSibling);
  },

  _showSaved: function(textarea) {
    var parent = textarea.parentElement;
    if (!parent) return;
    var existing = parent.querySelector('.draft-saved');
    if (existing) existing.remove();
    var div = document.createElement('div');
    div.className = 'draft-saved';
    div.style.cssText = 'font-size:10px;color:var(--success);padding:2px 0;opacity:1;transition:opacity 1s';
    div.textContent = '✓ Đã tự động lưu';
    parent.insertBefore(div, textarea.nextSibling);
    setTimeout(function() { div.style.opacity = '0'; }, 2000);
    setTimeout(function() { div.remove(); }, 3000);
  }
};
