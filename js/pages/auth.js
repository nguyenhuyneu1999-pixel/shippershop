/**
 * ShipperShop Page — Auth (login.html + register.html)
 * Form validation, password visibility toggle, redirect after login
 * Uses: SS.api, SS.ui, SS.store
 */
window.SS = window.SS || {};

SS.AuthPage = {

  init: function(mode) {
    // mode: 'login' or 'register'
    if (SS.store && SS.store.isLoggedIn()) {
      var redirect = new URLSearchParams(window.location.search).get('redirect') || '/';
      window.location.href = redirect;
      return;
    }

    // Password visibility toggle
    var pwFields = document.querySelectorAll('input[type="password"]');
    for (var i = 0; i < pwFields.length; i++) {
      SS.AuthPage._addEyeToggle(pwFields[i]);
    }

    // Enter key submits
    var form = document.querySelector('form, .auth-form');
    if (form) {
      form.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          if (mode === 'login') SS.AuthPage.login();
          else SS.AuthPage.register();
        }
      });
    }
  },

  _addEyeToggle: function(input) {
    var wrapper = input.parentElement;
    if (!wrapper || wrapper.querySelector('.eye-toggle')) return;
    wrapper.style.position = 'relative';
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'eye-toggle';
    btn.style.cssText = 'position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:16px;padding:4px';
    btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
    btn.onclick = function() {
      var isPass = input.type === 'password';
      input.type = isPass ? 'text' : 'password';
      btn.innerHTML = isPass ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
    };
    wrapper.appendChild(btn);
  },

  login: function() {
    var email = (document.getElementById('login-email') || document.getElementById('email') || {}).value;
    var password = (document.getElementById('login-password') || document.getElementById('password') || {}).value;

    if (!email || !email.trim()) { SS.ui.toast('Nhập email', 'warning'); return; }
    if (!password || password.length < 4) { SS.ui.toast('Nhập mật khẩu', 'warning'); return; }

    var btn = document.querySelector('.auth-submit, [type="submit"], .btn-primary');
    if (btn) { btn.disabled = true; btn.textContent = 'Đang đăng nhập...'; }

    SS.api.v1.post('/auth.php?action=login', {email: email.trim(), password: password}).then(function(d) {
      if (d.success && d.data && d.data.token) {
        localStorage.setItem('token', d.data.token);
        localStorage.setItem('user', JSON.stringify(d.data.user || d.data));
        SS.ui.toast('Đăng nhập thành công!', 'success');
        var redirect = new URLSearchParams(window.location.search).get('redirect') || '/';
        setTimeout(function() { window.location.href = redirect; }, 500);
      } else {
        SS.ui.toast(d.message || 'Sai email hoặc mật khẩu', 'error');
        if (btn) { btn.disabled = false; btn.textContent = 'Đăng nhập'; }
      }
    }).catch(function() {
      SS.ui.toast('Lỗi kết nối', 'error');
      if (btn) { btn.disabled = false; btn.textContent = 'Đăng nhập'; }
    });
  },

  register: function() {
    var fullname = (document.getElementById('reg-name') || document.getElementById('fullname') || {}).value;
    var email = (document.getElementById('reg-email') || document.getElementById('email') || {}).value;
    var password = (document.getElementById('reg-password') || document.getElementById('password') || {}).value;
    var confirm = (document.getElementById('reg-confirm') || document.getElementById('confirm') || {}).value;

    if (!fullname || fullname.trim().length < 2) { SS.ui.toast('Tên tối thiểu 2 ký tự', 'warning'); return; }
    if (!email || !email.includes('@')) { SS.ui.toast('Email không hợp lệ', 'warning'); return; }
    if (!password || password.length < 6) { SS.ui.toast('Mật khẩu tối thiểu 6 ký tự', 'warning'); return; }
    if (password !== confirm) { SS.ui.toast('Mật khẩu nhập lại không khớp', 'warning'); return; }

    var btn = document.querySelector('.auth-submit, [type="submit"], .btn-primary');
    if (btn) { btn.disabled = true; btn.textContent = 'Đang tạo...'; }

    SS.api.v1.post('/auth.php?action=register', {fullname: fullname.trim(), email: email.trim(), password: password}).then(function(d) {
      if (d.success) {
        SS.ui.toast('Đăng ký thành công!', 'success');
        if (d.data && d.data.token) {
          localStorage.setItem('token', d.data.token);
          localStorage.setItem('user', JSON.stringify(d.data.user || d.data));
          setTimeout(function() { window.location.href = '/'; }, 800);
        } else {
          setTimeout(function() { window.location.href = '/login.html'; }, 800);
        }
      } else {
        SS.ui.toast(d.message || 'Lỗi đăng ký', 'error');
        if (btn) { btn.disabled = false; btn.textContent = 'Đăng ký'; }
      }
    }).catch(function() {
      SS.ui.toast('Lỗi kết nối', 'error');
      if (btn) { btn.disabled = false; btn.textContent = 'Đăng ký'; }
    });
  },

  // Forgot password
  forgotPassword: function() {
    SS.ui.modal({
      title: 'Quên mật khẩu',
      html: '<div class="form-group"><label class="form-label">Email</label><input id="fp-email" type="email" class="form-input" placeholder="email@example.com"></div><div class="text-sm text-muted">Link đặt lại mật khẩu sẽ được gửi qua email</div>',
      confirmText: 'Gửi',
      onConfirm: function() {
        var email = document.getElementById('fp-email').value.trim();
        if (!email) { SS.ui.toast('Nhập email', 'warning'); return; }
        SS.api.v1.post('/auth.php?action=forgot_password', {email: email}).then(function() {
          SS.ui.toast('Đã gửi email đặt lại mật khẩu!', 'success');
        }).catch(function() {
          SS.ui.toast('Email không tồn tại', 'error');
        });
      }
    });
  },

  logout: function() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    SS.ui.toast('Đã đăng xuất', 'info');
    setTimeout(function() { window.location.href = '/login.html'; }, 500);
  }
};
