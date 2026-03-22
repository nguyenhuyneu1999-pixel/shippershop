/**
 * ShipperShop Component — AB Test Client
 * Get user's variant for active tests
 */
window.SS = window.SS || {};

SS.ABTest = {
  _cache: {},

  getVariant: function(testId, callback) {
    if (SS.ABTest._cache[testId]) { if (callback) callback(SS.ABTest._cache[testId]); return; }
    var userId = SS.store && SS.store.getUser() ? SS.store.getUser().id : 0;
    SS.api.get('/ab-test.php?action=variant&test_id=' + testId + '&user_id=' + userId).then(function(d) {
      var variant = (d.data || {}).variant || 'control';
      SS.ABTest._cache[testId] = variant;
      if (callback) callback(variant);
    }).catch(function() { if (callback) callback('control'); });
  },

  // Check if user is in variant B
  isB: function(testId, callback) {
    SS.ABTest.getVariant(testId, function(v) { callback(v === 'B'); });
  }
};
