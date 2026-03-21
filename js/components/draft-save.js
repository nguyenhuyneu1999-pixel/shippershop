/**
 * ShipperShop Component — Draft Auto-Save
 * Saves post/comment drafts to localStorage while typing
 * Restores on page reload, clears on successful submit
 */
window.SS = window.SS || {};

SS.DraftSave = {
  _timers: {},

  // Attach auto-save to a textarea
  attach: function(inputId, draftKey) {
    var input = document.getElementById(inputId);
    if (!input) return;
    draftKey = draftKey || 'draft_' + inputId;

    // Restore saved draft
    var saved = localStorage.getItem(draftKey);
    if (saved && !input.value) {
      input.value = saved;
      // Show restore notice
      var notice = document.createElement('div');
      notice.style.cssText = 'font-size:11px;color:var(--primary);padding:4px 8px;cursor:pointer';
      notice.textContent = 'Đã khôi phục bản nháp · Nhấn để xóa';
      notice.onclick = function() {
        input.value = '';
        localStorage.removeItem(draftKey);
        notice.remove();
      };
      input.parentNode.insertBefore(notice, input.nextSibling);
    }

    // Auto-save on input
    input.addEventListener('input', function() {
      clearTimeout(SS.DraftSave._timers[draftKey]);
      SS.DraftSave._timers[draftKey] = setTimeout(function() {
        var val = input.value.trim();
        if (val) {
          localStorage.setItem(draftKey, val);
        } else {
          localStorage.removeItem(draftKey);
        }
      }, 3000);
    });
  },

  // Clear draft (call after successful submit)
  clear: function(draftKey) {
    localStorage.removeItem(draftKey);
    if (SS.DraftSave._timers[draftKey]) {
      clearTimeout(SS.DraftSave._timers[draftKey]);
      delete SS.DraftSave._timers[draftKey];
    }
  },

  // Clear all drafts
  clearAll: function() {
    var keys = Object.keys(localStorage);
    for (var i = 0; i < keys.length; i++) {
      if (keys[i].indexOf('draft_') === 0) localStorage.removeItem(keys[i]);
    }
  },

  // Get saved draft count
  count: function() {
    var c = 0;
    var keys = Object.keys(localStorage);
    for (var i = 0; i < keys.length; i++) {
      if (keys[i].indexOf('draft_') === 0 && localStorage.getItem(keys[i])) c++;
    }
    return c;
  }
};
