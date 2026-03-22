/**
 * ShipperShop Component — Feature Flags Client
 * Check if feature is enabled before rendering
 */
window.SS = window.SS || {};

SS.FeatureFlags = {
  _cache: null,

  load: function(callback) {
    if (SS.FeatureFlags._cache) { if (callback) callback(SS.FeatureFlags._cache); return; }
    SS.api.get('/feature-flags.php').then(function(d) {
      SS.FeatureFlags._cache = (d.data || {}).flags || {};
      if (callback) callback(SS.FeatureFlags._cache);
    }).catch(function() { if (callback) callback({}); });
  },

  isEnabled: function(flag, callback) {
    SS.FeatureFlags.load(function(flags) {
      callback(flags[flag] === true);
    });
  },

  // Sync check (use after load)
  check: function(flag) {
    return SS.FeatureFlags._cache ? (SS.FeatureFlags._cache[flag] === true) : true;
  }
};
