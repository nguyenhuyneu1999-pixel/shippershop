/**
 * ShipperShop Component — Profile Card
 * Hover/tap popup showing user preview with follow button
 * Uses: SS.api, SS.BadgeDisplay
 */
window.SS = window.SS || {};

SS.ProfileCard = {
  _cache: {},
  _timer: null,
  _el: null,

  // Show card on hover/click
  show: function(userId, anchorEl) {
    if (!userId) return;
    clearTimeout(SS.ProfileCard._timer);
    SS.ProfileCard.hide();

    // Check cache
    if (SS.ProfileCard._cache[userId]) {
      SS.ProfileCard._render(SS.ProfileCard._cache[userId], anchorEl);
      return;
    }

    SS.api.get('/profile-card.php?user_id=' + userId).then(function(d) {
      if (!d.data) return;
      SS.ProfileCard._cache[userId] = d.data;
      SS.ProfileCard._render(d.data, anchorEl);
    });
  },

  _render: function(u, anchor) {
    var card = document.createElement('div');
    card.id = 'ss-profile-card';
    card.style.cssText = 'position:fixed;z-index:5000;background:var(--card);border-radius:12px;box-shadow:var(--shadow-lg);width:300px;overflow:hidden;animation:fadeIn .15s';

    var onlineDot = u.is_online ? '<span style="position:absolute;bottom:2px;right:2px;width:12px;height:12px;border-radius:50%;background:#22c55e;border:2px solid var(--card)"></span>' : '';
    var planBadge = SS.BadgeDisplay ? SS.BadgeDisplay.getSubBadge(u.plan_id) : '';
    var verifiedIcon = u.is_verified ? ' <i class="fa-solid fa-circle-check" style="color:#3b82f6;font-size:12px"></i>' : '';

    var followBtn = '';
    if (SS.store && SS.store.isLoggedIn() && SS.store.getUser().id !== u.id) {
      followBtn = u.is_following
        ? '<button class="btn btn-outline btn-sm" onclick="SS.api.post(\'/social.php?action=follow\',{user_id:' + u.id + '}).then(function(){SS.ProfileCard.hide();SS.ui.toast(\'Đã bỏ theo dõi\',\'success\')})">Đang theo dõi' + (u.is_mutual ? ' 🤝' : '') + '</button>'
        : '<button class="btn btn-primary btn-sm" onclick="SS.api.post(\'/social.php?action=follow\',{user_id:' + u.id + '}).then(function(){SS.ProfileCard.hide();SS.ui.toast(\'Đã theo dõi\',\'success\')})">Theo dõi</button>';
    }

    card.innerHTML = '<div style="padding:16px">'
      + '<div class="flex items-start gap-3 mb-3">'
      + '<div style="position:relative;flex-shrink:0"><img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:52px;height:52px;border-radius:50%;object-fit:cover" loading="lazy">' + onlineDot + '</div>'
      + '<div class="flex-1" style="min-width:0">'
      + '<a href="/user.html?id=' + u.id + '" style="font-weight:700;font-size:15px;color:var(--text);text-decoration:none">' + SS.utils.esc(u.fullname) + verifiedIcon + '</a>'
      + (u.shipping_company ? '<div class="text-xs text-muted">' + SS.utils.esc(u.shipping_company) + '</div>' : '')
      + '<div class="flex items-center gap-2 mt-1">' + planBadge + '</div>'
      + '</div></div>'
      + (u.bio ? '<div class="text-sm mb-3" style="line-height:1.5;color:var(--text-secondary)">' + SS.utils.esc(u.bio.substring(0, 120)) + '</div>' : '')
      + '<div class="flex gap-4 text-center mb-3">'
      + '<div><div class="font-bold">' + SS.utils.fN(u.posts) + '</div><div class="text-xs text-muted">Bài viết</div></div>'
      + '<div><div class="font-bold">' + SS.utils.fN(u.followers) + '</div><div class="text-xs text-muted">Theo dõi</div></div>'
      + '<div><div class="font-bold">' + SS.utils.fN(u.following) + '</div><div class="text-xs text-muted">Đang theo</div></div>'
      + '</div>'
      + '<div class="flex gap-2">'
      + '<a href="/user.html?id=' + u.id + '" class="btn btn-ghost btn-sm flex-1">Xem hồ sơ</a>'
      + followBtn
      + '</div></div>';

    // Position near anchor
    if (anchor) {
      var rect = anchor.getBoundingClientRect();
      card.style.top = Math.min(rect.bottom + 8, window.innerHeight - 300) + 'px';
      card.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - 316)) + 'px';
    } else {
      card.style.top = '50%';
      card.style.left = '50%';
      card.style.transform = 'translate(-50%,-50%)';
    }

    document.body.appendChild(card);
    SS.ProfileCard._el = card;

    // Close on click outside
    setTimeout(function() {
      document.addEventListener('click', SS.ProfileCard._outsideClick);
    }, 100);
  },

  _outsideClick: function(e) {
    var card = SS.ProfileCard._el;
    if (card && !card.contains(e.target)) {
      SS.ProfileCard.hide();
    }
  },

  hide: function() {
    if (SS.ProfileCard._el) {
      SS.ProfileCard._el.remove();
      SS.ProfileCard._el = null;
    }
    document.removeEventListener('click', SS.ProfileCard._outsideClick);
  },

  // Attach to elements with data-user-id
  attachAll: function(container) {
    var els = (container || document).querySelectorAll('[data-user-id]');
    for (var i = 0; i < els.length; i++) {
      (function(el) {
        if (el._pcAttached) return;
        el._pcAttached = true;
        // Desktop: hover
        if (window.innerWidth > 768) {
          el.addEventListener('mouseenter', function() {
            SS.ProfileCard._timer = setTimeout(function() {
              SS.ProfileCard.show(el.getAttribute('data-user-id'), el);
            }, 500);
          });
          el.addEventListener('mouseleave', function() {
            clearTimeout(SS.ProfileCard._timer);
          });
        }
      })(els[i]);
    }
  }
};
