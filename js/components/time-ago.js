/**
 * ShipperShop Component — Auto-refresh relative timestamps
 */
window.SS = window.SS || {};

SS.TimeAgo = {
  refresh: function() {
    var els = document.querySelectorAll('[data-time]');
    for (var i = 0; i < els.length; i++) {
      var ts = els[i].getAttribute('data-time');
      if (ts && SS.utils.ago) els[i].textContent = SS.utils.ago(ts);
    }
  },
  startAutoRefresh: function(interval) { setInterval(SS.TimeAgo.refresh, interval || 60000); }
};
