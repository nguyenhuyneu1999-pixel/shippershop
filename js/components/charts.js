/**
 * ShipperShop Component — Analytics Charts
 * Simple CSS bar/line charts for admin dashboard
 * No external libraries — pure CSS + JS
 */
window.SS = window.SS || {};

SS.Charts = {

  // Render bar chart
  bar: function(containerId, data, opts) {
    // data: [{label, value, color?}]
    opts = opts || {};
    var el = document.getElementById(containerId);
    if (!el || !data || !data.length) return;

    var maxVal = 0;
    for (var i = 0; i < data.length; i++) {
      if (data[i].value > maxVal) maxVal = data[i].value;
    }
    if (maxVal === 0) maxVal = 1;

    var barWidth = Math.max(20, Math.floor((100 / data.length) - 2));
    var html = '<div style="display:flex;align-items:flex-end;gap:4px;height:' + (opts.height || 160) + 'px;padding:0 8px">';
    for (var j = 0; j < data.length; j++) {
      var d = data[j];
      var pct = Math.round(d.value / maxVal * 100);
      var color = d.color || 'var(--primary)';
      html += '<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px">'
        + '<div class="text-xs font-bold" style="color:' + color + '">' + SS.utils.fN(d.value) + '</div>'
        + '<div style="width:100%;height:' + pct + '%;min-height:4px;background:' + color + ';border-radius:4px 4px 0 0;transition:height .5s"></div>'
        + '<div class="text-xs text-muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%">' + SS.utils.esc(d.label) + '</div>'
        + '</div>';
    }
    html += '</div>';
    if (opts.title) html = '<div class="text-sm font-bold mb-2">' + SS.utils.esc(opts.title) + '</div>' + html;
    el.innerHTML = html;
  },

  // Render sparkline (mini line chart)
  sparkline: function(containerId, values, opts) {
    opts = opts || {};
    var el = document.getElementById(containerId);
    if (!el || !values || !values.length) return;

    var w = opts.width || 120;
    var h = opts.height || 40;
    var max = Math.max.apply(null, values);
    var min = Math.min.apply(null, values);
    var range = max - min || 1;

    var points = [];
    for (var i = 0; i < values.length; i++) {
      var x = (i / (values.length - 1)) * w;
      var y = h - ((values[i] - min) / range) * (h - 4) - 2;
      points.push(x.toFixed(1) + ',' + y.toFixed(1));
    }

    var color = opts.color || 'var(--primary)';
    var trending = values[values.length - 1] >= values[0];

    el.innerHTML = '<svg width="' + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '">'
      + '<polyline points="' + points.join(' ') + '" fill="none" stroke="' + color + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
      + '</svg>'
      + (opts.showTrend ? '<span style="font-size:10px;color:' + (trending ? 'var(--success)' : 'var(--danger)') + ';margin-left:4px">' + (trending ? '↑' : '↓') + '</span>' : '');
  },

  // Render donut/pie chart
  donut: function(containerId, segments, opts) {
    // segments: [{label, value, color}]
    opts = opts || {};
    var el = document.getElementById(containerId);
    if (!el || !segments || !segments.length) return;

    var total = 0;
    for (var i = 0; i < segments.length; i++) total += segments[i].value;
    if (total === 0) total = 1;

    var size = opts.size || 120;
    var r = size / 2 - 8;
    var cx = size / 2;
    var cy = size / 2;
    var circumference = 2 * Math.PI * r;
    var offset = 0;

    var svgParts = '';
    var legendParts = '';

    for (var j = 0; j < segments.length; j++) {
      var s = segments[j];
      var pct = s.value / total;
      var dashLen = pct * circumference;
      var dashGap = circumference - dashLen;
      svgParts += '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="' + s.color + '" stroke-width="12" stroke-dasharray="' + dashLen.toFixed(1) + ' ' + dashGap.toFixed(1) + '" stroke-dashoffset="' + (-offset).toFixed(1) + '" transform="rotate(-90 ' + cx + ' ' + cy + ')"/>';
      offset += dashLen;

      legendParts += '<div style="display:flex;align-items:center;gap:6px;font-size:11px">'
        + '<div style="width:10px;height:10px;border-radius:2px;background:' + s.color + ';flex-shrink:0"></div>'
        + '<span class="text-muted">' + SS.utils.esc(s.label) + '</span>'
        + '<span class="font-bold">' + Math.round(pct * 100) + '%</span></div>';
    }

    el.innerHTML = '<div style="display:flex;align-items:center;gap:16px">'
      + '<svg width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '">'
      + '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="var(--border-light)" stroke-width="12"/>'
      + svgParts
      + '<text x="' + cx + '" y="' + (cy + 5) + '" text-anchor="middle" style="font-size:16px;font-weight:800;fill:var(--text)">' + SS.utils.fN(total) + '</text>'
      + '</svg>'
      + '<div style="display:flex;flex-direction:column;gap:6px">' + legendParts + '</div></div>';

    if (opts.title) el.innerHTML = '<div class="text-sm font-bold mb-3">' + SS.utils.esc(opts.title) + '</div>' + el.innerHTML;
  },

  // Load admin analytics and render
  loadAdminAnalytics: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/admin.php?action=analytics&days=14').then(function(d) {
      var data = d.data || {};
      var ug = data.user_growth || [];
      var pa = data.post_activity || [];

      var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">'
        + '<div class="card"><div class="card-body"><div id="ac-users"></div></div></div>'
        + '<div class="card"><div class="card-body"><div id="ac-posts"></div></div></div>'
        + '</div>';
      el.innerHTML = html;

      // User growth
      if (ug.length) {
        var ugData = [];
        for (var i = 0; i < ug.length; i++) {
          ugData.push({label: ug[i].date ? ug[i].date.substring(5) : '', value: parseInt(ug[i].count || 0), color: 'var(--primary)'});
        }
        SS.Charts.bar('ac-users', ugData, {title: 'User mới (14 ngày)', height: 120});
      }

      // Post activity
      if (pa.length) {
        var paData = [];
        for (var j = 0; j < pa.length; j++) {
          paData.push({label: pa[j].date ? pa[j].date.substring(5) : '', value: parseInt(pa[j].count || 0), color: 'var(--info)'});
        }
        SS.Charts.bar('ac-posts', paData, {title: 'Bài viết (14 ngày)', height: 120});
      }
    }).catch(function() {
      el.innerHTML = '<div class="text-center text-muted p-4">Lỗi tải analytics</div>';
    });
  }
};
