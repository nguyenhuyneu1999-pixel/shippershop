/**
 * ShipperShop Component — Daily Report
 */
window.SS = window.SS || {};

SS.DailyReport = {
  show: function(date) {
    date = date || new Date().toISOString().split('T')[0];
    SS.api.get('/daily-report.php?date=' + date).then(function(d) {
      var data = d.data || {};
      var gradeColors = {A: 'var(--success)', B: 'var(--primary)', C: 'var(--warning)', D: 'var(--danger)', F: '#999'};
      var color = gradeColors[data.grade] || '#999';

      var html = '<div class="text-center mb-3"><div style="width:70px;height:70px;border-radius:50%;border:4px solid ' + color + ';display:inline-flex;align-items:center;justify-content:center;flex-direction:column"><div style="font-size:22px;font-weight:800;color:' + color + '">' + (data.grade || 'F') + '</div><div style="font-size:10px">' + (data.score || 0) + ' pts</div></div>'
        + '<div class="text-sm mt-2">' + SS.utils.esc(data.date || '') + (data.streak ? ' 🔥' + data.streak : '') + '</div></div>';

      var items = [
        {icon: '📝', label: 'Bai viet', value: data.posts || 0},
        {icon: '❤️', label: 'Likes nhan', value: data.likes_received || 0},
        {icon: '💬', label: 'Binh luan', value: data.comments || 0},
        {icon: '⭐', label: 'XP', value: data.xp_earned || 0},
        {icon: '💌', label: 'Tin nhan', value: data.messages || 0},
        {icon: '👥', label: 'Follower moi', value: data.new_followers || 0},
      ];

      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center">';
      for (var i = 0; i < items.length; i++) {
        html += '<div class="card" style="padding:8px"><div>' + items[i].icon + '</div><div class="font-bold">' + items[i].value + '</div><div class="text-xs text-muted">' + items[i].label + '</div></div>';
      }
      html += '</div>';

      // Nav buttons
      var prev = new Date(date); prev.setDate(prev.getDate() - 1);
      var next = new Date(date); next.setDate(next.getDate() + 1);
      html += '<div class="flex justify-between mt-3"><button class="btn btn-ghost btn-sm" onclick="SS.DailyReport.show(\'' + prev.toISOString().split('T')[0] + '\')">← Hom truoc</button>'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.DailyReport.show(\'' + next.toISOString().split('T')[0] + '\')">Hom sau →</button></div>';

      SS.ui.sheet({title: 'Bao cao ngay', html: html});
    });
  }
};
