/**
 * ShipperShop Component — Profile Completion
 * Shows completion percentage + checklist of missing items
 * Uses: SS.api, SS.store, SS.ui
 */
window.SS = window.SS || {};

SS.ProfileComplete = SS.ProfileComplete || {

  render: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/users.php?action=me').then(function(d) {
      var u = d.data;
      if (!u) return;

      var checks = [
        {key: 'avatar', label: 'Ảnh đại diện', done: !!u.avatar && u.avatar.indexOf('default') === -1, action: '/profile.html'},
        {key: 'bio', label: 'Giới thiệu bản thân', done: !!(u.bio && u.bio.length > 5), action: '/profile.html'},
        {key: 'company', label: 'Hãng vận chuyển', done: !!u.shipping_company, action: '/profile.html'},
        {key: 'phone', label: 'Số điện thoại', done: !!u.phone, action: '/profile.html'},
        {key: 'post', label: 'Đăng bài đầu tiên', done: parseInt(u.total_posts || 0) > 0, action: '/'},
        {key: 'follow', label: 'Theo dõi người khác', done: parseInt(u.total_following || 0) > 0, action: '/people.html'},
      ];

      var done = 0;
      for (var i = 0; i < checks.length; i++) { if (checks[i].done) done++; }
      var pct = Math.round(done / checks.length * 100);

      // Don't show if already 100%
      if (pct >= 100) { el.innerHTML = ''; return; }

      // Dismiss check
      if (localStorage.getItem('ss_profile_complete_dismiss') === 'true') { el.innerHTML = ''; return; }

      var color = pct >= 80 ? 'var(--success)' : (pct >= 50 ? 'var(--warning)' : 'var(--primary)');

      var html = '<div class="card mb-3"><div class="card-body">'
        + '<div class="flex items-center justify-between mb-2">'
        + '<div class="font-bold text-sm">Hoàn thiện hồ sơ</div>'
        + '<button class="btn btn-ghost btn-xs" onclick="localStorage.setItem(\'ss_profile_complete_dismiss\',\'true\');this.closest(\'.card\').remove()" title="Ẩn">✕</button>'
        + '</div>'
        + '<div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">'
        + '<div style="flex:1;height:6px;background:var(--border);border-radius:3px;overflow:hidden"><div style="height:100%;width:' + pct + '%;background:' + color + ';border-radius:3px;transition:width .5s"></div></div>'
        + '<span style="font-weight:800;font-size:14px;color:' + color + '">' + pct + '%</span>'
        + '</div>';

      for (var j = 0; j < checks.length; j++) {
        var c = checks[j];
        if (!c.done) {
          html += '<a href="' + c.action + '" class="list-item" style="text-decoration:none;color:var(--text);padding:6px 0">'
            + '<div style="width:20px;height:20px;border-radius:50%;border:2px solid var(--border);flex-shrink:0"></div>'
            + '<div class="flex-1 text-sm">' + c.label + '</div>'
            + '<i class="fa-solid fa-chevron-right text-muted" style="font-size:10px"></i></a>';
        }
      }

      html += '</div></div>';
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = ''; });
  }
};
