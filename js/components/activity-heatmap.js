/**
 * ShipperShop Component — Activity Heatmap
 * GitHub-style contribution heatmap for user profiles
 */
window.SS = window.SS || {};

SS.ActivityHeatmap = {
  render: function(userId, containerId, days) {
    days = days || 180;
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '<div class="text-center p-2"><div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/activity-heatmap.php?user_id=' + userId + '&days=' + days).then(function(d) {
      var data = d.data || {};
      var daysList = data.days || [];
      if (!daysList.length) { el.innerHTML = ''; return; }

      var colors = ['var(--border-light)', '#c8e6c9', '#81c784', '#4caf50', '#2e7d32'];
      var cellSize = 10, gap = 2;
      var weeks = Math.ceil(daysList.length / 7);
      var svgW = weeks * (cellSize + gap) + 20;
      var svgH = 7 * (cellSize + gap) + 30;

      var svg = '<svg width="100%" viewBox="0 0 ' + svgW + ' ' + svgH + '" style="max-width:' + svgW + 'px">';
      for (var i = 0; i < daysList.length; i++) {
        var dd = daysList[i];
        var week = Math.floor(i / 7);
        var dow = i % 7;
        var x = week * (cellSize + gap);
        var y = dow * (cellSize + gap);
        var color = colors[dd.level || 0];
        svg += '<rect x="' + x + '" y="' + y + '" width="' + cellSize + '" height="' + cellSize + '" rx="2" fill="' + color + '"><title>' + dd.date + ': ' + dd.count + '</title></rect>';
      }
      svg += '</svg>';

      var html = '<div class="mb-1" style="overflow-x:auto">' + svg + '</div>'
        + '<div class="flex justify-between text-xs text-muted">'
        + '<span>' + (data.total_contributions || 0) + ' hoat dong · ' + (data.active_days || 0) + ' ngay</span>'
        + '<span>It <span style="display:inline-flex;gap:2px">';
      for (var j = 0; j < colors.length; j++) {
        html += '<span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:' + colors[j] + '"></span>';
      }
      html += '</span> Nhieu</span></div>';
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = ''; });
  }
};
