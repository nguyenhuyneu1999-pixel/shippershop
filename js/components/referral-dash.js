/**
 * ShipperShop Component — Referral Dashboard
 * Shows invite link, stats, recent referrals, leaderboard
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.ReferralDash = {

  show: function() {
    if (!SS.store || !SS.store.isLoggedIn()) {
      SS.ui.toast('Dang nhap de xem', 'warning'); return;
    }
    SS.api.get('/referral-dashboard.php').then(function(d) {
      var data = d.data || {};
      var html = '<div class="card mb-3" style="padding:16px;text-align:center;background:linear-gradient(135deg,var(--primary),#a855f7);color:#fff;border-radius:12px">'
        + '<div style="font-size:14px;opacity:0.9">Link gioi thieu cua ban</div>'
        + '<div style="font-size:16px;font-weight:700;margin:8px 0;word-break:break-all">' + SS.utils.esc(data.invite_url || '') + '</div>'
        + '<div class="flex gap-2 justify-center">'
        + '<button class="btn btn-sm" style="background:#fff;color:var(--primary)" onclick="SS.utils.copyText(\'' + SS.utils.esc(data.invite_url || '') + '\');SS.ui.toast(\'Da copy!\',\'success\')"><i class="fa-solid fa-copy"></i> Copy</button>'
        + '<button class="btn btn-sm" style="background:#fff3;color:#fff;border:1px solid #fff5" onclick="SS.QRShare&&SS.QRShare.show(\'invite\',' + (SS.store.getUser().id || 0) + ',\'Gioi thieu ShipperShop\')"><i class="fa-solid fa-qrcode"></i> QR</button>'
        + '</div></div>';

      // Stats
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:12px"><div style="font-size:22px;font-weight:800;color:var(--primary)">' + (data.total_referred || 0) + '</div><div class="text-xs text-muted">Tong</div></div>'
        + '<div class="card" style="padding:12px"><div style="font-size:22px;font-weight:800;color:var(--success)">' + (data.this_month || 0) + '</div><div class="text-xs text-muted">Thang nay</div></div>'
        + '<div class="card" style="padding:12px"><div style="font-size:22px;font-weight:800;color:var(--warning)">' + SS.utils.formatMoney(data.earnings || 0) + '</div><div class="text-xs text-muted">Thu nhap</div></div></div>';

      // Recent
      var recent = data.recent || [];
      if (recent.length) {
        html += '<div class="text-sm font-bold mb-2">Gioi thieu gan day</div>';
        for (var i = 0; i < recent.length; i++) {
          var r = recent[i];
          html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
            + '<img src="' + (r.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
            + '<span class="text-sm flex-1">' + SS.utils.esc(r.fullname) + '</span>'
            + '<span class="text-xs text-muted">' + SS.utils.ago(r.created_at) + '</span></div>';
        }
      }

      SS.ui.sheet({title: 'Gioi thieu ban be', html: html});
    });
  }
};
