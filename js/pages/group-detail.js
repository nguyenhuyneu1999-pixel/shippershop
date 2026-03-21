/**
 * ShipperShop Page — Group Detail (group.html)
 * Posts feed, members, leaderboard, join/leave, rules
 * Uses: SS.api, SS.PostCard, SS.ui
 */
window.SS = window.SS || {};

SS.GroupDetail = {
  _groupId: null,
  _page: 1,
  _loading: false,
  _tab: 'posts',

  init: function(groupId) {
    SS.GroupDetail._groupId = groupId;
    if (!groupId) return;
    SS.GroupDetail.loadHeader();
    SS.GroupDetail.loadTab('posts');
  },

  loadHeader: function() {
    var gid = SS.GroupDetail._groupId;
    var el = document.getElementById('gd-header');
    if (!el) return;

    SS.api.get('/groups.php?action=detail&id=' + gid).then(function(d) {
      var g = d.data;
      if (!g) { el.innerHTML = '<div class="empty-state"><div class="empty-text">Nhóm không tồn tại</div></div>'; return; }

      var isMember = g.is_member;
      var myRole = g.my_role;
      var joinBtn = '';
      if (SS.store && SS.store.isLoggedIn()) {
        joinBtn = '<button class="btn ' + (isMember ? 'btn-ghost' : 'btn-primary') + '" id="gd-join-btn" onclick="SS.GroupDetail.toggleJoin()">'
          + (isMember ? '<i class="fa-solid fa-check"></i> Đã tham gia' : '<i class="fa-solid fa-plus"></i> Tham gia') + '</button>';
      }

      el.innerHTML = '<div class="card mb-3">'
        + '<div style="height:140px;background:linear-gradient(135deg,#7C3AED,#3B82F6);border-radius:12px 12px 0 0"></div>'
        + '<div class="card-body">'
        + '<div class="flex items-center gap-3" style="margin-top:-32px">'
        + '<img class="avatar avatar-xl" src="' + (g.avatar || '/assets/img/defaults/avatar.svg') + '" style="border:3px solid var(--card);border-radius:16px" loading="lazy">'
        + '<div class="flex-1">'
        + '<h1 style="font-size:20px;font-weight:800;margin:0">' + SS.utils.esc(g.name) + '</h1>'
        + '<div class="text-sm text-muted">' + SS.utils.fN(g.member_count) + ' thành viên'
        + (g.category_name ? ' · ' + SS.utils.esc(g.category_name) : '')
        + ' · ' + SS.utils.fN(g.post_count) + ' bài viết</div>'
        + '</div></div>'
        + (g.description ? '<div class="text-sm mt-3" style="line-height:1.6">' + SS.utils.esc(g.description) + '</div>' : '')
        + '<div class="flex gap-2 mt-3">' + joinBtn
        + (isMember ? ' <button class="btn btn-secondary" onclick="SS.PostCreate&&SS.PostCreate.open()"><i class="fa-solid fa-pen"></i> Đăng bài</button>' : '')
        + (myRole === 'admin' ? ' <button class="btn btn-ghost" onclick="SS.GroupDetail.openSettings()"><i class="fa-solid fa-gear"></i></button>' : '')
        + '</div></div></div>';

      // Show rules
      if (g.rules && g.rules.length) {
        var rulesEl = document.getElementById('gd-rules');
        if (rulesEl) {
          var rHtml = '<div class="card mb-3"><div class="card-header">Quy tắc nhóm</div><div class="card-body">';
          for (var i = 0; i < g.rules.length; i++) {
            rHtml += '<div class="text-sm mb-2">' + (i + 1) + '. ' + SS.utils.esc(g.rules[i].rule) + '</div>';
          }
          rHtml += '</div></div>';
          rulesEl.innerHTML = rHtml;
        }
      }

      document.title = g.name + ' | ShipperShop';
    }).catch(function() {
      el.innerHTML = '<div class="empty-state"><div class="empty-text">Lỗi tải nhóm</div></div>';
    });
  },

  loadTab: function(tab) {
    SS.GroupDetail._tab = tab;
    SS.GroupDetail._page = 1;
    var tabs = document.querySelectorAll('.gd-tab');
    for (var i = 0; i < tabs.length; i++) tabs[i].classList.toggle('tab-active', tabs[i].getAttribute('data-tab') === tab);

    var el = document.getElementById('gd-content');
    if (!el) return;

    if (tab === 'posts') {
      el.innerHTML = SS.PostCard ? SS.PostCard.skeleton(3) : '';
      SS.GroupDetail._loadPosts(el, false);
    } else if (tab === 'members') {
      el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';
      SS.GroupDetail._loadMembers(el);
    } else if (tab === 'leaderboard') {
      el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';
      SS.GroupDetail._loadLeaderboard(el);
    }
  },

  _loadPosts: function(el, append) {
    if (SS.GroupDetail._loading) return;
    SS.GroupDetail._loading = true;
    SS.api.get('/groups.php?action=posts&group_id=' + SS.GroupDetail._groupId + '&page=' + SS.GroupDetail._page + '&limit=10').then(function(d) {
      var posts = d.data ? d.data.posts : [];
      if (!append) el.innerHTML = '';
      if (posts.length && SS.PostCard) {
        el.insertAdjacentHTML('beforeend', SS.PostCard.renderFeed(posts));
        SS.GroupDetail._page++;
      } else if (!append) {
        el.innerHTML = '<div class="empty-state"><div class="empty-text">Chưa có bài viết trong nhóm</div></div>';
      }
      SS.GroupDetail._loading = false;
    }).catch(function() { SS.GroupDetail._loading = false; });
  },

  _loadMembers: function(el) {
    SS.api.get('/groups.php?action=members&group_id=' + SS.GroupDetail._groupId + '&limit=50').then(function(d) {
      var members = d.data || [];
      if (!members.length) { el.innerHTML = '<div class="empty-state"><div class="empty-text">Chưa có thành viên</div></div>'; return; }
      var roleIcons = {admin:'👑',moderator:'⭐',member:''};
      var html = '';
      for (var i = 0; i < members.length; i++) {
        var m = members[i];
        html += '<a href="/user.html?id=' + m.id + '" class="list-item" style="text-decoration:none;color:var(--text)">'
          + '<img class="avatar" src="' + (m.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
          + '<div class="flex-1"><div class="list-title">' + (roleIcons[m.role] || '') + ' ' + SS.utils.esc(m.fullname) + '</div>'
          + '<div class="list-subtitle">' + SS.utils.esc(m.shipping_company || '') + (m.is_online ? ' · <span style="color:var(--success)">Online</span>' : '') + '</div></div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(m.role || 'member') + '</div></a>';
      }
      el.innerHTML = html;
    });
  },

  _loadLeaderboard: function(el) {
    SS.api.get('/groups.php?action=leaderboard&group_id=' + SS.GroupDetail._groupId).then(function(d) {
      var leaders = d.data || [];
      if (!leaders.length) { el.innerHTML = '<div class="empty-state"><div class="empty-text">Chưa có dữ liệu</div></div>'; return; }
      var html = '';
      for (var i = 0; i < leaders.length; i++) {
        var l = leaders[i];
        var medal = i === 0 ? '🥇' : (i === 1 ? '🥈' : (i === 2 ? '🥉' : '#' + (i + 1)));
        html += '<div class="list-item">'
          + '<div style="width:32px;text-align:center;font-weight:700;font-size:' + (i < 3 ? '18' : '13') + 'px">' + medal + '</div>'
          + '<img class="avatar avatar-sm" src="' + (l.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
          + '<div class="flex-1"><div class="list-title">' + SS.utils.esc(l.fullname) + '</div>'
          + '<div class="list-subtitle">' + SS.utils.esc(l.shipping_company || '') + '</div></div>'
          + '<div class="font-bold text-primary">' + SS.utils.fN(l.score) + '</div></div>';
      }
      el.innerHTML = html;
    });
  },

  toggleJoin: function() {
    if (!SS.store || !SS.store.isLoggedIn()) { window.location.href = '/login.html'; return; }
    var btn = document.getElementById('gd-join-btn');
    if (btn) btn.disabled = true;
    SS.api.post('/groups.php?action=join', {group_id: SS.GroupDetail._groupId}).then(function(d) {
      var joined = d.data && d.data.joined;
      SS.ui.toast(joined ? 'Đã tham gia nhóm!' : 'Đã rời nhóm', 'success');
      SS.GroupDetail.loadHeader();
    }).catch(function() { if (btn) btn.disabled = false; });
  },

  openSettings: function() {
    SS.ui.sheet({
      title: 'Cài đặt nhóm',
      html: '<div class="list-item" onclick="SS.GroupDetail._editGroup()"><i class="fa-solid fa-pen"></i><div class="flex-1">Sửa thông tin nhóm</div></div>'
        + '<div class="list-item danger" onclick="SS.GroupDetail._deleteGroup()"><i class="fa-solid fa-trash" style="color:var(--danger)"></i><div class="flex-1" style="color:var(--danger)">Xóa nhóm</div></div>'
    });
  },

  _editGroup: function() {
    SS.ui.closeSheet();
    SS.ui.modal({
      title: 'Sửa nhóm',
      html: '<div class="form-group"><label class="form-label">Tên</label><input id="ge-name" class="form-input"></div>'
        + '<div class="form-group"><label class="form-label">Mô tả</label><textarea id="ge-desc" class="form-textarea" rows="3"></textarea></div>',
      confirmText: 'Lưu',
      onConfirm: function() {
        SS.api.post('/groups.php?action=edit_group', {
          group_id: SS.GroupDetail._groupId,
          name: document.getElementById('ge-name').value.trim(),
          description: document.getElementById('ge-desc').value.trim()
        }).then(function() { SS.ui.toast('Đã cập nhật!', 'success'); SS.GroupDetail.loadHeader(); });
      }
    });
  },

  _deleteGroup: function() {
    SS.ui.closeSheet();
    SS.ui.confirm('Xóa nhóm này? Không thể hoàn tác.', function() {
      SS.api.post('/groups.php?action=delete_group', {group_id: SS.GroupDetail._groupId}).then(function() {
        SS.ui.toast('Đã xóa nhóm', 'success');
        window.location.href = '/groups.html';
      });
    }, {danger: true, confirmText: 'Xóa'});
  }
};
