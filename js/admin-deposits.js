// Admin Deposit Approval
// Usage: include on admin-wallet.html
(function(){
var TOKEN = localStorage.getItem('token');
if (!TOKEN) return;

window.loadPendingDeposits = function() {
    var el = document.getElementById('pendingDeposits');
    if (!el) return;
    el.innerHTML = '<div style="text-align:center;padding:20px;color:#999"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    fetch('/api/admin-moderation.php?action=pending_deposits', {
        headers: { 'Authorization': 'Bearer ' + TOKEN }
    }).then(function(r) { return r.json(); })
    .then(function(d) {
        if (!d.success || !d.data || !d.data.length) {
            el.innerHTML = '<div style="text-align:center;padding:30px;color:#999"><i class="fas fa-check-circle" style="font-size:24px;color:#00b14f"></i><p>Không có yêu cầu nạp tiền chờ duyệt</p></div>';
            return;
        }
        var html = '<h3 style="padding:12px 16px;margin:0;font-size:16px">Yêu cầu nạp tiền (' + d.data.length + ')</h3>';
        d.data.forEach(function(dep) {
            var av = dep.avatar ? '<img src="' + dep.avatar + '" style="width:40px;height:40px;border-radius:50%;object-fit:cover">' : '<div style="width:40px;height:40px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">' + (dep.fullname || 'U').charAt(0) + '</div>';
            html += '<div style="display:flex;gap:12px;padding:12px 16px;border-bottom:1px solid #e4e6eb;align-items:center">'
                + av
                + '<div style="flex:1;min-width:0">'
                + '<div style="font-weight:600;font-size:14px">' + (dep.fullname || '?') + '</div>'
                + '<div style="font-size:13px;color:#65676B">' + Number(dep.amount).toLocaleString('vi-VN') + 'đ · ' + (dep.bank_name || '') + '</div>'
                + '<div style="font-size:12px;color:#999">' + dep.created_at + '</div>'
                + '</div>'
                + '<div style="display:flex;gap:6px">'
                + '<button onclick="approveDeposit(' + dep.id + ',1,this)" style="padding:6px 14px;border:none;border-radius:6px;background:#00b14f;color:#fff;cursor:pointer;font-size:13px;font-weight:600">Duyệt</button>'
                + '<button onclick="approveDeposit(' + dep.id + ',0,this)" style="padding:6px 14px;border:1px solid #ddd;border-radius:6px;background:#fff;cursor:pointer;font-size:13px">Từ chối</button>'
                + '</div></div>';
        });
        el.innerHTML = html;
    })
    .catch(function() { el.innerHTML = '<div style="padding:20px;text-align:center;color:#f00">Lỗi tải dữ liệu</div>'; });
};

window.approveDeposit = function(txnId, approve, btn) {
    if (!confirm(approve ? 'Duyệt nạp tiền này?' : 'Từ chối nạp tiền này?')) return;
    btn.disabled = true;
    fetch('/api/admin-moderation.php?action=approve_deposit', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + TOKEN, 'Content-Type': 'application/json' },
        body: JSON.stringify({ transaction_id: txnId, approve: approve })
    }).then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            btn.closest('[style*="border-bottom"]').style.display = 'none';
            alert(d.message);
        } else { alert(d.message || 'Error'); btn.disabled = false; }
    })
    .catch(function() { alert('Connection error'); btn.disabled = false; });
};

// Auto-load on page ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { loadPendingDeposits(); });
} else { loadPendingDeposits(); }
})();
