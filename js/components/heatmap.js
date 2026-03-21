/**
 * ShipperShop Component — Activity Heatmap
 * GitHub-style activity grid showing daily activity levels
 * Uses: SS.api, SS.utils
 */
window.SS = window.SS || {};

SS.Heatmap = {

  render: function(containerId, userId, opts) {
    opts = opts || {};
    var el = document.getElementById(containerId);
    if (!el) return;

    SS.api.get('/heatmap.php?user_id=' + userId + '&days=' + (opts.days || 365)).then(function(d) {
      var data = d.data;
      if (!data || !data.days || !data.days.length) { el.innerHTML = ''; return; }

      var days = data.days;
      var maxVal = Math.max(1, data.max_value);
      var cellSize = opts.cellSize || 11;
      var gap = opts.gap || 2;
      var weeks = Math.ceil(days.length / 7);
      var width = weeks * (cellSize + gap) + 40;
      var height = 7 * (cellSize + gap) + 30;

      var colors = ['var(--border)', '#9be9a8', '#40c463', '#30a14e', '#216e39'];

      var html = '<div class="card"><div class="card-header flex justify-between items-center">Hoạt động'
        + '<span class="text-xs text-muted">' + data.active_days + ' ngày hoạt động · Streak dài nhất: ' + data.longest_streak + '</span></div>'
        + '<div class="card-body" style="overflow-x:auto;padding:12px">'
        + '<svg width="' + width + '" height="' + height + '" style="font-family:inherit">';

      // Day labels
      var dayLabels = ['', 'T2', '', 'T4', '', 'T6', ''];
      for (var di = 0; di < 7; di++) {
        if (dayLabels[di]) {
          html += '<text x="0" y="' + (di * (cellSize + gap) + cellSize + 20) + '" fill="var(--text-muted)" font-size="9">' + dayLabels[di] + '</text>';
        }
      }

      // Cells
      for (var i = 0; i < days.length; i++) {
        var week = Math.floor(i / 7);
        var dow = i % 7;
        var val = days[i].value;
        var level = val === 0 ? 0 : Math.min(4, Math.ceil(val / maxVal * 4));
        var x = week * (cellSize + gap) + 30;
        var y = dow * (cellSize + gap) + 16;
        html += '<rect x="' + x + '" y="' + y + '" width="' + cellSize + '" height="' + cellSize + '" rx="2" fill="' + colors[level] + '" data-day="' + days[i].day + '" data-value="' + val + '"><title>' + days[i].day + ': ' + val + ' hoạt động</title></rect>';
      }

      html += '</svg></div></div>';
      el.innerHTML = html;
    }).catch(function() { el.innerHTML = ''; });
  }
};
