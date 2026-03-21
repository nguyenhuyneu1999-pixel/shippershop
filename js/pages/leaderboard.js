/**
 * ShipperShop Page — Leaderboard (leaderboard.html)
 * XP rankings by all-time, monthly, weekly
 * Uses: SS.api, SS.Gamification, SS.ui
 */
window.SS = window.SS || {};

SS.LeaderboardPage = {
  _period: 'all',

  init: function() {
    SS.LeaderboardPage.load('all');
    // Load my XP card
    if (SS.Gamification && SS.store && SS.store.isLoggedIn()) {
      SS.Gamification.loadProfile(SS.store.userId(), 'lb-my-xp');
    }
  },

  load: function(period) {
    SS.LeaderboardPage._period = period;
    // Update tabs
    var tabs = document.querySelectorAll('.lb-tab');
    for (var i = 0; i < tabs.length; i++) {
      tabs[i].classList.toggle('tab-active', tabs[i].getAttribute('data-period') === period);
    }
    if (SS.Gamification) {
      SS.Gamification.loadLeaderboard('lb-list', period);
    }
  }
};
