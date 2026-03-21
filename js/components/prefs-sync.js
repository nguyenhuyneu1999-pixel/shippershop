/**
 * ShipperShop Component — Preferences Sync
 * Syncs user settings to server, applies on page load
 * Uses: SS.api, SS.store
 */
window.SS = window.SS || {};

SS.PrefsSync = {
  _prefs: null,
  _debounce: null,

  // Load preferences from server on init
  init: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    SS.api.get('/user-prefs.php').then(function(d) {
      SS.PrefsSync._prefs = d.data || {};
      SS.PrefsSync._apply();
    }).catch(function() {});
  },

  // Apply preferences to page
  _apply: function() {
    var p = SS.PrefsSync._prefs;
    if (!p) return;

    // Theme
    if (p.theme === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else if (p.theme === 'light') {
      document.documentElement.removeAttribute('data-theme');
    }

    // Font size
    if (p.font_size === 'large') {
      document.documentElement.style.fontSize = '17px';
    } else if (p.font_size === 'small') {
      document.documentElement.style.fontSize = '13px';
    }

    // Compact feed
    if (p.compact_feed) {
      document.body.classList.add('ss-compact');
    }
  },

  // Get a preference value
  get: function(key, defaultVal) {
    if (!SS.PrefsSync._prefs) return defaultVal;
    return SS.PrefsSync._prefs[key] !== undefined ? SS.PrefsSync._prefs[key] : defaultVal;
  },

  // Set a preference (debounced server save)
  set: function(key, value) {
    if (!SS.PrefsSync._prefs) SS.PrefsSync._prefs = {};
    SS.PrefsSync._prefs[key] = value;
    SS.PrefsSync._apply();

    clearTimeout(SS.PrefsSync._debounce);
    SS.PrefsSync._debounce = setTimeout(function() {
      var update = {};
      update[key] = value;
      SS.api.post('/user-prefs.php', update).catch(function() {});
    }, 1000);
  },

  // Get all prefs
  getAll: function() {
    return SS.PrefsSync._prefs || {};
  }
};
