/**
 * ShipperShop Component — Two-Factor Auth Setup
 * Setup 2FA with QR code, verify code, enable/disable
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.TwoFactor = {

  open: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/two-factor.php?action=status').then(function(d) {
      var data = d.data || {};
      if (data.enabled) {
        SS.TwoFactor._showEnabled();
      } else {
        SS.TwoFactor._showSetup();
      }
    });
  },

  _showSetup: function() {
    SS.ui.modal({
      title: 'Bật xác thực 2 bước',
      html: '<div class="text-center p-3"><div class="text-sm text-muted mb-3">Bảo vệ tài khoản bằng mã xác thực từ app (Google Authenticator, Authy)</div>'
        + '<div id="tfa-qr"><button class="btn btn-primary" onclick="SS.TwoFactor._generate()"><i class="fa-solid fa-shield-halved"></i> Tạo mã QR</button></div></div>',
      hideConfirm: true
    });
  },

  _generate: function() {
    var el = document.getElementById('tfa-qr');
    if (!el) return;
    el.innerHTML = '<div class="p-3 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.post('/two-factor.php?action=setup', {}).then(function(d) {
      var data = d.data || {};
      el.innerHTML = '<div class="text-center">'
        + '<div class="text-sm text-muted mb-3">Quét mã QR bằng app xác thực</div>'
        + '<img src="' + SS.utils.esc(data.qr_url || '') + '" style="width:200px;height:200px;border-radius:8px;border:1px solid var(--border)" alt="QR Code">'
        + '<div class="text-xs text-muted mt-2">Hoặc nhập mã: <code style="background:var(--bg);padding:2px 6px;border-radius:4px;font-weight:700">' + SS.utils.esc(data.secret || '') + '</code></div>'
        + '<div class="form-group mt-4"><label class="form-label">Nhập mã 6 số từ app</label><input id="tfa-code" type="text" class="form-input" maxlength="6" placeholder="000000" style="text-align:center;font-size:24px;letter-spacing:8px;font-weight:800"></div>'
        + '<button class="btn btn-primary btn-block" onclick="SS.TwoFactor._verify()">Xác nhận</button>'
        + '</div>';
    }).catch(function() {
      el.innerHTML = '<div class="text-danger">Lỗi tạo mã QR</div>';
    });
  },

  _verify: function() {
    var code = (document.getElementById('tfa-code') || {}).value;
    if (!code || code.length !== 6) { SS.ui.toast('Nhập đủ 6 số', 'warning'); return; }

    SS.api.post('/two-factor.php?action=verify', {code: code}).then(function(d) {
      SS.ui.toast(d.message || 'Đã bật 2FA!', 'success');
      SS.ui.closeModal();
    }).catch(function(e) {
      SS.ui.toast(e && e.message ? e.message : 'Mã không đúng', 'error');
    });
  },

  _showEnabled: function() {
    SS.ui.modal({
      title: 'Xác thực 2 bước',
      html: '<div class="text-center p-3">'
        + '<div style="font-size:48px;margin-bottom:12px">🛡️</div>'
        + '<div class="font-bold" style="color:var(--success)">Đã bật xác thực 2 bước</div>'
        + '<div class="text-sm text-muted mt-2">Tài khoản của bạn được bảo vệ bằng mã xác thực</div>'
        + '</div>',
      confirmText: 'Tắt 2FA',
      danger: true,
      onConfirm: function() { SS.TwoFactor._disable(); }
    });
  },

  _disable: function() {
    SS.ui.modal({
      title: 'Tắt xác thực 2 bước',
      html: '<div class="form-group"><label class="form-label">Mật khẩu</label><input id="tfa-pw" type="password" class="form-input"></div>'
        + '<div class="form-group"><label class="form-label">Mã 2FA hiện tại</label><input id="tfa-disable-code" type="text" class="form-input" maxlength="6" placeholder="000000"></div>',
      confirmText: 'Tắt 2FA',
      danger: true,
      onConfirm: function() {
        var pw = (document.getElementById('tfa-pw') || {}).value;
        var code = (document.getElementById('tfa-disable-code') || {}).value;
        SS.api.post('/two-factor.php?action=disable', {password: pw, code: code}).then(function(d) {
          SS.ui.toast(d.message || 'Đã tắt', 'success');
        });
      }
    });
  }
};
