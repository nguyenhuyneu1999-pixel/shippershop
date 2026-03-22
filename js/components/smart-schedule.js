/**
 * ShipperShop Component — Smart Schedule
 * AI-powered optimal posting time recommendations
 */
window.SS = window.SS || {};

SS.SmartSchedule = {
  show: function() {
    SS.api.get('/smart-schedule.php').then(function(d) {
      var data = d.data || {};
      var slots = data.recommended_slots || [];
      var dayStats = data.day_stats || [];

      var html = '<div class="text-center mb-3" style="padding:12px;background:linear-gradient(135deg,var(--primary),#a855f7);color:#fff;border-radius:12px">'
        + '<div style="font-size:16px">✨ Thoi diem dang bai tot nhat</div>'
        + '<div class="text-xs" style="opacity:0.8">Dua tren du lieu ' + SS.utils.esc(data.analysis_period || '30 ngay') + '</div></div>';

      // Recommended slots
      if (slots.length) {
        html += '<div class="text-sm font-bold mb-2">Khung gio vang</div><div class="flex gap-2 mb-3" style="overflow-x:auto">';
        for (var i = 0; i < slots.length; i++) {
          var s = slots[i];
          var bg = i === 0 ? 'var(--primary)' : 'var(--border)';
          var color = i === 0 ? '#fff' : 'var(--text)';
          html += '<div style="min-width:70px;padding:10px;border-radius:10px;background:' + bg + ';color:' + color + ';text-align:center">'
            + '<div class="font-bold">' + s.time + '</div>'
            + '<div style="font-size:11px;opacity:0.8">' + s.label + '</div>'
            + '<div style="font-size:10px">⭐ ' + s.score + '</div></div>';
        }
        html += '</div>';
      }

      // Best days
      if (dayStats.length) {
        html += '<div class="text-sm font-bold mb-2">Ngay tot nhat</div><div class="flex gap-2 mb-3">';
        for (var j = 0; j < Math.min(dayStats.length, 7); j++) {
          var ds = dayStats[j];
          var isTop = j < 2;
          html += '<div class="card" style="padding:6px 10px;text-align:center' + (isTop ? ';border:2px solid var(--primary)' : '') + '">'
            + '<div class="font-bold text-sm">' + SS.utils.esc(ds.day_name || '') + '</div>'
            + '<div class="text-xs text-muted">' + Math.round(parseFloat(ds.avg_eng || 0) * 10) / 10 + '</div></div>';
        }
        html += '</div>';
      }

      // User best (if logged in)
      var userBest = data.user_best || [];
      if (userBest.length) {
        html += '<div class="text-sm font-bold mb-2">Gio tot nhat cua ban</div><div class="flex gap-2">';
        for (var k = 0; k < userBest.length; k++) {
          html += '<div class="chip chip-active">' + sprintf('%02d:00', userBest[k].h) + ' (⭐' + Math.round(parseFloat(userBest[k].avg_eng) * 10) / 10 + ')</div>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Lich dang thong minh', html: html});
    });
  },

  // Quick next best time
  getNext: function(callback) {
    SS.api.get('/smart-schedule.php?action=next').then(function(d) {
      if (callback) callback((d.data || {}).next_time || '08:00');
    });
  }
};

function sprintf(fmt) { var args = arguments; var i = 1; return fmt.replace(/%(\d+)?([sd])/g, function(m, w, t) { var v = args[i++]; if (w) v = String(v); while (String(v).length < parseInt(w || 0)) v = '0' + v; return v; }); }
