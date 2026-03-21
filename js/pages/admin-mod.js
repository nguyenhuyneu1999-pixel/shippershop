/**
 * ShipperShop Page — Admin Moderation
 * Report queue, resolve actions, stats
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.AdminMod = {
  _status: 'pending',

  init: function() {
    SS.AdminMod.load('pending');
  },

  load: function(status) {
    SS.AdminMod._status = status;
    var el = document.getElementById('mod-content');
    if (!el) return;
    el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/moderation.php?action=queue&status=' + status).then(function(d) {
      var data = d.data || {};
      var reports = data.reports || [];
      var stats = data.stats || {};

      var html = '<div class="flex gap-3 mb-4">'
        + '<div class="card flex-1"><div class="card-body text-center"><div style="font-size:24px;font-weight:800;color:var(--warning)">' + (stats.pending || 0) + '</div><div class="text-xs text-muted">Chờ xử lý</div></div></div>'
        + '<div class="card flex-1"><div class="card-body text-center"><div style="font-size:24px;font-weight:800;color:var(--success)">' + (stats.resolved || 0) + '</div><div class="text-xs text-muted">Đã xử lý</div></div></div>'
        + '</div>';

      html += '<div class="flex gap-2 mb-3">'
        + '<button class="btn btn-sm ' + (status === 'pending' ? 'btn-primary' : 'btn-outline') + '" onclick="SS.AdminMod.load(\'pending\')">Chờ xử lý</button>'
        + '<button class="btn btn-sm ' + (status === 'dismiss' ? 'btn-primary' : 'btn-outline') + '" onclick="SS.AdminMod.load(\'dismiss\')">Đã bỏ qua</button>'
        + '<button class="btn btn-sm ' + (status === 'hide' ? 'btn-primary' : 'btn-outline') + '" onclick="SS.AdminMod.load(\'hide\')">Đã ẩn</button>'
        + '<button class="btn btn-sm ' + (status === 'delete' ? 'btn-primary' : 'btn-outline') + '" onclick="SS.AdminMod.load(\'delete\')">Đã xóa</button>'
        + '</div>';

      if (!reports.length) {
        html += '<div class="empty-state p-4"><div class="empty-icon">✅</div><div class="empty-text">Không có báo cáo ' + status + '</div></div>';
        el.innerHTML = html;
        return;
      }

      var reasonLabels = {spam:'Spam',inappropriate:'Không phù hợp',harassment:'Quấy rối',misinformation:'Sai lệch',violence:'Bạo lực',scam:'Lừa đảo',copyright:'Bản quyền',other:'Khác'};

      for (var i = 0; i < reports.length; i++) {
        var r = reports[i];
        html += '<div class="card mb-3"><div class="card-body">'
          + '<div class="flex items-start gap-3">'
          + '<div class="flex-1">'
          + '<div class="flex items-center gap-2 mb-2">'
          + '<span class="badge badge-danger">' + SS.utils.esc(reasonLabels[r.reason] || r.reason) + '</span>'
          + '<span class="text-xs text-muted">' + SS.utils.ago(r.created_at) + '</span>'
          + '</div>'
          + '<div class="text-sm mb-2"><strong>Post #' + r.post_id + '</strong> bởi ' + SS.utils.esc(r.post_author_name || 'User') + '</div>'
          + (r.post_content ? '<div class="text-sm p-2 mb-2" style="background:var(--bg);border-radius:8px;line-height:1.5">' + SS.utils.esc((r.post_content || '').substring(0, 200)) + '</div>' : '')
          + '<div class="text-xs text-muted">Báo cáo bởi: ' + SS.utils.esc(r.reporter_name || 'User #' + r.reporter_id) + '</div>'
          + (r.detail ? '<div class="text-xs text-muted mt-1">Chi tiết: ' + SS.utils.esc(r.detail) + '</div>' : '')
          + '</div></div>';

        if (status === 'pending') {
          html += '<div class="flex gap-2 mt-3 pt-3" style="border-top:1px solid var(--border)">'
            + '<button class="btn btn-sm btn-ghost" onclick="SS.AdminMod.resolve(' + r.id + ',\'dismiss\')">Bỏ qua</button>'
            + '<button class="btn btn-sm btn-warning" onclick="SS.AdminMod.resolve(' + r.id + ',\'hide\')"><i class="fa-solid fa-eye-slash"></i> Ẩn</button>'
            + '<button class="btn btn-sm btn-danger" onclick="SS.AdminMod.resolve(' + r.id + ',\'delete\')"><i class="fa-solid fa-trash"></i> Xóa</button>'
            + '<button class="btn btn-sm btn-danger" onclick="SS.AdminMod.resolve(' + r.id + ',\'ban_user\')"><i class="fa-solid fa-ban"></i> Ban</button>'
            + '<a href="/post-detail.html?id=' + r.post_id + '" target="_blank" class="btn btn-sm btn-ghost"><i class="fa-solid fa-eye"></i></a>'
            + '</div>';
        }

        html += '</div></div>';
      }
      el.innerHTML = html;
    }).catch(function() {
      el.innerHTML = '<div class="p-4 text-center text-danger">Lỗi tải</div>';
    });
  },

  resolve: function(reportId, action) {
    var labels = {dismiss: 'Bỏ qua', hide: 'Ẩn bài', delete: 'Xóa bài', ban_user: 'Ban user'};
    SS.ui.confirm(labels[action] + ' báo cáo này?', function() {
      SS.api.post('/moderation.php?action=resolve', {report_id: reportId, resolution: action}).then(function() {
        SS.ui.toast('Đã xử lý!', 'success');
        SS.AdminMod.load(SS.AdminMod._status);
      });
    }, {danger: action !== 'dismiss'});
  }
};
