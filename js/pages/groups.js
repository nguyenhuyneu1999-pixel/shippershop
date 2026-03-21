/**
 * ShipperShop Page — Groups (groups.html)
 * Discover groups, categories, my groups
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.GroupsPage = {
  _page: 1,
  _loading: false,
  _catId: 0,
  _search: '',

  init: function() {
    SS.GroupsPage.loadCategories();
    SS.GroupsPage.loadGroups(false);
    SS.GroupsPage.loadMyGroups();
  },

  loadCategories: function() {
    var el = document.getElementById('gp-categories');
    if (!el) return;
    SS.api.get('/groups.php?action=categories').then(function(d) {
      var cats = d.data || [];
      var html = '<div class="chip chip-active" onclick="SS.GroupsPage.filterCat(0,this)">Tất cả</div>';
      for (var i = 0; i < cats.length; i++) {
        html += '<div class="chip" onclick="SS.GroupsPage.filterCat(' + cats[i].id + ',this)">' + SS.utils.esc(cats[i].name) + '</div>';
      }
      el.innerHTML = html;
    }).catch(function() {});
  },

  filterCat: function(catId, el) {
    SS.GroupsPage._catId = catId;
    SS.GroupsPage._page = 1;
    // Update active chip
    var chips = el.parentNode.querySelectorAll('.chip');
    for (var i = 0; i < chips.length; i++) chips[i].classList.remove('chip-active');
    el.classList.add('chip-active');
    SS.GroupsPage.loadGroups(false);
  },

  search: function(query) {
    SS.GroupsPage._search = query;
    SS.GroupsPage._page = 1;
    SS.GroupsPage.loadGroups(false);
  },

  loadGroups: function(append) {
    if (SS.GroupsPage._loading) return;
    SS.GroupsPage._loading = true;

    var el = document.getElementById('gp-list');
    if (!el) { SS.GroupsPage._loading = false; return; }

    if (!append) {
      el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';
    }

    var params = {action: 'discover', page: SS.GroupsPage._page, limit: 12};
    if (SS.GroupsPage._catId) params.category_id = SS.GroupsPage._catId;
    if (SS.GroupsPage._search) params.search = SS.GroupsPage._search;

    SS.api.get('/groups.php', params).then(function(d) {
      var groups = d.data ? d.data.groups : [];
      if (!append) el.innerHTML = '';

      if (!groups.length && !append) {
        el.innerHTML = '<div class="empty-state"><img src="/assets/img/defaults/no-groups.svg" style="width:100px;opacity:.5" loading="lazy"><div class="empty-text mt-3">Chưa có nhóm nào</div></div>';
        SS.GroupsPage._loading = false;
        return;
      }

      var html = '';
      for (var i = 0; i < groups.length; i++) {
        var g = groups[i];
        html += '<a href="/group.html?id=' + g.id + '" class="card card-hover mb-3" style="display:block;text-decoration:none;color:var(--text)">'
          + '<div class="card-body" style="display:flex;gap:12px;align-items:center">'
          + '<img class="avatar avatar-lg" src="' + (g.avatar || '/assets/img/defaults/avatar.svg') + '" style="border-radius:12px" loading="lazy">'
          + '<div class="flex-1" style="min-width:0">'
          + '<div class="font-bold">' + SS.utils.esc(g.name) + '</div>'
          + '<div class="text-sm text-muted">' + SS.utils.fN(g.member_count || 0) + ' thành viên'
          + (g.category_name ? ' · ' + SS.utils.esc(g.category_name) : '') + '</div>'
          + (g.description ? '<div class="text-sm text-secondary mt-1 line-clamp-2">' + SS.utils.esc(g.description) + '</div>' : '')
          + '</div>'
          + (g.is_member ? '<span class="badge badge-success">Đã tham gia</span>' : '<span class="badge badge-primary">Tham gia</span>')
          + '</div></a>';
      }
      el.insertAdjacentHTML('beforeend', html);
      SS.GroupsPage._page++;
      SS.GroupsPage._loading = false;
    }).catch(function() {
      SS.GroupsPage._loading = false;
      if (!append) el.innerHTML = '<div class="empty-state"><div class="empty-text">Lỗi tải nhóm</div></div>';
    });
  },

  loadMyGroups: function() {
    var el = document.getElementById('gp-my-groups');
    if (!el || !SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/groups.php?action=my_groups').then(function(d) {
      var groups = d.data || [];
      if (!groups.length) {
        el.innerHTML = '<div class="text-center text-muted text-sm p-4">Bạn chưa tham gia nhóm nào</div>';
        return;
      }
      var html = '';
      for (var i = 0; i < groups.length; i++) {
        var g = groups[i];
        html += '<a href="/group.html?id=' + g.id + '" class="list-item" style="text-decoration:none;color:var(--text)">'
          + '<img class="avatar avatar-sm" src="' + (g.avatar || '/assets/img/defaults/avatar.svg') + '" style="border-radius:8px" loading="lazy">'
          + '<div class="flex-1" style="min-width:0"><div class="list-title truncate">' + SS.utils.esc(g.name) + '</div>'
          + '<div class="list-subtitle">' + SS.utils.fN(g.member_count || 0) + ' thành viên</div></div></a>';
      }
      el.innerHTML = html;
    }).catch(function() {});
  }
};
