/**
 * ShipperShop Page — Traffic (traffic.html)
 * Real-time traffic alerts with filter, vote, create
 * Uses: SS.api, SS.ui, SS.LocationPicker
 */
window.SS = window.SS || {};

SS.TrafficPage = {
  _category: '',
  _alerts: [],

  init: function() {
    SS.TrafficPage.load();
    // Auto refresh every 2 minutes
    setInterval(function() { SS.TrafficPage.load(); }, 120000);
  },

  load: function() {
    var el = document.getElementById('tp-list');
    if (!el) return;

    var params = {};
    if (SS.TrafficPage._category) params.category = SS.TrafficPage._category;

    SS.api.get('/traffic.php', params).then(function(d) {
      SS.TrafficPage._alerts = d.data || [];
      SS.TrafficPage._render(el);
    }).catch(function() {
      el.innerHTML = '<div class="empty-state"><div class="empty-text">Lỗi tải cảnh báo</div></div>';
    });
  },

  _render: function(el) {
    var alerts = SS.TrafficPage._alerts;
    if (!alerts.length) {
      el.innerHTML = '<div class="empty-state"><div class="empty-icon">🚦</div><div class="empty-text">Không có cảnh báo giao thông</div><div class="text-sm text-muted">Khu vực đang thông thoáng!</div></div>';
      return;
    }

    var catIcons = {traffic:'🚗',weather:'🌧️',terrain:'🚧',warning:'⚠️',other:'📢'};
    var sevColors = {low:'var(--success)',medium:'var(--warning)',high:'var(--accent)',critical:'var(--danger)'};
    var html = '';

    for (var i = 0; i < alerts.length; i++) {
      var a = alerts[i];
      var icon = catIcons[a.category] || '📢';
      var sevColor = sevColors[a.severity] || 'var(--text-muted)';
      var confirms = parseInt(a.confirms) || 0;
      var denies = parseInt(a.denies) || 0;
      var total = confirms + denies;
      var trustPct = total > 0 ? Math.round(confirms / total * 100) : 0;

      html += '<div class="card mb-3">'
        + '<div class="card-body">'
        + '<div class="flex gap-3 items-start">'
        + '<div style="font-size:28px">' + icon + '</div>'
        + '<div class="flex-1">'
        + '<div class="flex items-center gap-2">'
        + '<span class="font-bold">' + SS.utils.esc(a.title || '') + '</span>'
        + '<span style="width:8px;height:8px;border-radius:50%;background:' + sevColor + (a.severity === 'critical' ? ';animation:pulse 1s infinite' : '') + '"></span>'
        + '</div>'
        + (a.description ? '<div class="text-sm mt-1" style="line-height:1.5">' + SS.utils.esc(a.description) + '</div>' : '')
        + '<div class="text-xs text-muted mt-2">'
        + (a.user_name ? SS.utils.esc(a.user_name) + ' · ' : '')
        + SS.utils.ago(a.created_at)
        + (a.location ? ' · 📍 ' + SS.utils.esc(a.location) : '')
        + '</div>'
        + '</div></div>'
        + '<div class="flex gap-2 mt-3" style="border-top:1px solid var(--border);padding-top:10px">'
        + '<button class="btn btn-sm btn-ghost flex-1" onclick="SS.TrafficPage.vote(' + a.id + ',\'confirm\',this)" style="color:var(--success)"><i class="fa-solid fa-check"></i> Đúng (' + confirms + ')</button>'
        + '<button class="btn btn-sm btn-ghost flex-1" onclick="SS.TrafficPage.vote(' + a.id + ',\'deny\',this)" style="color:var(--danger)"><i class="fa-solid fa-xmark"></i> Sai (' + denies + ')</button>'
        + '<button class="btn btn-sm btn-ghost" onclick="SS.TrafficPage.showComments(' + a.id + ')"><i class="fa-regular fa-comment"></i> ' + (parseInt(a.comments_count) || 0) + '</button>'
        + '</div>'
        + (total > 0 ? '<div class="progress-bar mt-2"><div class="progress-fill" style="width:' + trustPct + '%;background:' + (trustPct > 60 ? 'var(--success)' : 'var(--danger)') + '"></div></div>' : '')
        + '</div></div>';
    }
    el.innerHTML = html;
  },

  filterCategory: function(cat, el) {
    SS.TrafficPage._category = cat;
    var chips = el.parentNode.querySelectorAll('.chip');
    for (var i = 0; i < chips.length; i++) chips[i].classList.remove('chip-active');
    el.classList.add('chip-active');
    SS.TrafficPage.load();
  },

  vote: function(alertId, type, btn) {
    btn.disabled = true;
    SS.api.v1.post('/traffic.php?action=vote', {alert_id: alertId, type: type}).then(function() {
      SS.ui.toast(type === 'confirm' ? 'Đã xác nhận!' : 'Đã báo sai', 'success');
      SS.TrafficPage.load();
    }).catch(function() { btn.disabled = false; });
  },

  showComments: function(alertId) {
    SS.api.get('/traffic.php?action=comments&alert_id=' + alertId).then(function(d) {
      var cmts = d.data || [];
      var html = '';
      if (!cmts.length) {
        html = '<div class="text-center text-muted text-sm p-4">Chưa có bình luận</div>';
      } else {
        for (var i = 0; i < cmts.length; i++) {
          var c = cmts[i];
          html += '<div class="list-item" style="align-items:flex-start">'
            + '<img class="avatar avatar-sm" src="' + (c.user_avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
            + '<div class="flex-1"><div class="font-medium text-sm">' + SS.utils.esc(c.user_name || '') + ' <span class="text-muted font-normal">' + SS.utils.ago(c.created_at) + '</span></div>'
            + '<div class="text-sm mt-1">' + SS.utils.esc(c.content) + '</div></div></div>';
        }
      }
      html += '<div style="display:flex;gap:8px;padding:8px 0;border-top:1px solid var(--border)">'
        + '<input type="text" id="tp-cmt-input" class="form-input" placeholder="Bình luận..." style="flex:1">'
        + '<button class="btn btn-primary btn-sm" onclick="SS.TrafficPage._submitComment(' + alertId + ')">Gửi</button></div>';
      SS.ui.sheet({title: 'Bình luận', html: html});
    });
  },

  _submitComment: function(alertId) {
    var inp = document.getElementById('tp-cmt-input');
    if (!inp || !inp.value.trim()) return;
    SS.api.v1.post('/traffic.php?action=comment', {alert_id: alertId, content: inp.value.trim()}).then(function() {
      SS.ui.closeSheet();
      SS.ui.toast('Đã bình luận!', 'success');
    });
  },

  openCreateAlert: function() {
    var html = '<div class="form-group"><label class="form-label">Tiêu đề</label><input id="ta-title" class="form-input" placeholder="VD: Kẹt xe Nguyễn Huệ"></div>'
      + '<div class="form-group"><label class="form-label">Mô tả</label><textarea id="ta-desc" class="form-textarea" rows="3" placeholder="Chi tiết..."></textarea></div>'
      + '<div class="form-group"><label class="form-label">Loại</label><select id="ta-cat" class="form-select"><option value="traffic">Giao thông</option><option value="weather">Thời tiết</option><option value="terrain">Địa hình</option><option value="warning">Cảnh báo</option><option value="other">Khác</option></select></div>'
      + '<div class="form-group"><label class="form-label">Mức độ</label><select id="ta-sev" class="form-select"><option value="low">Thấp</option><option value="medium">Trung bình</option><option value="high">Cao</option><option value="critical">Nghiêm trọng</option></select></div>';

    SS.ui.modal({
      title: 'Báo cáo giao thông',
      html: html,
      confirmText: 'Gửi báo cáo',
      onConfirm: function() {
        var title = document.getElementById('ta-title').value.trim();
        if (!title) { SS.ui.toast('Nhập tiêu đề', 'warning'); return; }
        SS.api.v1.post('/traffic.php', {
          title: title,
          description: document.getElementById('ta-desc').value.trim(),
          category: document.getElementById('ta-cat').value,
          severity: document.getElementById('ta-sev').value
        }).then(function() {
          SS.ui.toast('Đã gửi báo cáo!', 'success');
          SS.TrafficPage.load();
        });
      }
    });
  }
};
