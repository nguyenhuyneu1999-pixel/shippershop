/**
 * ShipperShop Page — Admin User Management
 * Search, filter, bulk actions, user detail
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.AdminUsers = {
  _page: 1,
  _selected: [],

  init: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;

    el.innerHTML = '<div class="flex gap-2 mb-3">'
      + '<input id="au-search" class="form-input flex-1" placeholder="Tìm tên, email, SĐT..." onkeydown="if(event.key===\'Enter\')SS.AdminUsers.search()">'
      + '<button class="btn btn-primary btn-sm" onclick="SS.AdminUsers.search()"><i class="fa-solid fa-search"></i></button>'
      + '</div>'
      + '<div class="flex gap-2 mb-3 overflow-x-auto" style="flex-wrap:nowrap">'
      + '<select id="au-status" class="form-select" style="width:auto;font-size:12px"><option value="">Trạng thái</option><option value="active">Active</option><option value="banned">Banned</option><option value="deactivated">Deactivated</option></select>'
      + '<select id="au-role" class="form-select" style="width:auto;font-size:12px"><option value="">Vai trò</option><option value="user">User</option><option value="admin">Admin</option></select>'
      + '<select id="au-sort" class="form-select" style="width:auto;font-size:12px"><option value="newest">Mới nhất</option><option value="active">Hoạt động</option><option value="posts">Nhiều bài</option><option value="name">Tên A-Z</option></select>'
      + '</div>'
      + '<div id="au-bulk" style="display:none" class="flex gap-2 mb-3 p-2" style="background:var(--bg);border-radius:8px">'
      + '<span id="au-bulk-count" class="text-sm font-bold" style="line-height:32px">0 chọn</span>'
      + '<button class="btn btn-sm btn-warning" onclick="SS.AdminUsers.bulk(\'ban\')">Ban</button>'
      + '<button class="btn btn-sm btn-success" onclick="SS.AdminUsers.bulk(\'verify\')">Verify</button>'
      + '<button class="btn btn-sm btn-danger" onclick="SS.AdminUsers.bulk(\'delete\')">Xóa</button>'
      + '<button class="btn btn-sm btn-ghost" onclick="SS.AdminUsers._clearSelection()">Bỏ chọn</button>'
      + '</div>'
      + '<div id="au-results"></div>';

    SS.AdminUsers.search();
  },

  search: function() {
    SS.AdminUsers._page = 1;
    SS.AdminUsers._load();
  },

  _load: function() {
    var el = document.getElementById('au-results');
    if (!el) return;
    el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    var q = (document.getElementById('au-search') || {}).value || '';
    var status = (document.getElementById('au-status') || {}).value || '';
    var role = (document.getElementById('au-role') || {}).value || '';
    var sort = (document.getElementById('au-sort') || {}).value || 'newest';

    var params = ['action=search', 'page=' + SS.AdminUsers._page, 'sort=' + sort];
    if (q) params.push('q=' + encodeURIComponent(q));
    if (status) params.push('status=' + status);
    if (role) params.push('role=' + role);

    SS.api.get('/admin-users.php?' + params.join('&')).then(function(d) {
      var data = d.data || {};
      var users = data.users || [];
      var total = data.total || 0;

      var html = '<div class="text-xs text-muted mb-2">' + total + ' users</div>';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        var statusBadge = u.status === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">' + u.status + '</span>';
        html += '<div class="list-item" style="padding:8px 0">'
          + '<input type="checkbox" data-uid="' + u.id + '" onchange="SS.AdminUsers._toggleSelect(' + u.id + ')" style="flex-shrink:0">'
          + '<img class="avatar avatar-sm" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
          + '<div class="flex-1" style="min-width:0">'
          + '<div class="text-sm font-medium truncate">' + SS.utils.esc(u.fullname) + (u.is_verified ? ' ✓' : '') + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(u.email || '') + ' · ' + SS.utils.esc(u.shipping_company || '') + '</div>'
          + '</div>'
          + '<div class="text-right" style="flex-shrink:0">'
          + statusBadge
          + '<div class="text-xs text-muted">' + (u.total_posts || 0) + ' bài</div>'
          + '</div>'
          + '<button class="btn btn-ghost btn-xs" onclick="SS.AdminUsers.showDetail(' + u.id + ')"><i class="fa-solid fa-eye"></i></button>'
          + '</div>';
      }

      // Pagination
      if (total > 20) {
        var pages = Math.ceil(total / 20);
        html += '<div class="flex justify-center gap-2 mt-3">';
        if (SS.AdminUsers._page > 1) html += '<button class="btn btn-ghost btn-sm" onclick="SS.AdminUsers._page--;SS.AdminUsers._load()">← Trước</button>';
        html += '<span class="text-sm text-muted" style="line-height:32px">Trang ' + SS.AdminUsers._page + '/' + pages + '</span>';
        if (SS.AdminUsers._page < pages) html += '<button class="btn btn-ghost btn-sm" onclick="SS.AdminUsers._page++;SS.AdminUsers._load()">Sau →</button>';
        html += '</div>';
      }

      el.innerHTML = html;
    }).catch(function() { el.innerHTML = '<div class="p-4 text-danger">Lỗi</div>'; });
  },

  _toggleSelect: function(uid) {
    var idx = SS.AdminUsers._selected.indexOf(uid);
    if (idx >= 0) SS.AdminUsers._selected.splice(idx, 1);
    else SS.AdminUsers._selected.push(uid);
    var bulkEl = document.getElementById('au-bulk');
    var countEl = document.getElementById('au-bulk-count');
    if (bulkEl) bulkEl.style.display = SS.AdminUsers._selected.length > 0 ? 'flex' : 'none';
    if (countEl) countEl.textContent = SS.AdminUsers._selected.length + ' chọn';
  },

  _clearSelection: function() {
    SS.AdminUsers._selected = [];
    document.querySelectorAll('#au-results input[type=checkbox]').forEach(function(cb) { cb.checked = false; });
    var bulkEl = document.getElementById('au-bulk');
    if (bulkEl) bulkEl.style.display = 'none';
  },

  bulk: function(action) {
    if (!SS.AdminUsers._selected.length) return;
    var labels = {ban: 'Ban', verify: 'Verify', delete: 'Xóa'};
    SS.ui.confirm(labels[action] + ' ' + SS.AdminUsers._selected.length + ' users?', function() {
      SS.api.post('/admin-users.php?action=bulk', {user_ids: SS.AdminUsers._selected, action: action}).then(function(d) {
        SS.ui.toast(d.message || 'OK', 'success');
        SS.AdminUsers._clearSelection();
        SS.AdminUsers._load();
      });
    }, {danger: action !== 'verify'});
  },

  showDetail: function(userId) {
    SS.api.get('/admin-users.php?action=detail&user_id=' + userId).then(function(d) {
      var u = d.data || {};
      var html = '<div class="flex items-center gap-3 mb-3"><img class="avatar" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:56px;height:56px"><div><div class="font-bold text-lg">' + SS.utils.esc(u.fullname) + '</div><div class="text-sm text-muted">' + SS.utils.esc(u.email) + '</div></div></div>'
        + '<div class="flex gap-2 mb-3"><span class="badge ' + (u.status === 'active' ? 'badge-success' : 'badge-danger') + '">' + u.status + '</span><span class="badge">' + u.role + '</span>' + (u.is_verified ? '<span class="badge badge-info">Verified</span>' : '') + '</div>'
        + '<div class="list-item"><div class="flex-1">Posts</div><div class="font-bold">' + (u.stats && u.stats.posts || 0) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Reports received</div><div class="font-bold" style="color:var(--danger)">' + (u.stats && u.stats.reports_received || 0) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Joined</div><div class="text-sm">' + SS.utils.formatDateTime(u.created_at) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Last active</div><div class="text-sm">' + (u.last_active ? SS.utils.ago(u.last_active) : 'N/A') + '</div></div>';
      SS.ui.sheet({title: 'User #' + userId, html: html});
    });
  }
};
