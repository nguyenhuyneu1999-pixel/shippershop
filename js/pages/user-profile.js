/**
 * ShipperShop Page — User Profile (user.html)
 * Loads user data, posts, followers/following, XP, tabs
 * Uses: SS.api, SS.PostCard, SS.UserCard, SS.Gamification, SS.ui
 */
window.SS = window.SS || {};

SS.UserProfile = {
  _userId: null,
  _page: 1,
  _loading: false,
  _hasMore: true,
  _tab: 'posts',

  init: function(userId) {
    SS.UserProfile._userId = userId;
    if (!userId) return;

    // Load profile header
    SS.UserProfile.loadHeader();

    // Load default tab
    SS.UserProfile.loadTab('posts');

    // Load XP card in sidebar
    if (SS.Gamification) {
      SS.Gamification.loadProfile(userId, 'up-xp-card');
    }
  },

  loadHeader: function() {
    var uid = SS.UserProfile._userId;
    SS.api.get('/users.php?action=profile&id=' + uid).then(function(d) {
      var u = d.data;
      if (!u) return;

      var el = document.getElementById('up-header');
      if (!el) return;

      var isSelf = SS.store && SS.store.userId() === parseInt(u.id);
      var isFollowing = u.is_following;

      var shipColor = (SS.PostCard && SS.PostCard._shipColors) ? (SS.PostCard._shipColors[u.shipping_company] || 'var(--text-muted)') : 'var(--text-muted)';

      var actions = '';
      if (isSelf) {
        actions = '<a href="/profile.html" class="btn btn-secondary btn-sm"><i class="fa-solid fa-pen"></i> Sửa hồ sơ</a>';
      } else {
        actions = '<button class="btn ' + (isFollowing ? 'btn-ghost' : 'btn-primary') + ' btn-sm" onclick="SS.UserProfile.toggleFollow()"  id="up-follow-btn">'
          + (isFollowing ? '<i class="fa-solid fa-check"></i> Đang theo dõi' : '<i class="fa-solid fa-plus"></i> Theo dõi') + '</button>'
          + ' <a href="/messages.html?to=' + u.id + '" class="btn btn-secondary btn-sm"><i class="fa-solid fa-envelope"></i> Nhắn tin</a>';
      }

      var subBadge = '';
      if (u.subscription && u.subscription.badge) {
        subBadge = ' <span class="badge badge-vip">' + SS.utils.esc(u.subscription.badge) + '</span>';
      }

      el.innerHTML = '<div style="position:relative">'
        + '<div style="height:180px;background:linear-gradient(135deg,#7C3AED,#3B82F6);border-radius:0 0 16px 16px;overflow:hidden">'
        + (u.cover_photo ? '<img src="' + SS.utils.esc(u.cover_photo) + '" style="width:100%;height:100%;object-fit:cover" loading="lazy">' : '')
        + '</div>'
        + '<div style="position:relative;padding:0 16px;margin-top:-48px">'
        + '<div class="flex items-end gap-3">'
        + '<img class="avatar avatar-xxl" src="' + SS.utils.esc(u.avatar || '/assets/img/defaults/avatar.svg') + '" style="border:4px solid var(--card)" loading="lazy">'
        + '<div class="flex-1" style="padding-bottom:8px">'
        + '<h1 style="font-size:22px;font-weight:800;margin:0">' + SS.utils.esc(u.fullname) + subBadge + '</h1>'
        + (u.shipping_company ? '<div style="color:' + shipColor + ';font-weight:600;font-size:13px">' + SS.utils.esc(u.shipping_company) + '</div>' : '')
        + '</div></div>'
        + '<div class="flex gap-4 mt-3" style="padding:8px 0">'
        + '<div class="stat-card"><div class="stat-number">' + SS.utils.fN(u.total_posts || 0) + '</div><div class="stat-label">bài viết</div></div>'
        + '<div class="stat-card"><div class="stat-number">' + SS.utils.fN(u.total_success || 0) + '</div><div class="stat-label">thành công</div></div>'
        + '<div class="stat-card cursor-pointer" onclick="SS.UserProfile.loadTab(\'followers\')"><div class="stat-number">' + SS.utils.fN(u.followers_count || 0) + '</div><div class="stat-label">theo dõi</div></div>'
        + '<div class="stat-card cursor-pointer" onclick="SS.UserProfile.loadTab(\'following\')"><div class="stat-number">' + SS.utils.fN(u.following_count || 0) + '</div><div class="stat-label">đang theo dõi</div></div>'
        + '</div>'
        + (u.bio ? '<div class="text-sm mt-2" style="line-height:1.5">' + SS.utils.esc(u.bio) + '</div>' : '')
        + '<div class="flex gap-2 mt-3">' + actions + '</div>'
        + '</div></div>';
    }).catch(function() {
      var el = document.getElementById('up-header');
      if (el) el.innerHTML = '<div class="empty-state"><div class="empty-text">Không thể tải hồ sơ</div></div>';
    });
  },

  loadTab: function(tab) {
    SS.UserProfile._tab = tab;
    SS.UserProfile._page = 1;
    SS.UserProfile._hasMore = true;

    // Update tab UI
    var tabs = document.querySelectorAll('.up-tab');
    for (var i = 0; i < tabs.length; i++) {
      tabs[i].classList.toggle('tab-active', tabs[i].getAttribute('data-tab') === tab);
    }

    var container = document.getElementById('up-content');
    if (!container) return;

    if (tab === 'posts') {
      container.innerHTML = SS.PostCard ? SS.PostCard.skeleton(3) : '';
      SS.UserProfile._loadPosts(container, false);
    } else if (tab === 'followers') {
      container.innerHTML = SS.UserCard ? SS.UserCard.skeleton(5) : '';
      SS.UserProfile._loadFollowers(container);
    } else if (tab === 'following') {
      container.innerHTML = SS.UserCard ? SS.UserCard.skeleton(5) : '';
      SS.UserProfile._loadFollowing(container);
    }
  },

  _loadPosts: function(container, append) {
    if (SS.UserProfile._loading) return;
    SS.UserProfile._loading = true;

    SS.api.get('/posts.php', {user_id: SS.UserProfile._userId, page: SS.UserProfile._page, limit: 10}).then(function(d) {
      var posts = d.data ? d.data.posts : [];
      if (!append) container.innerHTML = '';
      if (posts && posts.length) {
        container.insertAdjacentHTML('beforeend', SS.PostCard.renderFeed(posts));
        SS.UserProfile._page++;
        if (d.data.meta && SS.UserProfile._page > d.data.meta.total_pages) SS.UserProfile._hasMore = false;
      } else if (!append) {
        container.innerHTML = '<div class="empty-state"><img src="/assets/img/defaults/no-posts.svg" style="width:100px;opacity:.5" loading="lazy"><div class="empty-text mt-3">Chưa có bài viết</div></div>';
      }
      SS.UserProfile._loading = false;
    }).catch(function() { SS.UserProfile._loading = false; });
  },

  _loadFollowers: function(container) {
    SS.api.get('/social.php?action=followers&user_id=' + SS.UserProfile._userId + '&limit=30').then(function(d) {
      var users = d.data ? d.data.users : [];
      container.innerHTML = SS.UserCard ? SS.UserCard.renderList(users) : '';
    });
  },

  _loadFollowing: function(container) {
    SS.api.get('/social.php?action=following&user_id=' + SS.UserProfile._userId + '&limit=30').then(function(d) {
      var users = d.data ? d.data.users : [];
      container.innerHTML = SS.UserCard ? SS.UserCard.renderList(users) : '';
    });
  },

  toggleFollow: function() {
    var btn = document.getElementById('up-follow-btn');
    if (!btn || !SS.store || !SS.store.isLoggedIn()) { window.location.href = '/login.html'; return; }
    btn.disabled = true;
    SS.api.post('/social.php?action=follow', {user_id: SS.UserProfile._userId}).then(function(d) {
      var f = d.data && d.data.following;
      btn.className = 'btn ' + (f ? 'btn-ghost' : 'btn-primary') + ' btn-sm';
      btn.innerHTML = f ? '<i class="fa-solid fa-check"></i> Đang theo dõi' : '<i class="fa-solid fa-plus"></i> Theo dõi';
      btn.disabled = false;
    }).catch(function() { btn.disabled = false; });
  }
};
