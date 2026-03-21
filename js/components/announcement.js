/**
 * ShipperShop Component — Announcement Banner
 * Shows system-wide announcements (info/warning/danger/success)
 * Auto-fetches on page load, dismissible per-announcement
 * Uses: SS.api
 */
window.SS = window.SS || {};

SS.Announcement = {

  init: function() {
    SS.api.get('/announcements.php').then(function(d) {
      var anns = d.data || [];
      if (!anns.length) return;

      var dismissed = JSON.parse(localStorage.getItem('ss_dismissed_anns') || '[]');
      var container = document.getElementById('ss-announcements');
      if (!container) {
        container = document.createElement('div');
        container.id = 'ss-announcements';
        container.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:9999';
        document.body.appendChild(container);
      }

      var html = '';
      var count = 0;
      var colors = {info:'#3b82f6',warning:'#f59e0b',danger:'#ef4444',success:'#22c55e'};

      for (var i = 0; i < anns.length; i++) {
        var a = anns[i];
        if (dismissed.indexOf(a.id) !== -1) continue;
        var bg = colors[a.type] || colors.info;
        html += '<div class="ss-ann-item" data-id="' + a.id + '" style="background:' + bg + ';color:#fff;padding:8px 16px;display:flex;align-items:center;gap:8px;font-size:13px;min-height:36px">'
          + '<div class="flex-1" style="line-height:1.4">'
          + (a.title ? '<strong>' + SS.utils.esc(a.title) + '</strong> — ' : '')
          + SS.utils.esc(a.message)
          + '</div>'
          + '<button onclick="SS.Announcement.dismiss(\'' + a.id + '\',this)" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:24px;height:24px;border-radius:50%;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0">✕</button>'
          + '</div>';
        count++;
      }

      if (count > 0) {
        container.innerHTML = html;
        // Push body down
        var h = container.offsetHeight;
        document.body.style.paddingTop = (parseInt(getComputedStyle(document.body).paddingTop || 0) + h) + 'px';
      }
    }).catch(function() {});
  },

  dismiss: function(id, btn) {
    var dismissed = JSON.parse(localStorage.getItem('ss_dismissed_anns') || '[]');
    dismissed.push(id);
    localStorage.setItem('ss_dismissed_anns', JSON.stringify(dismissed));
    var item = btn.closest('.ss-ann-item');
    if (item) {
      var h = item.offsetHeight;
      item.style.maxHeight = h + 'px';
      item.style.transition = 'max-height .3s, opacity .3s, padding .3s';
      setTimeout(function() {
        item.style.maxHeight = '0';
        item.style.opacity = '0';
        item.style.padding = '0 16px';
        setTimeout(function() { item.remove(); }, 300);
      }, 10);
      document.body.style.paddingTop = (parseInt(getComputedStyle(document.body).paddingTop) - h) + 'px';
    }
  },

  // Admin: create announcement
  create: function() {
    var html = '<div class="form-group"><label class="form-label">Tiêu đề</label><input id="ann-title" class="form-input" placeholder="Thông báo quan trọng"></div>'
      + '<div class="form-group"><label class="form-label">Nội dung</label><textarea id="ann-msg" class="form-input" rows="3" placeholder="Nội dung thông báo..."></textarea></div>'
      + '<div class="form-group"><label class="form-label">Loại</label><select id="ann-type" class="form-select"><option value="info">Thông tin (xanh)</option><option value="warning">Cảnh báo (vàng)</option><option value="danger">Quan trọng (đỏ)</option><option value="success">Tích cực (xanh lá)</option></select></div>'
      + '<div class="form-group"><label class="form-label">Kết thúc sau</label><select id="ann-dur" class="form-select"><option value="">Không giới hạn</option><option value="1">1 giờ</option><option value="6">6 giờ</option><option value="24">24 giờ</option><option value="168">7 ngày</option></select></div>';

    SS.ui.modal({
      title: 'Tạo thông báo hệ thống',
      html: html,
      confirmText: 'Đăng',
      onConfirm: function() {
        var title = (document.getElementById('ann-title') || {}).value;
        var message = (document.getElementById('ann-msg') || {}).value;
        var type = (document.getElementById('ann-type') || {}).value;
        var hours = parseInt((document.getElementById('ann-dur') || {}).value || 0);
        var endsAt = hours ? new Date(Date.now() + hours * 3600000).toISOString() : null;
        SS.api.post('/announcements.php?action=create', {title: title, message: message, type: type, ends_at: endsAt}).then(function() {
          SS.ui.toast('Đã đăng thông báo!', 'success');
          SS.ui.closeModal();
        });
      }
    });
  }
};
