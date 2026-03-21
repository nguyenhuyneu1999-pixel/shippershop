/**
 * ShipperShop Component — Verified Badge
 * Blue checkmark display + request verification flow
 */
window.SS = window.SS || {};

SS.VerifiedBadge = {

  // Render inline badge (for post cards, profile headers, etc.)
  render: function(isVerified) {
    if (!isVerified) return '';
    return ' <span title="Tài khoản đã xác minh" style="color:#3b82f6;font-size:14px;cursor:help"><i class="fa-solid fa-circle-check"></i></span>';
  },

  // Check and show verification status on profile
  showStatus: function(containerId, userId) {
    var el = document.getElementById(containerId);
    if (!el) return;

    SS.api.get('/verification.php?action=status&user_id=' + userId).then(function(d) {
      var data = d.data || {};
      if (data.is_verified) {
        el.innerHTML = '<div class="badge badge-info"><i class="fa-solid fa-circle-check"></i> Đã xác minh ' + SS.utils.formatDate(data.verified_at) + '</div>';
      } else {
        var isSelf = SS.store && SS.store.userId() === parseInt(userId);
        if (isSelf) {
          el.innerHTML = '<button class="btn btn-outline btn-sm" onclick="SS.VerifiedBadge.requestVerification()"><i class="fa-solid fa-shield-check"></i> Yêu cầu xác minh</button>';
        }
      }
    }).catch(function() {});
  },

  requestVerification: function() {
    SS.ui.modal({
      title: 'Yêu cầu xác minh tài khoản',
      html: '<div class="text-sm text-muted mb-3">Tài khoản được xác minh sẽ có dấu <span style="color:#3b82f6"><i class="fa-solid fa-circle-check"></i></span> bên cạnh tên.</div>'
        + '<div class="form-group"><label class="form-label">Hãng vận chuyển</label>'
        + '<select id="vr-company" class="form-select">'
        + ['GHTK','GHN','J&T','SPX','Viettel Post','Ninja Van','BEST','Ahamove','Grab Express','Be','Gojek'].map(function(c) {
            return '<option value="' + c + '">' + c + '</option>';
          }).join('')
        + '</select></div>'
        + '<div class="form-group"><label class="form-label">Thông tin xác minh</label>'
        + '<textarea id="vr-note" class="form-textarea" rows="3" placeholder="Mã shipper, số hợp đồng, hoặc bất kỳ thông tin nào chứng minh bạn là shipper..."></textarea></div>'
        + '<div class="text-xs text-muted">Admin sẽ xem xét trong 1-3 ngày làm việc.</div>',
      confirmText: 'Gửi yêu cầu',
      onConfirm: function() {
        var company = document.getElementById('vr-company').value;
        var note = document.getElementById('vr-note').value.trim();
        if (note.length < 10) { SS.ui.toast('Mô tả tối thiểu 10 ký tự', 'warning'); return; }
        SS.api.post('/verification.php?action=request', {shipping_company: company, note: note}).then(function(d) {
          SS.ui.toast(d.message || 'Đã gửi!', 'success');
        });
      }
    });
  }
};
