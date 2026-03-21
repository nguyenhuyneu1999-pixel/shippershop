/**
 * ShipperShop Page — Admin (admin-v2.html)
 * Dashboard stats, user management, reports, deposits
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.AdminPage = {
  _tab: 'dashboard',

  init: function() {
    if (!SS.store || !SS.store.isAdmin()) {
      SS.ui.toast('Không có quyền truy cập', 'error');
      setTimeout(function() { window.location.href = '/'; }, 1500);
      return;
    }
    SS.AdminPage.loadDashboard();
  },

  loadTab: function(tab) {
    SS.AdminPage._tab = tab;
    var tabs = document.querySelectorAll('.admin-tab');
    for (var i = 0; i < tabs.length; i++) tabs[i].classList.toggle('tab-active', tabs[i].getAttribute('data-tab') === tab);

    var el = document.getElementById('admin-content');
    if (!el) return;
    el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    if (tab === 'dashboard') SS.AdminPage.loadDashboard();
    else if (tab === 'users') SS.AdminPage.loadUsers();
    else if (tab === 'reports') SS.AdminPage.loadReports();
    else if (tab === 'deposits') SS.AdminPage.loadDeposits();
    else if (tab === 'analytics') SS.AdminPage.loadAnalytics();
    else if (tab === 'system') SS.AdminPage.loadSystem();
  },

  loadDashboard: function() {
    var el = document.getElementById('admin-content');
    if (!el) return;

    SS.api.get('/admin.php?action=dashboard').then(function(d) {
      var s = d.data;
      el.innerHTML = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:24px">'
        + SS.AdminPage._statCard('Người dùng', s.users.total, s.users.today_new + ' mới', '#7C3AED')
        + SS.AdminPage._statCard('Bài viết', s.posts.total, s.posts.today + ' hôm nay', '#3b82f6')
        + SS.AdminPage._statCard('Tin nhắn', s.messages.total, '', '#22c55e')
        + SS.AdminPage._statCard('Nhóm', s.groups.total, s.groups.total_members + ' TV', '#f59e0b')
        + SS.AdminPage._statCard('Báo cáo', s.reports.pending, 'chờ xử lý', '#ef4444')
        + SS.AdminPage._statCard('Doanh thu', SS.utils.formatMoney(s.revenue.total), s.revenue.pending_deposits + ' chờ duyệt', '#ec4899')
        + '</div>'
        + '<div class="card mb-3"><div class="card-header">Hoạt động gần đây</div><div class="card-body">'
        + '<div class="text-sm text-muted">Users today: ' + s.users.today_new + ' · Posts today: ' + s.posts.today + ' · Active: ' + s.users.online + ' online</div>'
        + '</div></div>';
    }).catch(function(e) {
      el.innerHTML = '<div class="p-4 text-center text-danger">Lỗi: ' + (e && e.message ? SS.utils.esc(e.message) : 'Unknown') + '</div>';
    });
  },

  _statCard: function(label, value, sub, color) {
    return '<div class="card"><div class="card-body" style="text-align:center;padding:16px 12px">'
      + '<div style="font-size:24px;font-weight:800;color:' + color + '">' + value + '</div>'
      + '<div class="text-sm font-medium mt-1">' + label + '</div>'
      + (sub ? '<div class="text-xs text-muted mt-1">' + sub + '</div>' : '')
      + '</div></div>';
  },

  loadUsers: function() {
    var el = document.getElementById('admin-content');
    el.innerHTML = '<div class="flex gap-2 mb-3"><input id="au-search" class="form-input" placeholder="Tìm user..." style="flex:1" onkeydown="if(event.key===\'Enter\')SS.AdminPage._searchUsers()"><button class="btn btn-primary btn-sm" onclick="SS.AdminPage._searchUsers()">Tìm</button></div><div id="au-list"></div>';
    SS.AdminPage._searchUsers();
  },

  _searchUsers: function() {
    var q = document.getElementById('au-search') ? document.getElementById('au-search').value.trim() : '';
    var list = document.getElementById('au-list');
    if (!list) return;
    list.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/admin.php?action=users&limit=20' + (q ? '&search=' + encodeURIComponent(q) : '')).then(function(d) {
      var users = d.data ? (d.data.users || d.data) : [];
      if (!users.length) { list.innerHTML = '<div class="text-center text-muted p-4">Không tìm thấy</div>'; return; }
      var html = '';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        var statusBadge = u.status === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Banned</span>';
        html += '<div class="list-item">'
          + '<img class="avatar" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
          + '<div class="flex-1"><div class="list-title">#' + u.id + ' ' + SS.utils.esc(u.fullname) + ' ' + statusBadge + '</div>'
          + '<div class="list-subtitle">' + SS.utils.esc(u.email || '') + ' · ' + SS.utils.esc(u.shipping_company || '') + ' · ' + SS.utils.fN(u.total_posts || 0) + ' bài</div></div>'
          + '<div class="flex gap-1">'
          + (u.status === 'active' ? '<button class="btn btn-ghost btn-sm" onclick="SS.AdminPage.banUser(' + u.id + ')" style="color:var(--danger)" title="Ban"><i class="fa-solid fa-ban"></i></button>' : '<button class="btn btn-ghost btn-sm" onclick="SS.AdminPage.unbanUser(' + u.id + ')" style="color:var(--success)" title="Unban"><i class="fa-solid fa-check"></i></button>')
          + '</div></div>';
      }
      list.innerHTML = html;
    }).catch(function() {
      list.innerHTML = '<div class="text-center text-danger p-4">Lỗi tải</div>';
    });
  },

  banUser: function(userId) {
    SS.ui.modal({
      title: 'Ban user #' + userId,
      html: '<div class="form-group"><label class="form-label">Lý do</label><input id="ban-reason" class="form-input" placeholder="Spam, vi phạm..."></div><div class="form-group"><label class="form-label">Thời gian (ngày, 0 = vĩnh viễn)</label><input id="ban-days" type="number" class="form-input" value="7"></div>',
      confirmText: 'Ban',
      danger: true,
      onConfirm: function() {
        SS.api.post('/admin.php?action=ban_user', {
          user_id: userId,
          reason: document.getElementById('ban-reason').value.trim(),
          days: parseInt(document.getElementById('ban-days').value) || 0
        }).then(function() {
          SS.ui.toast('Đã ban user!', 'success');
          SS.AdminPage._searchUsers();
        });
      }
    });
  },

  unbanUser: function(userId) {
    SS.ui.confirm('Unban user #' + userId + '?', function() {
      SS.api.post('/admin.php?action=unban_user', {user_id: userId}).then(function() {
        SS.ui.toast('Đã unban!', 'success');
        SS.AdminPage._searchUsers();
      });
    });
  },

  loadReports: function() {
    var el = document.getElementById('admin-content');
    SS.api.get('/admin.php?action=reports').then(function(d) {
      var reports = d.data || [];
      if (!reports.length) { el.innerHTML = '<div class="empty-state"><div class="empty-icon">✅</div><div class="empty-text">Không có báo cáo chờ xử lý</div></div>'; return; }
      var html = '';
      for (var i = 0; i < reports.length; i++) {
        var r = reports[i];
        html += '<div class="card mb-3"><div class="card-body">'
          + '<div class="flex items-center gap-2 mb-2"><span class="badge badge-danger">' + SS.utils.esc(r.reason || 'report') + '</span><span class="text-sm text-muted">' + SS.utils.ago(r.created_at) + '</span></div>'
          + '<div class="text-sm">Post #' + r.post_id + ' bởi user #' + r.reporter_id + '</div>'
          + '<div class="flex gap-2 mt-3">'
          + '<button class="btn btn-danger btn-sm" onclick="SS.AdminPage.resolveReport(' + r.id + ',\'hide\')"><i class="fa-solid fa-eye-slash"></i> Ẩn bài</button>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.AdminPage.resolveReport(' + r.id + ',\'dismiss\')">Bỏ qua</button>'
          + '</div></div></div>';
      }
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = '<div class="text-center text-muted p-4">Lỗi tải</div>'; });
  },

  resolveReport: function(reportId, action) {
    SS.api.post('/admin.php?action=resolve_report', {report_id: reportId, resolution: action}).then(function() {
      SS.ui.toast('Đã xử lý!', 'success');
      SS.AdminPage.loadReports();
    });
  },

  loadDeposits: function() {
    var el = document.getElementById('admin-content');
    SS.api.get('/admin.php?action=deposits').then(function(d) {
      var deposits = d.data || [];
      if (!deposits.length) { el.innerHTML = '<div class="empty-state"><div class="empty-text">Không có yêu cầu nạp tiền</div></div>'; return; }
      var html = '';
      for (var i = 0; i < deposits.length; i++) {
        var dep = deposits[i];
        html += '<div class="card mb-3"><div class="card-body">'
          + '<div class="flex items-center gap-3">'
          + '<div class="flex-1"><div class="font-bold">User #' + dep.user_id + '</div>'
          + '<div class="text-lg font-bold" style="color:var(--success)">' + SS.utils.formatMoney(dep.amount) + '</div>'
          + '<div class="text-sm text-muted">' + SS.utils.ago(dep.created_at) + '</div></div>'
          + '<div class="flex gap-2">'
          + '<button class="btn btn-primary btn-sm" onclick="SS.AdminPage.approveDeposit(' + dep.id + ')"><i class="fa-solid fa-check"></i> Duyệt</button>'
          + '<button class="btn btn-danger btn-sm" onclick="SS.AdminPage.rejectDeposit(' + dep.id + ')"><i class="fa-solid fa-xmark"></i></button>'
          + '</div></div></div></div>';
      }
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = '<div class="text-center text-muted p-4">Lỗi tải</div>'; });
  },

  approveDeposit: function(id) {
    SS.api.post('/admin.php?action=approve_deposit', {transaction_id: id}).then(function() {
      SS.ui.toast('Đã duyệt!', 'success');
      SS.AdminPage.loadDeposits();
    });
  },

  rejectDeposit: function(id) {
    SS.ui.confirm('Từ chối nạp tiền này?', function() {
      SS.api.post('/admin.php?action=reject_deposit', {transaction_id: id}).then(function() {
        SS.ui.toast('Đã từ chối', 'info');
        SS.AdminPage.loadDeposits();
      });
    }, {danger: true});
  },

  loadSystem: function() {
    var el = document.getElementById('admin-content');
    SS.api.get('/admin.php?action=system').then(function(d) {
      var s = d.data;
      el.innerHTML = '<div class="card"><div class="card-header">Hệ thống</div><div class="card-body">'
        + '<div class="list-item"><div class="flex-1">PHP</div><div class="font-bold">' + SS.utils.esc(s.php_version) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Database</div><div class="font-bold">' + s.db_size_mb + ' MB (' + s.db_tables + ' tables)</div></div>'
        + '<div class="list-item"><div class="flex-1">Disk</div><div class="font-bold">' + SS.utils.fN(s.disk_free_mb) + ' MB free (' + s.disk_used_pct + '% used)</div></div>'
        + '<div class="list-item"><div class="flex-1">Users</div><div class="font-bold">' + SS.utils.fN(s.total_users) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Posts</div><div class="font-bold">' + SS.utils.fN(s.total_posts) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Active Stories</div><div class="font-bold">' + (s.active_stories || 0) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Pending Reports</div><div class="font-bold" style="color:' + (s.pending_reports > 0 ? 'var(--danger)' : 'var(--success)') + '">' + (s.pending_reports || 0) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Pending Deposits</div><div class="font-bold" style="color:' + (s.pending_deposits > 0 ? 'var(--warning)' : 'var(--success)') + '">' + (s.pending_deposits || 0) + '</div></div>'
        + '<div class="list-item"><div class="flex-1">Server Time</div><div class="font-bold">' + (s.server_time || '') + '</div></div>'
        + '</div></div>';
    }).catch(function() { el.innerHTML = '<div class="text-center text-muted p-4">Lỗi</div>'; });
  },

  // Analytics tab — charts
  loadAnalytics: function() {
    var el = document.getElementById('admin-content');
    if (!el) return;
    el.innerHTML = '<div class="flex gap-2 mb-3">'
      + '<button class="btn btn-sm btn-primary" onclick="SS.AdminPage._loadCharts(7)">7 ngày</button>'
      + '<button class="btn btn-sm btn-outline" onclick="SS.AdminPage._loadCharts(14)">14 ngày</button>'
      + '<button class="btn btn-sm btn-outline" onclick="SS.AdminPage._loadCharts(30)">30 ngày</button>'
      + '</div><div id="admin-charts"><div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div></div>';
    SS.AdminPage._loadCharts(7);
  },

  _loadCharts: function(days) {
    var el = document.getElementById('admin-charts');
    if (!el) return;
    el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/admin.php?action=analytics&days=' + days).then(function(d) {
      var data = d.data || {};
      el.innerHTML = '<div class="card mb-3"><div class="card-body"><div class="flex items-center gap-3 mb-2"><div class="text-lg font-bold">Active users</div><div class="badge badge-primary">' + SS.utils.fN(data.active_users || 0) + '</div></div><div class="text-sm text-muted">' + days + ' ngày qua</div></div></div>'
        + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">'
        + '<div class="card"><div class="card-body"><div id="ac-users"></div></div></div>'
        + '<div class="card"><div class="card-body"><div id="ac-posts"></div></div></div>'
        + '<div class="card"><div class="card-body"><div id="ac-likes"></div></div></div>'
        + '<div class="card"><div class="card-body"><div id="ac-views"></div></div></div>'
        + '</div>'
        + '<div class="card mt-3"><div class="card-body"><div id="ac-companies"></div></div></div>';

      // Charts
      if (SS.Charts) {
        var mkData = function(arr, color) {
          var r = [];
          for (var i = 0; i < (arr || []).length; i++) {
            r.push({label: (arr[i].day || '').substring(5), value: parseInt(arr[i].count || 0), color: color});
          }
          return r;
        };
        SS.Charts.bar('ac-users', mkData(data.user_growth, '#7C3AED'), {title: 'User mới', height: 120});
        SS.Charts.bar('ac-posts', mkData(data.post_activity, '#3b82f6'), {title: 'Bài viết', height: 120});
        SS.Charts.bar('ac-likes', mkData(data.engagement_likes, '#22c55e'), {title: 'Lượt thích', height: 120});
        SS.Charts.bar('ac-views', mkData(data.page_views, '#f59e0b'), {title: 'Page views', height: 120});

        // Company donut
        var companyColors = ['#00b14f','#d32f2f','#ff6600','#e21a1a','#ffc107','#c41230','#EE4D2D','#f5a623','#5bc500','#00aa13','#7C3AED','#3b82f6'];
        var segments = [];
        for (var j = 0; j < (data.companies || []).length; j++) {
          segments.push({label: data.companies[j].name, value: parseInt(data.companies[j].count), color: companyColors[j % companyColors.length]});
        }
        if (segments.length) SS.Charts.donut('ac-companies', segments, {title: 'Hãng vận chuyển', size: 140});
      }
    }).catch(function() {
      el.innerHTML = '<div class="text-center text-muted p-4">Lỗi tải analytics</div>';
    });
  }
};
