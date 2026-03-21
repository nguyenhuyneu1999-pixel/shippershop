/**
 * ShipperShop Component — Payment UI
 * Deposit modal with amount selection, PayOS checkout, manual transfer fallback
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.Payment = {

  openDeposit: function() {
    if (!SS.store || !SS.store.isLoggedIn()) { window.location.href = '/login.html'; return; }

    var amounts = [20000, 50000, 100000, 200000, 500000, 1000000];
    var amtHtml = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px">';
    for (var i = 0; i < amounts.length; i++) {
      amtHtml += '<button class="btn btn-outline btn-sm pay-amt" onclick="document.getElementById(\'pay-amount\').value=' + amounts[i] + ';document.querySelectorAll(\'.pay-amt\').forEach(function(b){b.classList.remove(\'btn-primary\');b.classList.add(\'btn-outline\')});this.classList.remove(\'btn-outline\');this.classList.add(\'btn-primary\')">' + SS.utils.formatMoney(amounts[i]) + '</button>';
    }
    amtHtml += '</div>';

    SS.ui.modal({
      title: 'Nạp tiền vào ví',
      html: amtHtml
        + '<div class="form-group"><label class="form-label">Hoặc nhập số tiền</label>'
        + '<input id="pay-amount" type="number" class="form-input" placeholder="50000" min="10000" max="10000000" step="1000"></div>'
        + '<div class="text-xs text-muted">Tối thiểu 10.000đ · Tối đa 10.000.000đ</div>',
      confirmText: 'Thanh toán',
      onConfirm: function() {
        var amount = parseInt(document.getElementById('pay-amount').value);
        if (!amount || amount < 10000) { SS.ui.toast('Tối thiểu 10.000đ', 'warning'); return; }
        if (amount > 10000000) { SS.ui.toast('Tối đa 10.000.000đ', 'warning'); return; }
        SS.Payment._processPayment(amount);
      }
    });
  },

  _processPayment: function(amount) {
    SS.ui.loading(true);
    SS.api.post('/payment.php?action=create', {amount: amount}).then(function(d) {
      SS.ui.loading(false);
      SS.ui.closeModal();
      var data = d.data || {};

      if (data.checkout_url) {
        // PayOS redirect
        SS.Payment._showCheckout(data);
      } else if (data.bank_info) {
        // Manual transfer
        SS.Payment._showManualTransfer(data);
      } else {
        SS.ui.toast('Đã gửi yêu cầu nạp tiền', 'success');
      }
    }).catch(function() {
      SS.ui.loading(false);
      SS.ui.toast('Lỗi tạo thanh toán', 'error');
    });
  },

  _showCheckout: function(data) {
    var html = '<div style="text-align:center;padding:16px 0">'
      + '<div style="font-size:28px;font-weight:800;color:var(--primary);margin-bottom:8px">' + SS.utils.formatMoney(data.amount) + '</div>'
      + '<div class="text-sm text-muted mb-4">Mã đơn: #' + data.order_code + '</div>';

    if (data.qr_code) {
      html += '<div style="background:#fff;padding:16px;border-radius:12px;display:inline-block;margin-bottom:16px">'
        + '<img src="' + SS.utils.esc(data.qr_code) + '" style="width:200px;height:200px" alt="QR Code">'
        + '</div><div class="text-sm text-muted mb-3">Quét mã QR để thanh toán</div>';
    }

    html += '<a href="' + SS.utils.esc(data.checkout_url) + '" target="_blank" class="btn btn-primary btn-block">Mở trang thanh toán</a>'
      + '<div class="text-xs text-muted mt-3">Sau khi thanh toán, số dư sẽ tự động cập nhật</div>'
      + '</div>';

    SS.ui.modal({title: 'Thanh toán', html: html, hideConfirm: true});
  },

  _showManualTransfer: function(data) {
    var bank = data.bank_info || {};
    var html = '<div style="text-align:center;padding:12px 0">'
      + '<div style="font-size:28px;font-weight:800;color:var(--primary);margin-bottom:12px">' + SS.utils.formatMoney(data.amount) + '</div>'
      + '<div class="card" style="text-align:left"><div class="card-body">'
      + '<div class="text-sm mb-2"><span class="text-muted">Ngân hàng:</span> <strong>' + SS.utils.esc(bank.bank || '') + '</strong></div>'
      + '<div class="text-sm mb-2"><span class="text-muted">Số TK:</span> <strong>' + SS.utils.esc(bank.account || '') + '</strong> <button class="btn btn-ghost btn-xs" onclick="SS.utils.copyText(\'' + (bank.account || '') + '\')">Copy</button></div>'
      + '<div class="text-sm mb-2"><span class="text-muted">Chủ TK:</span> <strong>' + SS.utils.esc(bank.name || '') + '</strong></div>'
      + '<div class="text-sm"><span class="text-muted">Nội dung CK:</span> <strong style="color:var(--accent)">' + SS.utils.esc(bank.content || '') + '</strong> <button class="btn btn-ghost btn-xs" onclick="SS.utils.copyText(\'' + (bank.content || '') + '\')">Copy</button></div>'
      + '</div></div>'
      + '<div class="text-sm text-muted mt-3">' + SS.utils.esc(data.note || 'Admin sẽ duyệt trong 24h') + '</div>'
      + '</div>';

    SS.ui.modal({title: 'Chuyển khoản thủ công', html: html, hideConfirm: true});
  },

  // Check payment status (called from wallet page on return)
  checkReturn: function() {
    var params = new URLSearchParams(window.location.search);
    var status = params.get('payment');
    if (status === 'success') {
      SS.ui.toast('Thanh toán thành công! Số dư đang được cập nhật...', 'success', 5000);
    } else if (status === 'cancel') {
      SS.ui.toast('Đã hủy thanh toán', 'info');
    }
  }
};
