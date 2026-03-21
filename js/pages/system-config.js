/**
 * ShipperShop Page — Admin System Config
 * Feature toggles, limits, maintenance mode
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.SystemConfig = {

  _config: null,

  init: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/system-config.php').then(function(d) {
      SS.SystemConfig._config = d.data || {};
      SS.SystemConfig._render(el);
    }).catch(function() { el.innerHTML = '<div class="p-4 text-danger">Lỗi tải cấu hình</div>'; });
  },

  _render: function(el) {
    var c = SS.SystemConfig._config;

    var html = '<div class="card mb-3"><div class="card-header" style="color:var(--danger)"><i class="fa-solid fa-triangle-exclamation"></i> Chế độ bảo trì</div><div class="card-body">'
      + SS.SystemConfig._toggle('maintenance_mode', 'Bật bảo trì', c.maintenance_mode, 'Tất cả user sẽ thấy thông báo bảo trì')
      + '<div class="form-group mt-2"><label class="form-label text-xs">Nội dung thông báo</label><input id="sc-maint-msg" class="form-input" value="' + SS.utils.esc(c.maintenance_message || '') + '"></div>'
      + '</div></div>';

    html += '<div class="card mb-3"><div class="card-header">Đăng ký & Tài khoản</div><div class="card-body">'
      + SS.SystemConfig._toggle('registration_open', 'Cho phép đăng ký mới', c.registration_open)
      + SS.SystemConfig._toggle('require_email_verification', 'Yêu cầu xác minh email', c.require_email_verification)
      + '</div></div>';

    html += '<div class="card mb-3"><div class="card-header">Giới hạn nội dung</div><div class="card-body">'
      + SS.SystemConfig._number('max_post_length', 'Độ dài bài viết tối đa', c.max_post_length, 'ký tự')
      + SS.SystemConfig._number('max_comment_length', 'Độ dài bình luận tối đa', c.max_comment_length, 'ký tự')
      + SS.SystemConfig._number('max_images_per_post', 'Số ảnh/bài viết tối đa', c.max_images_per_post)
      + SS.SystemConfig._number('max_upload_size_mb', 'Dung lượng upload tối đa', c.max_upload_size_mb, 'MB')
      + '</div></div>';

    html += '<div class="card mb-3"><div class="card-header">Kiểm duyệt</div><div class="card-body">'
      + SS.SystemConfig._toggle('auto_moderate', 'Tự động kiểm duyệt', c.auto_moderate, 'Ẩn bài khi đạt ngưỡng báo cáo')
      + SS.SystemConfig._number('auto_hide_report_threshold', 'Ngưỡng báo cáo tự động ẩn', c.auto_hide_report_threshold, 'báo cáo')
      + '</div></div>';

    html += '<div class="card mb-3"><div class="card-header">Tính năng</div><div class="card-body">'
      + SS.SystemConfig._toggle('enable_stories', 'Stories', c.enable_stories)
      + SS.SystemConfig._toggle('enable_marketplace', 'Chợ mua bán', c.enable_marketplace)
      + SS.SystemConfig._toggle('enable_traffic', 'Cảnh báo giao thông', c.enable_traffic)
      + SS.SystemConfig._toggle('enable_polls', 'Bình chọn (Polls)', c.enable_polls)
      + SS.SystemConfig._toggle('enable_reactions', 'Reactions (emoji)', c.enable_reactions)
      + SS.SystemConfig._toggle('enable_sse', 'Real-time (SSE)', c.enable_sse)
      + '</div></div>';

    html += '<button class="btn btn-primary btn-block" onclick="SS.SystemConfig._save()"><i class="fa-solid fa-floppy-disk"></i> Lưu cấu hình</button>';

    el.innerHTML = html;
  },

  _toggle: function(key, label, value, desc) {
    return '<div class="list-item" style="cursor:pointer;padding:8px 0" onclick="var t=this.querySelector(\'[data-key]\');var on=t.getAttribute(\'data-on\')===\'1\';t.setAttribute(\'data-on\',on?\'0\':\'1\');t.style.background=on?\'var(--border)\':\'var(--primary)\';t.querySelector(\'div\').style.left=on?\'2px\':\'auto\';t.querySelector(\'div\').style.right=on?\'auto\':\'2px\'">'
      + '<div class="flex-1"><div class="text-sm">' + label + '</div>' + (desc ? '<div class="text-xs text-muted">' + desc + '</div>' : '') + '</div>'
      + '<div style="width:44px;height:24px;border-radius:12px;background:' + (value ? 'var(--primary)' : 'var(--border)') + ';position:relative;transition:background .2s" data-key="' + key + '" data-on="' + (value ? '1' : '0') + '">'
      + '<div style="width:20px;height:20px;border-radius:50%;background:#fff;position:absolute;top:2px;' + (value ? 'right:2px' : 'left:2px') + ';box-shadow:0 1px 3px rgba(0,0,0,.2);transition:all .2s"></div></div></div>';
  },

  _number: function(key, label, value, unit) {
    return '<div class="flex items-center gap-3" style="padding:6px 0"><div class="flex-1 text-sm">' + label + '</div><input data-num-key="' + key + '" type="number" class="form-input" style="width:80px;text-align:center" value="' + (value || 0) + '">' + (unit ? '<span class="text-xs text-muted">' + unit + '</span>' : '') + '</div>';
  },

  _save: function() {
    var data = {};
    // Toggles
    var toggles = document.querySelectorAll('[data-key]');
    for (var i = 0; i < toggles.length; i++) {
      data[toggles[i].getAttribute('data-key')] = toggles[i].getAttribute('data-on') === '1';
    }
    // Numbers
    var nums = document.querySelectorAll('[data-num-key]');
    for (var j = 0; j < nums.length; j++) {
      data[nums[j].getAttribute('data-num-key')] = parseInt(nums[j].value) || 0;
    }
    // Maintenance message
    var msg = document.getElementById('sc-maint-msg');
    if (msg) data.maintenance_message = msg.value;

    SS.api.post('/system-config.php', data).then(function(d) {
      SS.SystemConfig._config = d.data;
      SS.ui.toast('Đã lưu cấu hình!', 'success');
    }).catch(function() { SS.ui.toast('Lỗi lưu', 'error'); });
  }
};
