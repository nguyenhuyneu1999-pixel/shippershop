/**
 * ShipperShop Component — Post Create
 * Modal for creating/editing posts with image upload, location, emoji
 * Uses: SS.api, SS.Upload, SS.EmojiPicker, SS.LocationPicker, SS.ui
 */
window.SS = window.SS || {};

SS.PostCreate = {
  _editId: null,

  open: function(editId, prefill) {
    SS.PostCreate._editId = editId || null;
    var isEdit = !!editId;
    var user = SS.store ? SS.store.user() : null;
    if (!user) { window.location.href = '/login.html'; return; }

    var html = '<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">'
      + '<img class="avatar" src="' + SS.utils.esc(user.avatar || '/assets/img/defaults/avatar.svg') + '">'
      + '<div><div class="font-bold">' + SS.utils.esc(user.fullname) + '</div>'
      + '<div class="text-sm text-muted">' + (isEdit ? 'Sửa bài viết' : 'Đăng bài mới') + '</div></div></div>'
      + '<textarea id="pc-content" class="form-textarea" placeholder="Bạn đang nghĩ gì? Chia sẻ kinh nghiệm, hỏi đáp, review..." rows="5" style="font-size:15px;line-height:1.6;border:none;resize:none;padding:0;margin-bottom:12px">' + SS.utils.esc((prefill && prefill.content) || '') + '</textarea>'
      + '<div id="pc-previews" style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:12px"></div>'
      + '<div style="display:flex;gap:8px;flex-wrap:wrap;border-top:1px solid var(--border);padding-top:12px">'
      + '<button class="btn btn-ghost btn-sm" onclick="document.getElementById(\'pc-file-input\').click()" title="Thêm ảnh"><i class="fa-solid fa-image" style="color:#22c55e"></i> Ảnh</button>'
      + '<button class="btn btn-ghost btn-sm" onclick="SS.EmojiPicker&&SS.EmojiPicker.open(this,function(e){var t=document.getElementById(\'pc-content\');t.value+=e;t.focus();})" title="Emoji"><i class="fa-solid fa-face-smile" style="color:#f59e0b"></i> Emoji</button>'
      + '<button class="btn btn-ghost btn-sm" id="pc-loc-btn" onclick="SS.PostCreate._toggleLocation()" title="Vị trí"><i class="fa-solid fa-location-dot" style="color:#3b82f6"></i> Vị trí</button>'
      + '<input type="file" id="pc-file-input" accept="image/*" multiple style="display:none" onchange="SS.Upload&&SS.Upload.handle(\'pc-file-input\',this.files)">'
      + '</div>'
      + '<div id="pc-loc-area" style="display:none;margin-top:12px;padding:12px;background:var(--bg);border-radius:8px">'
      + '<div class="flex gap-2">'
      + '<select id="pc-province" class="form-select" style="flex:1;font-size:12px"><option value="">Tỉnh/TP</option></select>'
      + '<select id="pc-district" class="form-select" style="flex:1;font-size:12px"><option value="">Quận/Huyện</option></select>'
      + '<select id="pc-ward" class="form-select" style="flex:1;font-size:12px"><option value="">Xã/Phường</option></select>'
      + '</div></div>';

    SS.ui.modal({
      title: isEdit ? 'Sửa bài viết' : 'Tạo bài viết mới',
      html: html,
      confirmText: isEdit ? 'Lưu' : 'Đăng bài',
      onConfirm: function() { SS.PostCreate._submit(); }
    });

    // Init upload previews
    if (SS.Upload) SS.Upload.init('pc-file-input', 'pc-previews', {maxFiles: 10, maxSizeMB: 5});

    // Focus textarea
    setTimeout(function() {
      var ta = document.getElementById('pc-content');
      if (ta) ta.focus();
    }, 300);
  },

  _toggleLocation: function() {
    var area = document.getElementById('pc-loc-area');
    if (!area) return;
    var show = area.style.display === 'none';
    area.style.display = show ? 'block' : 'none';
    if (show && SS.LocationPicker) {
      SS.LocationPicker.init('pc-province', 'pc-district', 'pc-ward');
    }
  },

  _submit: function() {
    var content = document.getElementById('pc-content');
    if (!content) return;
    var text = content.value.trim();
    if (text.length < 3) { SS.ui.toast('Tối thiểu 3 ký tự', 'warning'); return; }

    var isEdit = !!SS.PostCreate._editId;

    if (isEdit) {
      SS.api.post('/posts.php?action=edit', {
        post_id: SS.PostCreate._editId,
        content: text
      }).then(function() {
        SS.ui.toast('Đã sửa bài!', 'success');
        SS.ui.closeModal();
        if (SS.Feed) SS.Feed.refresh();
      });
      return;
    }

    // New post — need to handle file upload
    var files = SS.Upload ? SS.Upload.getFiles('pc-file-input') : [];
    var loc = SS.LocationPicker ? SS.LocationPicker.getSelected() : {};

    if (files.length > 0) {
      var fd = new FormData();
      fd.append('content', text);
      if (loc.province) fd.append('province', loc.province);
      if (loc.district) fd.append('district', loc.district);
      if (loc.ward) fd.append('ward', loc.ward);
      for (var i = 0; i < files.length; i++) {
        fd.append('images[]', files[i]);
      }
      SS.ui.loading(true);
      SS.api.upload('/posts.php', fd).then(function(d) {
        SS.ui.loading(false);
        SS.ui.toast('Đã đăng bài!', 'success');
        SS.ui.closeModal();
        if (SS.Feed) SS.Feed.refresh();
      }).catch(function() {
        SS.ui.loading(false);
      });
    } else {
      var data = {content: text};
      if (loc.province) data.province = loc.province;
      if (loc.district) data.district = loc.district;
      if (loc.ward) data.ward = loc.ward;
      SS.api.post('/posts.php', data).then(function() {
        SS.ui.toast('Đã đăng bài!', 'success');
        SS.ui.closeModal();
        if (SS.Feed) SS.Feed.refresh();
      });
    }
  }
};
