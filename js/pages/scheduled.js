/**
 * ShipperShop Page — Scheduled Posts (accessed from profile/post-create)
 * Manage drafts + scheduled posts
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.ScheduledPosts = {
  _tab: 'all',

  open: function() {
    var bd = SS.ui.sheet({title: 'Bài đã hẹn giờ & Nháp', maxHeight: '85vh'});
    bd.innerHTML = '<div class="tab-bar mb-3">'
      + '<div class="tab tab-active" data-tab="all" onclick="SS.ScheduledPosts.loadTab(\'all\',this)">Tất cả</div>'
      + '<div class="tab" data-tab="scheduled" onclick="SS.ScheduledPosts.loadTab(\'scheduled\',this)">Hẹn giờ</div>'
      + '<div class="tab" data-tab="draft" onclick="SS.ScheduledPosts.loadTab(\'draft\',this)">Nháp</div>'
      + '</div><div id="sp-list"></div>';
    SS.ScheduledPosts.loadTab('all');
  },

  loadTab: function(tab, el) {
    SS.ScheduledPosts._tab = tab;
    if (el) {
      var tabs = el.parentNode.querySelectorAll('.tab');
      for (var i = 0; i < tabs.length; i++) tabs[i].classList.toggle('tab-active', tabs[i].getAttribute('data-tab') === tab);
    }
    var list = document.getElementById('sp-list');
    if (!list) return;
    list.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/scheduled.php?action=list&type=' + tab).then(function(d) {
      var posts = d.data || [];
      if (!posts.length) {
        list.innerHTML = '<div class="empty-state p-4"><div class="empty-icon">' + (tab === 'draft' ? '📝' : '⏰') + '</div><div class="empty-text">Không có bài ' + (tab === 'draft' ? 'nháp' : 'hẹn giờ') + '</div></div>';
        return;
      }

      var html = '';
      for (var i = 0; i < posts.length; i++) {
        var p = posts[i];
        var isDraft = parseInt(p.is_draft);
        var badge = isDraft ? '<span class="badge badge-warning">Nháp</span>' : '<span class="badge badge-info"><i class="fa-solid fa-clock"></i> ' + SS.utils.formatDateTime(p.scheduled_at) + '</span>';

        html += '<div class="card mb-3"><div class="card-body">'
          + '<div class="flex items-start gap-3">'
          + '<div class="flex-1">'
          + '<div class="mb-2">' + badge + '</div>'
          + '<div class="text-sm line-clamp-3">' + SS.utils.esc(p.content) + '</div>'
          + '<div class="text-xs text-muted mt-2">Tạo: ' + SS.utils.formatDateTime(p.created_at) + '</div>'
          + '</div>'
          + '<div class="flex-col gap-1" style="display:flex;flex-direction:column;gap:4px">'
          + '<button class="btn btn-primary btn-sm" onclick="SS.ScheduledPosts.publishNow(' + p.id + ')" title="Đăng ngay"><i class="fa-solid fa-paper-plane"></i></button>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.ScheduledPosts.edit(' + p.id + ')" title="Sửa"><i class="fa-solid fa-pen"></i></button>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.ScheduledPosts.del(' + p.id + ')" title="Xóa" style="color:var(--danger)"><i class="fa-solid fa-trash"></i></button>'
          + '</div></div></div></div>';
      }
      list.innerHTML = html;
    }).catch(function() {
      list.innerHTML = '<div class="p-4 text-center text-muted">Lỗi tải</div>';
    });
  },

  publishNow: function(postId) {
    SS.ui.confirm('Đăng bài này ngay?', function() {
      SS.api.post('/scheduled.php?action=publish_now', {post_id: postId}).then(function() {
        SS.ui.toast('Đã đăng!', 'success');
        SS.ScheduledPosts.loadTab(SS.ScheduledPosts._tab);
      });
    });
  },

  edit: function(postId) {
    SS.ui.modal({
      title: 'Sửa bài',
      html: '<textarea id="se-content" class="form-textarea" rows="5"></textarea>'
        + '<div class="form-group mt-3"><label class="form-label">Hẹn giờ (tùy chọn)</label>'
        + '<input id="se-time" type="datetime-local" class="form-input"></div>',
      confirmText: 'Lưu',
      onConfirm: function() {
        var content = document.getElementById('se-content').value.trim();
        var time = document.getElementById('se-time').value;
        SS.api.post('/scheduled.php?action=edit', {
          post_id: postId,
          content: content || undefined,
          scheduled_at: time || undefined
        }).then(function() {
          SS.ui.toast('Đã cập nhật!', 'success');
          SS.ScheduledPosts.loadTab(SS.ScheduledPosts._tab);
        });
      }
    });
  },

  del: function(postId) {
    SS.ui.confirm('Xóa bài này?', function() {
      SS.api.post('/scheduled.php?action=delete', {post_id: postId}).then(function() {
        SS.ui.toast('Đã xóa', 'success');
        SS.ScheduledPosts.loadTab(SS.ScheduledPosts._tab);
      });
    }, {danger: true});
  },

  // Create scheduled post (called from PostCreate with schedule option)
  createScheduled: function(content, scheduledAt, isDraft) {
    return SS.api.post('/scheduled.php?action=create', {
      content: content,
      scheduled_at: scheduledAt || null,
      is_draft: isDraft ? 1 : 0
    });
  }
};
