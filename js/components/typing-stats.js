/**
 * ShipperShop Component — Chat/Typing Stats
 * Message activity analytics: sent, received, busiest hours, top contacts
 */
window.SS = window.SS || {};

SS.TypingStats = {
  show: function() {
    SS.api.get('/typing-stats.php').then(function(d) {
      var data = d.data || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div style="font-size:20px;font-weight:800;color:var(--primary)">' + (data.sent || 0) + '</div><div class="text-xs text-muted">Da gui</div></div>'
        + '<div class="card" style="padding:10px"><div style="font-size:20px;font-weight:800;color:var(--success)">' + (data.received || 0) + '</div><div class="text-xs text-muted">Da nhan</div></div>'
        + '<div class="card" style="padding:10px"><div style="font-size:20px;font-weight:800">' + (data.conversations || 0) + '</div><div class="text-xs text-muted">Cuoc tro chuyen</div></div></div>';

      // Avg per day
      html += '<div class="card mb-3" style="padding:10px;text-align:center"><div class="text-xs text-muted">Trung binh moi ngay</div><div class="font-bold text-lg" style="color:var(--primary)">' + (data.avg_per_day || 0) + ' tin nhan</div></div>';

      // Top contacts
      var tops = data.top_contacts || [];
      if (tops.length) {
        html += '<div class="text-sm font-bold mb-2">Nguoi nhan nhieu nhat</div>';
        for (var i = 0; i < tops.length; i++) {
          var t = tops[i];
          html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
            + '<img src="' + (t.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
            + '<span class="text-sm flex-1">' + SS.utils.esc(t.fullname) + '</span>'
            + '<span class="text-xs font-bold">' + t.msg_count + ' tin</span></div>';
        }
      }

      // Busiest hours
      var hours = data.busiest_hours || [];
      if (hours.length) {
        html += '<div class="text-sm font-bold mt-3 mb-2">Gio nhan tin nhieu nhat</div><div class="flex gap-2">';
        for (var j = 0; j < hours.length; j++) {
          html += '<div class="card" style="padding:6px 10px;text-align:center"><div class="font-bold">' + hours[j].h + ':00</div><div class="text-xs text-muted">' + hours[j].c + '</div></div>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Thong ke tin nhan', html: html});
    });
  }
};
