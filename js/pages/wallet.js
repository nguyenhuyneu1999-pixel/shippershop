/**
 * ShipperShop Page — Wallet (wallet.html)
 * Balance card, subscription plans, transaction history
 * Uses: SS.api, SS.ui, SS.Gamification
 */
window.SS = window.SS || {};

SS.WalletPage = {

  init: function() {
    SS.WalletPage.loadInfo();
    SS.WalletPage.loadPlans();
  },

  loadInfo: function() {
    var el = document.getElementById('wp-info');
    if (!el) return;
    SS.api.get('/wallet.php?action=info').then(function(d) {
      var w = d.data || {};
      var sub = w.subscription;
      el.innerHTML = '<div class="card mb-3" style="background:linear-gradient(135deg,#7C3AED,#5B21B6);color:#fff;border-radius:16px">'
        + '<div class="card-body" style="text-align:center;padding:28px 20px">'
        + '<div class="text-sm" style="opacity:.8">Số dư ví</div>'
        + '<div style="font-size:32px;font-weight:800;margin:8px 0">' + SS.utils.formatMoney(w.balance || 0) + '</div>'
        + (sub && sub.plan_name ? '<div class="badge" style="background:rgba(255,255,255,.2);color:#fff;margin-top:8px">' + SS.utils.esc(sub.badge || '') + ' ' + SS.utils.esc(sub.plan_name) + '</div>' : '')
        + '<div style="display:flex;gap:12px;justify-content:center;margin-top:20px">'
        + '<button class="btn" style="background:rgba(255,255,255,.2);color:#fff" onclick="SS.WalletPage.deposit()"><i class="fa-solid fa-plus"></i> Nạp tiền</button>'
        + '<button class="btn" style="background:rgba(255,255,255,.2);color:#fff" onclick="SS.WalletPage.showTransactions()"><i class="fa-solid fa-clock-rotate-left"></i> Lịch sử</button>'
        + '</div></div></div>';
    }).catch(function() {});
  },

  loadPlans: function() {
    var el = document.getElementById('wp-plans');
    if (!el) return;
    SS.api.get('/wallet.php?action=plans').then(function(d) {
      var plans = d.data || [];
      var html = '';
      var badges = {1:'📋',2:'⭐',3:'👑',4:'💎',5:'🚀'};
      for (var i = 0; i < plans.length; i++) {
        var p = plans[i];
        var isFree = parseInt(p.price) === 0;
        var badge = badges[p.id] || '📦';
        html += '<div class="card mb-3 card-hover">'
          + '<div class="card-body" style="display:flex;gap:16px;align-items:center">'
          + '<div style="font-size:32px">' + badge + '</div>'
          + '<div class="flex-1">'
          + '<div class="font-bold">' + SS.utils.esc(p.name) + '</div>'
          + '<div class="text-sm text-muted">' + (isFree ? 'Miễn phí' : SS.utils.formatMoney(p.price) + '/tháng') + '</div>'
          + '<div class="text-xs text-muted mt-1">' + (p.post_limit > 100 ? 'Không giới hạn' : p.post_limit + ' bài/ngày') + '</div>'
          + '</div>'
          + (isFree ? '<span class="badge badge-success">Đang dùng</span>' : '<button class="btn btn-primary btn-sm" onclick="SS.WalletPage.subscribe(' + p.id + ')">Đăng ký</button>')
          + '</div></div>';
      }
      el.innerHTML = html;
    }).catch(function() {});
  },

  deposit: function() {
    SS.ui.modal({
      title: 'Nạp tiền',
      html: '<div class="form-group"><label class="form-label">Số tiền (VNĐ)</label><input type="number" id="wp-amount" class="form-input" placeholder="50000" min="10000" step="1000"></div><div class="text-sm text-muted">Chuyển khoản đến tài khoản ShipperShop. Admin sẽ duyệt trong 24h.</div>',
      confirmText: 'Gửi yêu cầu',
      onConfirm: function() {
        var amount = parseInt(document.getElementById('wp-amount').value);
        if (!amount || amount < 10000) { SS.ui.toast('Tối thiểu 10.000đ', 'warning'); return; }
        SS.api.post('/wallet.php?action=deposit', {amount: amount}).then(function() {
          SS.ui.toast('Đã gửi yêu cầu nạp tiền!', 'success');
        });
      }
    });
  },

  subscribe: function(planId) {
    SS.ui.confirm('Đăng ký gói này? Số dư sẽ bị trừ.', function() {
      SS.api.post('/wallet.php?action=subscribe', {plan_id: planId}).then(function(d) {
        SS.ui.toast(d.message || 'Đã đăng ký!', 'success');
        SS.WalletPage.loadInfo();
      });
    });
  },

  showTransactions: function() {
    SS.api.get('/wallet.php?action=transactions&limit=20').then(function(d) {
      var txns = d.data || [];
      var html = '';
      if (!txns.length) {
        html = '<div class="text-center text-muted p-4">Chưa có giao dịch</div>';
      } else {
        for (var i = 0; i < txns.length; i++) {
          var t = txns[i];
          var isPlus = parseInt(t.amount) > 0;
          html += '<div class="list-item">'
            + '<div class="flex-1"><div class="list-title">' + SS.utils.esc(t.description || t.type || '') + '</div>'
            + '<div class="list-subtitle">' + SS.utils.formatDateTime(t.created_at) + '</div></div>'
            + '<div style="font-weight:700;color:' + (isPlus ? 'var(--success)' : 'var(--danger)') + '">' + (isPlus ? '+' : '') + SS.utils.formatMoney(t.amount) + '</div>'
            + '</div>';
        }
      }
      SS.ui.sheet({title: 'Lịch sử giao dịch', html: html});
    });
  }
};
