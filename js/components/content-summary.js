/**
 * ShipperShop Component — Content Summary
 * Visual summary: trending hashtags, peak hours, engagement rate
 */
window.SS = window.SS || {};

SS.ContentSummary = {
  show: function(hours) {
    hours = hours || 24;
    SS.api.get('/content-summary.php?hours=' + hours).then(function(d) {
      var data = d.data || {};

      var html = '<div class="flex gap-2 mb-3">';
      [24, 72, 168].forEach(function(h) {
        var active = h === hours ? 'chip-active' : '';
        html += '<div class="chip ' + active + '" onclick="SS.ContentSummary.show(' + h + ')" style="cursor:pointer">' + (h === 24 ? '24h' : (h === 72 ? '3 ngay' : '7 ngay')) + '</div>';
      });
      html += '</div>';

      // Engagement rate
      html += '<div class="card mb-3" style="padding:12px;text-align:center">'
        + '<div class="text-xs text-muted">Ty le tuong tac</div>'
        + '<div style="font-size:28px;font-weight:800;color:var(--primary)">' + (data.engagement_rate || 0) + '%</div>'
        + '<div class="text-xs text-muted">' + (data.total_posts_in_period || 0) + ' bai viet</div></div>';

      // Hashtags
      var tags = data.hashtags || {};
      var tagKeys = Object.keys(tags);
      if (tagKeys.length) {
        html += '<div class="text-sm font-bold mb-2"># Hashtags noi bat</div><div class="flex gap-2 flex-wrap mb-3">';
        for (var i = 0; i < Math.min(tagKeys.length, 10); i++) {
          var size = Math.max(12, Math.min(20, 12 + tags[tagKeys[i]]));
          html += '<span class="chip" style="font-size:' + size + 'px">#' + SS.utils.esc(tagKeys[i]) + ' <small class="text-muted">' + tags[tagKeys[i]] + '</small></span>';
        }
        html += '</div>';
      }

      // Peak hours
      var peaks = data.peak_hours || [];
      if (peaks.length) {
        html += '<div class="text-sm font-bold mb-2">Gio cao diem</div><div class="flex gap-2 mb-3">';
        for (var j = 0; j < peaks.length; j++) {
          html += '<div class="card" style="padding:6px 10px;text-align:center"><div class="font-bold">' + peaks[j].h + ':00</div><div class="text-xs text-muted">' + peaks[j].c + ' bai</div></div>';
        }
        html += '</div>';
      }

      // Top words
      var words = data.top_words || {};
      var wordKeys = Object.keys(words);
      if (wordKeys.length) {
        html += '<div class="text-sm font-bold mb-2">Tu khoa pho bien</div><div class="flex gap-1 flex-wrap">';
        for (var k = 0; k < Math.min(wordKeys.length, 15); k++) {
          var opacity = Math.max(0.4, Math.min(1, words[wordKeys[k]] / (words[wordKeys[0]] || 1)));
          html += '<span style="font-size:13px;opacity:' + opacity + ';padding:2px 6px">' + SS.utils.esc(wordKeys[k]) + '</span>';
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Tong quan noi dung', html: html});
    });
  }
};
