/**
 * ShipperShop Page — Profile Settings (profile.html)
 * Edit profile, change password, upload avatar/cover, settings
 * Uses: SS.api, SS.ui, SS.Upload
 */
window.SS = window.SS || {};

SS.ProfileSettings = {

  init: function() {
    if (!SS.store || !SS.store.isLoggedIn()) { window.location.href = '/login.html'; return; }
    SS.ProfileSettings.loadProfile();
  },

  loadProfile: function() {
    SS.api.get('/users.php?action=me').then(function(d) {
      var u = d.data;
      if (!u) return;
      var el = document.getElementById('ps-form');
      if (!el) return;

      el.innerHTML = '<div style="text-align:center;margin-bottom:24px">'
        + '<div style="position:relative;display:inline-block">'
        + '<img id="ps-avatar-img" class="avatar avatar-xxl" src="' + SS.utils.esc(u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
        + '<button class="btn btn-icon btn-primary btn-sm" style="position:absolute;bottom:0;right:0" onclick="document.getElementById(\'ps-avatar-input\').click()">'
        + '<i class="fa-solid fa-camera"></i></button>'
        + '<input type="file" id="ps-avatar-input" accept="image/*" style="display:none" onchange="SS.ProfileSettings.uploadAvatar(this)">'
        + '</div></div>'
        + '<div class="form-group"><label class="form-label">Họ tên</label><input id="ps-name" class="form-input" value="' + SS.utils.esc(u.fullname || '') + '"></div>'
        + '<div class="form-group"><label class="form-label">Email</label><input id="ps-email" class="form-input" value="' + SS.utils.esc(u.email || '') + '" disabled></div>'
        + '<div class="form-group"><label class="form-label">Số điện thoại</label><input id="ps-phone" class="form-input" value="' + SS.utils.esc(u.phone || '') + '"></div>'
        + '<div class="form-group"><label class="form-label">Hãng vận chuyển</label>'
        + '<select id="ps-company" class="form-select">'
        + '<option value="">-- Chọn hãng --</option>'
        + ['GHTK','GHN','J&T','SPX','Viettel Post','Ninja Van','BEST','Ahamove','Grab Express','Be','Gojek'].map(function(c) {
            return '<option value="' + c + '"' + (u.shipping_company === c ? ' selected' : '') + '>' + c + '</option>';
          }).join('')
        + '</select></div>'
        + '<div class="form-group"><label class="form-label">Giới thiệu</label><textarea id="ps-bio" class="form-textarea" rows="3">' + SS.utils.esc(u.bio || '') + '</textarea></div>'
        + '<button class="btn btn-primary btn-block" onclick="SS.ProfileSettings.save()">Lưu thay đổi</button>'
        + '<div class="divider"></div>'
        + '<button class="btn btn-ghost btn-block" onclick="SS.ProfileSettings.changePassword()"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</button>'
        + '<button class="btn btn-ghost btn-block text-danger" onclick="SS.ProfileSettings.deleteAccount()" style="color:var(--danger)"><i class="fa-solid fa-trash"></i> Xóa tài khoản</button>';
    });
  },

  save: function() {
    var data = {
      fullname: document.getElementById('ps-name').value.trim(),
      phone: document.getElementById('ps-phone').value.trim(),
      shipping_company: document.getElementById('ps-company').value,
      bio: document.getElementById('ps-bio').value.trim()
    };
    if (!data.fullname || data.fullname.length < 2) { SS.ui.toast('Tên tối thiểu 2 ký tự', 'warning'); return; }

    SS.api.post('/users.php?action=update_profile', data).then(function() {
      SS.ui.toast('Đã lưu!', 'success');
      SS.store.updateUser({fullname: data.fullname, shipping_company: data.shipping_company});
    });
  },

  uploadAvatar: function(input) {
    if (!input.files || !input.files[0]) return;
    var fd = new FormData();
    fd.append('avatar', input.files[0]);
    SS.ui.loading(true);
    SS.api.upload('/users.php?action=upload_avatar', fd).then(function(d) {
      SS.ui.loading(false);
      SS.ui.toast('Đã cập nhật ảnh!', 'success');
      var img = document.getElementById('ps-avatar-img');
      if (img && d.data && d.data.avatar) {
        img.src = d.data.avatar;
        SS.store.updateUser({avatar: d.data.avatar});
      }
    }).catch(function() { SS.ui.loading(false); });
  },

  changePassword: function() {
    SS.ui.modal({
      title: 'Đổi mật khẩu',
      html: '<div class="form-group"><label class="form-label">Mật khẩu cũ</label><input id="cp-old" type="password" class="form-input"></div>'
        + '<div class="form-group"><label class="form-label">Mật khẩu mới</label><input id="cp-new" type="password" class="form-input"></div>'
        + '<div class="form-group"><label class="form-label">Nhập lại</label><input id="cp-confirm" type="password" class="form-input"></div>',
      confirmText: 'Đổi',
      onConfirm: function() {
        var oldPw = document.getElementById('cp-old').value;
        var newPw = document.getElementById('cp-new').value;
        var confirm = document.getElementById('cp-confirm').value;
        if (newPw.length < 6) { SS.ui.toast('Mật khẩu tối thiểu 6 ký tự', 'warning'); return; }
        if (newPw !== confirm) { SS.ui.toast('Mật khẩu nhập lại không khớp', 'warning'); return; }
        SS.api.post('/users.php?action=change_password', {old_password: oldPw, new_password: newPw}).then(function() {
          SS.ui.toast('Đã đổi mật khẩu!', 'success');
        });
      }
    });
  },

  deleteAccount: function() {
    SS.ui.confirm('Xóa tài khoản? Dữ liệu sẽ bị xóa vĩnh viễn sau 30 ngày.', function() {
      SS.api.post('/users.php?action=delete_account', {}).then(function() {
        SS.ui.toast('Tài khoản đã bị vô hiệu hóa', 'info');
        SS.store.logout();
      });
    }, {danger: true, confirmText: 'Xóa tài khoản'});
  }
};
