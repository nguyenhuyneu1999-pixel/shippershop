/**
 * ShipperShop Page — People (people.html)
 * Suggestions, online, followers
 * Uses: SS.api, SS.UserCard, SS.ui
 */
window.SS = window.SS || {};

SS.PeoplePage = {
  _tab: 'suggestions',

  init: function() {
    SS.PeoplePage.loadTab('suggestions');
  },

  loadTab: function(tab) {
    SS.PeoplePage._tab = tab;
    var tabs = document.querySelectorAll('.pp-tab');
    for (var i = 0; i < tabs.length; i++) {
      tabs[i].classList.toggle('tab-active', tabs[i].getAttribute('data-tab') === tab);
    }

    var el = document.getElementById('pp-list');
    if (!el) return;
    el.innerHTML = SS.UserCard ? SS.UserCard.skeleton(6) : '';

    if (tab === 'suggestions') {
      SS.api.get('/social.php?action=suggestions&limit=20').then(function(d) {
        el.innerHTML = SS.UserCard ? SS.UserCard.renderList(d.data || []) : '';
      }).catch(function() { el.innerHTML = '<div class="empty-state"><div class="empty-text">Lỗi tải</div></div>'; });
    } else if (tab === 'online') {
      SS.api.get('/social.php?action=online&limit=30').then(function(d) {
        var users = d.data || [];
        if (!users.length) {
          el.innerHTML = '<div class="empty-state"><div class="empty-text">Chưa ai online</div></div>';
          return;
        }
        el.innerHTML = SS.UserCard ? SS.UserCard.renderList(users) : '';
      }).catch(function() { el.innerHTML = ''; });
    } else if (tab === 'friends') {
      SS.api.get('/social.php?action=friends').then(function(d) {
        var users = d.data || [];
        if (!users.length) {
          el.innerHTML = '<div class="empty-state"><div class="empty-text">Chưa có bạn bè chung</div></div>';
          return;
        }
        el.innerHTML = SS.UserCard ? SS.UserCard.renderList(users, {hideFollow: true}) : '';
      }).catch(function() { el.innerHTML = ''; });
    }
  }
};
