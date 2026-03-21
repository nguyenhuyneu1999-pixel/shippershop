/**
 * ShipperShop Component — Post Calendar
 * Monthly grid showing published + scheduled posts per day
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.Calendar = {

  _month: new Date().getMonth() + 1,
  _year: new Date().getFullYear(),

  render: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/calendar.php?month=' + SS.Calendar._month + '&year=' + SS.Calendar._year).then(function(d) {
      var data = d.data || {};
      var days = data.days || {};
      var stats = data.stats || {};
      var m = data.month;
      var y = data.year;

      var monthNames = ['','Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];

      var html = '<div class="card"><div class="card-header flex justify-between items-center">'
        + '<button class="btn btn-ghost btn-xs" onclick="SS.Calendar._prev(\'' + containerId + '\')"><i class="fa-solid fa-chevron-left"></i></button>'
        + '<span class="font-bold">' + monthNames[m] + ' ' + y + '</span>'
        + '<button class="btn btn-ghost btn-xs" onclick="SS.Calendar._next(\'' + containerId + '\')"><i class="fa-solid fa-chevron-right"></i></button>'
        + '</div><div class="card-body" style="padding:8px">';

      // Stats row
      html += '<div class="flex gap-3 text-center mb-3" style="font-size:11px">'
        + '<div class="flex-1"><span class="font-bold" style="color:var(--primary)">' + (stats.posts || 0) + '</span> bài</div>'
        + '<div class="flex-1"><span class="font-bold" style="color:var(--success)">' + (stats.likes || 0) + '</span> thành công</div>'
        + '<div class="flex-1"><span class="font-bold" style="color:var(--info)">' + (stats.scheduled || 0) + '</span> hẹn giờ</div>'
        + '<div class="flex-1"><span class="font-bold" style="color:var(--warning)">' + (stats.drafts || 0) + '</span> nháp</div>'
        + '</div>';

      // Day headers
      var dayHeaders = ['CN','T2','T3','T4','T5','T6','T7'];
      html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center">';
      for (var dh = 0; dh < 7; dh++) {
        html += '<div style="font-size:10px;font-weight:600;color:var(--text-muted);padding:4px 0">' + dayHeaders[dh] + '</div>';
      }

      // Calculate first day offset
      var firstDay = new Date(y, m - 1, 1).getDay();
      var daysInMonth = new Date(y, m, 0).getDate();
      var today = new Date().toISOString().split('T')[0];

      // Empty cells
      for (var e = 0; e < firstDay; e++) html += '<div></div>';

      // Day cells
      for (var dd = 1; dd <= daysInMonth; dd++) {
        var dayKey = y + '-' + String(m).padStart(2, '0') + '-' + String(dd).padStart(2, '0');
        var dayData = days[dayKey] || null;
        var isToday = dayKey === today;
        var hasPublished = dayData && dayData.published > 0;
        var hasScheduled = dayData && dayData.scheduled && dayData.scheduled.length > 0;

        var bg = isToday ? 'var(--primary)' : (hasPublished ? 'var(--primary)' + '20' : 'transparent');
        var color = isToday ? '#fff' : 'var(--text)';
        var border = hasScheduled ? '2px solid var(--warning)' : (isToday ? 'none' : '1px solid var(--border-light,#f0f0f0)');

        html += '<div style="aspect-ratio:1;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:6px;background:' + bg + ';color:' + color + ';font-size:12px;font-weight:' + (isToday || hasPublished ? '700' : '400') + ';border:' + border + ';cursor:pointer;position:relative" onclick="SS.Calendar._showDay(\'' + dayKey + '\')">'
          + dd
          + (hasPublished ? '<div style="font-size:8px;color:' + (isToday ? '#fff' : 'var(--primary)') + '">' + dayData.published + '</div>' : '')
          + '</div>';
      }

      html += '</div></div></div>';
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = '<div class="p-3 text-muted text-center">Lỗi tải</div>'; });
  },

  _prev: function(id) {
    SS.Calendar._month--;
    if (SS.Calendar._month < 1) { SS.Calendar._month = 12; SS.Calendar._year--; }
    SS.Calendar.render(id);
  },

  _next: function(id) {
    SS.Calendar._month++;
    if (SS.Calendar._month > 12) { SS.Calendar._month = 1; SS.Calendar._year++; }
    SS.Calendar.render(id);
  },

  _showDay: function(day) {
    SS.api.get('/calendar.php?action=day&day=' + day).then(function(d) {
      var data = d.data || {};
      var posts = data.posts || [];
      var scheduled = data.scheduled || [];
      if (!posts.length && !scheduled.length) { SS.ui.toast('Không có bài viết ngày này', 'info'); return; }

      var html = '';
      if (scheduled.length) {
        html += '<div class="text-xs font-bold text-muted mb-2">HẸN GIỜ</div>';
        for (var i = 0; i < scheduled.length; i++) {
          var s = scheduled[i];
          html += '<div class="list-item" style="padding:6px 0"><div style="width:16px;color:var(--warning)"><i class="fa-solid fa-clock"></i></div><div class="flex-1 text-sm truncate">' + SS.utils.esc((s.content || '').substring(0, 60)) + '</div><div class="text-xs text-muted">' + (s.scheduled_at || '').substring(11, 16) + '</div></div>';
        }
      }
      if (posts.length) {
        html += '<div class="text-xs font-bold text-muted mb-2 mt-3">ĐÃ ĐĂNG</div>';
        for (var j = 0; j < posts.length; j++) {
          var p = posts[j];
          html += '<a href="/post-detail.html?id=' + p.id + '" class="list-item" style="padding:6px 0;text-decoration:none;color:var(--text)"><div class="flex-1 text-sm truncate">' + SS.utils.esc((p.content || '').substring(0, 60)) + '</div><div class="text-xs text-muted">' + SS.utils.fN(p.likes_count) + '❤️</div></a>';
        }
      }
      SS.ui.sheet({title: 'Ngày ' + day, html: html});
    });
  }
};
