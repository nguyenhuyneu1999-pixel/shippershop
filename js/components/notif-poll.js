/**
 * ShipperShop Component — Notification Polling
 * Checks for new notifications every 30s, updates badge, shows toast for new ones
 * Uses: SS.api, SS.ui, SS.store
 */
window.SS = window.SS || {};

SS.NotifPoll = {
  _timer: null,
  _lastCheck: null,
  _lastCount: 0,

  init: function(interval) {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    interval = interval || 30000;
    SS.NotifPoll._lastCheck = new Date().toISOString();
    SS.NotifPoll._timer = setInterval(SS.NotifPoll.check, interval);
    // First check after 5s
    setTimeout(SS.NotifPoll.check, 5000);
  },

  check: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    if (document.hidden) return; // Don't poll when tab is hidden

    SS.api.get('/notifications.php?action=unread_count').then(function(d) {
      var count = parseInt((d.data && d.data.count) || d.count || 0);

      // Update all bell badges on page
      var badges = document.querySelectorAll('.ss-notif-badge');
      for (var i = 0; i < badges.length; i++) {
        if (count > 0) {
          badges[i].textContent = count > 99 ? '99+' : count;
          badges[i].style.display = 'flex';
        } else {
          badges[i].style.display = 'none';
        }
      }

      // Update page title badge
      var title = document.title.replace(/^\(\d+\+?\)\s*/, '');
      document.title = count > 0 ? '(' + count + ') ' + title : title;

      // Toast for new notifications
      if (count > SS.NotifPoll._lastCount && SS.NotifPoll._lastCount >= 0) {
        var newCount = count - SS.NotifPoll._lastCount;
        if (newCount > 0 && SS.ui) {
          SS.ui.toast(newCount + ' thông báo mới', 'info', 3000);
        }
      }
      SS.NotifPoll._lastCount = count;
    }).catch(function() {});
  },

  stop: function() {
    if (SS.NotifPoll._timer) {
      clearInterval(SS.NotifPoll._timer);
      SS.NotifPoll._timer = null;
    }
  }
};

// Auto-init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { SS.NotifPoll.init(); });
} else {
  SS.NotifPoll.init();
}
