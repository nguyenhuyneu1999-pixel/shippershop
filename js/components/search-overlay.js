/**
 * ShipperShop Component — Search Overlay
 * Global search with live results (users, posts, groups)
 */
window.SS = window.SS || {};

SS.Search = {
  _el: null,
  _debounceTimer: null,

  open: function() {
    if (SS.Search._el) return;
    var ov = document.createElement('div');
    ov.id = 'ss-search-overlay';
    ov.style.cssText = 'position:fixed;inset:0;background:var(--card);z-index:1500;display:flex;flex-direction:column;animation:fadeIn .15s';

    ov.innerHTML = '<div style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-bottom:1px solid var(--border)">'
      + '<button class="btn btn-icon btn-ghost" onclick="SS.Search.close()"><i class="fa-solid fa-arrow-left"></i></button>'
      + '<input type="text" id="ss-search-input" class="form-input" placeholder="Tìm kiếm..." style="flex:1;border:none;background:var(--bg);border-radius:var(--radius-full);padding:8px 16px" autofocus>'
      + '</div>'
      + '<div id="ss-search-results" style="flex:1;overflow-y:auto;padding:8px"></div>';

    document.body.appendChild(ov);
    document.body.style.overflow = 'hidden';
    SS.Search._el = ov;

    var inp = document.getElementById('ss-search-input');
    if (inp) {
      inp.focus();
      inp.addEventListener('input', function() {
        var q = this.value.trim();
        clearTimeout(SS.Search._debounceTimer);
        if (q.length < 2) {
          SS.Search._showHistory();
          return;
        }
        SS.Search._debounceTimer = setTimeout(function() {
          SS.Search._doSearch(q);
        }, 300);
      });
      inp.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') SS.Search.close();
      });
    }

    SS.Search._showHistory();
  },

  close: function() {
    if (SS.Search._el) {
      document.body.removeChild(SS.Search._el);
      SS.Search._el = null;
      document.body.style.overflow = '';
    }
  },

  _showHistory: function() {
    var el = document.getElementById('ss-search-results');
    if (!el) return;
    SS.api.get('/search.php?action=history').then(function(d) {
      var items = d.data || [];
      if (!items.length) {
        el.innerHTML = '<div class="p-4 text-center text-muted text-sm">Nhập từ khóa để tìm kiếm</div>';
        return;
      }
      var html = '<div class="px-3 py-2 text-sm text-muted flex justify-between">Lịch sử tìm kiếm <span class="text-primary cursor-pointer" onclick="SS.Search._clearHistory()">Xóa</span></div>';
      for (var i = 0; i < items.length; i++) {
        html += '<div class="list-item" onclick="document.getElementById(\'ss-search-input\').value=\'' + SS.utils.esc(items[i].query).replace(/'/g, '\\x27') + '\';SS.Search._doSearch(\'' + SS.utils.esc(items[i].query).replace(/'/g, '\\x27') + '\')">'
          + '<i class="fa-solid fa-clock-rotate-left text-muted"></i>'
          + '<span class="flex-1">' + SS.utils.esc(items[i].query) + '</span>'
          + '</div>';
      }
      el.innerHTML = html;
    }).catch(function() {
      el.innerHTML = '<div class="p-4 text-center text-muted text-sm">Nhập từ khóa để tìm kiếm</div>';
    });
  },

  _clearHistory: function() {
    SS.api.get('/search.php?action=clear_history').then(function() {
      SS.Search._showHistory();
    });
  },

  _doSearch: function(q) {
    var el = document.getElementById('ss-search-results');
    if (!el) return;
    el.innerHTML = '<div class="flex justify-center p-4"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%"></div></div>';

    SS.api.get('/search.php?action=global&q=' + encodeURIComponent(q)).then(function(d) {
      var data = d.data || {};
      var users = data.users || [];
      var posts = data.posts || [];
      var groups = data.groups || [];
      var html = '';

      if (!users.length && !posts.length && !groups.length) {
        el.innerHTML = '<div class="empty-state p-4"><div class="empty-icon">🔍</div><div class="empty-text">Không tìm thấy kết quả cho "' + SS.utils.esc(q) + '"</div></div>';
        return;
      }

      // Users
      if (users.length) {
        html += '<div class="px-3 py-2 text-sm font-medium text-muted">Người dùng</div>';
        for (var i = 0; i < users.length; i++) {
          var u = users[i];
          html += '<a href="/user.html?id=' + u.id + '" class="list-item" style="text-decoration:none">'
            + '<img class="avatar avatar-sm" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
            + '<div class="list-content"><div class="list-title">' + SS.utils.esc(u.fullname) + '</div>'
            + '<div class="list-subtitle">' + SS.utils.esc(u.shipping_company || '') + (u.total_success ? ' · ' + SS.utils.fN(u.total_success) + ' thành công' : '') + '</div></div></a>';
        }
      }

      // Posts
      if (posts.length) {
        html += '<div class="px-3 py-2 text-sm font-medium text-muted">Bài viết</div>';
        for (var j = 0; j < posts.length; j++) {
          var p = posts[j];
          html += '<a href="/post-detail.html?id=' + p.id + '" class="list-item" style="text-decoration:none">'
            + '<img class="avatar avatar-sm" src="' + (p.user_avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
            + '<div class="list-content"><div class="list-title">' + SS.utils.esc(p.user_name || '') + '</div>'
            + '<div class="list-subtitle line-clamp-2">' + SS.utils.esc(SS.utils.truncate(p.content, 80)) + '</div></div>'
            + '<div class="text-muted text-sm">' + SS.utils.fN(p.likes_count) + ' <i class="fa-solid fa-check-circle"></i></div></a>';
        }
      }

      // Groups
      if (groups.length) {
        html += '<div class="px-3 py-2 text-sm font-medium text-muted">Nhóm</div>';
        for (var k = 0; k < groups.length; k++) {
          var g = groups[k];
          html += '<a href="/group.html?id=' + g.id + '" class="list-item" style="text-decoration:none">'
            + '<img class="avatar avatar-sm" src="' + (g.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
            + '<div class="list-content"><div class="list-title">' + SS.utils.esc(g.name) + '</div>'
            + '<div class="list-subtitle">' + SS.utils.fN(g.member_count || 0) + ' thành viên</div></div></a>';
        }
      }

      el.innerHTML = html;
    }).catch(function() {
      el.innerHTML = '<div class="p-4 text-center text-muted text-sm">Lỗi tìm kiếm</div>';
    });
  }
};
