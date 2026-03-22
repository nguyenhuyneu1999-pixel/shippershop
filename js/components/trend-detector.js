/**
 * ShipperShop Component — Trend Detector
 */
window.SS = window.SS || {};

SS.TrendDetector = {
  show: function(hours) {
    hours = hours || 24;
    SS.api.get('/trend-detector.php?hours=' + hours).then(function(d) {
      var data = d.data || {};
      var keywords = data.keywords || [];
      var hashtags = data.hashtags || [];

      var html = '<div class="flex gap-2 mb-3">';
      [6, 24, 72, 168].forEach(function(h) {
        var label = h < 24 ? h + 'h' : (h / 24) + 'd';
        html += '<div class="chip ' + (h === hours ? 'chip-active' : '') + '" onclick="SS.TrendDetector.show(' + h + ')" style="cursor:pointer">' + label + '</div>';
      });
      html += '</div>';

      html += '<div class="text-xs text-muted mb-3">' + (data.posts_analyzed || 0) + ' bai phan tich</div>';

      // Hashtags
      if (hashtags.length) {
        html += '<div class="text-sm font-bold mb-2"># Xu huong</div><div class="flex gap-2 flex-wrap mb-3">';
        for (var h = 0; h < hashtags.length; h++) {
          var size = Math.max(12, Math.min(20, 12 + hashtags[h].count));
          html += '<span style="font-size:' + size + 'px;color:var(--primary);cursor:pointer;font-weight:600">' + SS.utils.esc(hashtags[h].hashtag) + '<sup class="text-xs text-muted">' + hashtags[h].count + '</sup></span> ';
        }
        html += '</div>';
      }

      // Keywords
      if (keywords.length) {
        html += '<div class="text-sm font-bold mb-2">Tu khoa hot</div>';
        var maxCount = keywords[0] ? keywords[0].count : 1;
        for (var i = 0; i < Math.min(keywords.length, 10); i++) {
          var k = keywords[i];
          var w = Math.max(10, Math.round(k.count / maxCount * 100));
          html += '<div class="flex items-center gap-2 mb-1"><span class="text-xs" style="width:70px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + SS.utils.esc(k.keyword) + '</span>'
            + '<div style="flex:1;height:12px;background:var(--border-light);border-radius:4px"><div style="width:' + w + '%;height:100%;background:var(--primary);border-radius:4px"></div></div>'
            + '<span class="text-xs font-bold" style="width:24px;text-align:right">' + k.count + '</span></div>';
        }
      }

      SS.ui.sheet({title: '🔥 Xu huong', html: html});
    });
  },

  showRising: function() {
    SS.api.get('/trend-detector.php?action=rising&hours=24').then(function(d) {
      var rising = (d.data || {}).rising || [];
      var html = '';
      if (!rising.length) { html = '<div class="text-center text-muted p-3">Khong co xu huong moi</div>'; }
      for (var i = 0; i < rising.length; i++) {
        var r = rising[i];
        html += '<div class="flex justify-between p-2" style="border-bottom:1px solid var(--border-light)"><span class="text-sm font-medium">' + SS.utils.esc(r.keyword) + '</span>'
          + '<span class="text-xs" style="color:var(--success)">📈 +' + (r.growth === 999 ? 'NEW' : r.growth + '%') + ' (' + r.current + ')</span></div>';
      }
      SS.ui.sheet({title: '📈 Dang tang', html: html});
    });
  }
};
