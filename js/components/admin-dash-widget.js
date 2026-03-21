/**
 * ShipperShop Component — Admin Dashboard Widget
 * Renders admin dashboard stats from cached API
 * Uses: SS.api, SS.Charts
 */
window.SS = window.SS || {};

SS.AdminDashWidget = {

  render: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;

    SS.api.get('/admin-stats-cache.php?action=dashboard').then(function(d) {
      var data = d.data || {};
      var u = data.users || {};
      var p = data.posts || {};
      var r = data.revenue || {};
      var pending = data.pending || {};

      var html = '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:16px">'
        + SS.AdminDashWidget._card('👥', 'Thành viên', SS.utils.fN(u.total || 0), '+' + (u.today || 0) + ' hôm nay', 'var(--primary)')
        + SS.AdminDashWidget._card('📝', 'Bài viết', SS.utils.fN(p.total || 0), '+' + (p.today || 0) + ' hôm nay', 'var(--info)')
        + SS.AdminDashWidget._card('💰', 'Doanh thu', SS.utils.formatMoney(r.total || 0), SS.utils.formatMoney(r.month || 0) + '/tháng', 'var(--success)')
        + SS.AdminDashWidget._card('⚠️', 'Cần xử lý', (pending.reports || 0) + ' báo cáo', (pending.deposits || 0) + ' nạp tiền', 'var(--warning)')
        + '</div>';

      // Alerts
      if ((pending.reports || 0) > 0 || (pending.deposits || 0) > 0) {
        html += '<div class="card mb-3" style="border-left:3px solid var(--warning);padding:10px 14px">'
          + '<div class="font-bold text-sm mb-1">⚡ Cần xử lý ngay</div>';
        if (pending.reports > 0) html += '<div class="text-xs">• ' + pending.reports + ' báo cáo chờ duyệt</div>';
        if (pending.deposits > 0) html += '<div class="text-xs">• ' + pending.deposits + ' yêu cầu nạp tiền</div>';
        html += '</div>';
      }

      // Top users
      var topUsers = data.top_users || [];
      if (topUsers.length) {
        html += '<div class="text-sm font-bold mb-2">Top thành viên</div>';
        for (var i = 0; i < topUsers.length; i++) {
          var tu = topUsers[i];
          html += '<div class="flex items-center gap-2 mb-2">'
            + '<span class="text-xs text-muted" style="width:16px">#' + (i + 1) + '</span>'
            + '<img src="' + (tu.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:28px;height:28px;border-radius:50%" loading="lazy">'
            + '<div class="flex-1 text-sm">' + SS.utils.esc(tu.fullname) + '</div>'
            + '<span class="text-xs text-muted">' + tu.total_posts + ' bài</span></div>';
        }
      }

      html += '<div class="text-xs text-muted text-center mt-3">Cập nhật ' + (data.cached_at ? SS.utils.ago(data.cached_at) : 'vừa xong') + '</div>';
      el.innerHTML = html;
    }).catch(function() {
      el.innerHTML = '<div class="text-center text-muted p-3">Lỗi tải dashboard</div>';
    });
  },

  _card: function(icon, label, value, sub, color) {
    return '<div class="card" style="padding:12px">'
      + '<div class="flex items-center gap-2 mb-1"><span style="font-size:18px">' + icon + '</span><span class="text-xs text-muted">' + label + '</span></div>'
      + '<div style="font-size:20px;font-weight:800;color:' + color + '">' + value + '</div>'
      + '<div class="text-xs text-muted">' + sub + '</div></div>';
  }
};
