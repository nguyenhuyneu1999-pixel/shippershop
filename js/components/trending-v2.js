window.SS = window.SS || {};
SS.TrendingV2 = {
  show: function(hours) {
    hours = hours || 24;
    SS.api.get('/trending-v2.php?hours=' + hours).then(function(d) {
      var data = d.data || {};
      var html = '<div class="flex gap-2 mb-3">';
      [6, 24, 72].forEach(function(h) { html += '<div class="chip ' + (h === hours ? 'chip-active' : '') + '" onclick="SS.TrendingV2.show(' + h + ')" style="cursor:pointer">' + h + 'h</div>'; });
      html += '</div><div class="text-xs text-muted mb-3">' + (data.posts_analyzed || 0) + ' bai phan tich</div>';
      // Keywords
      var kw = data.keywords || [];
      if (kw.length) {
        html += '<div class="text-sm font-bold mb-2">🔥 Tu khoa</div><div class="flex gap-2 flex-wrap mb-3">';
        for (var i = 0; i < Math.min(kw.length, 12); i++) {
          var size = Math.max(11, Math.min(18, 11 + Math.round(kw[i].count / (kw[0].count || 1) * 7)));
          html += '<span style="font-size:' + size + 'px;color:var(--primary);font-weight:600;cursor:pointer">' + SS.utils.esc(kw[i].word) + '<sup style="font-size:9px;color:var(--text-muted)">' + kw[i].count + '</sup> </span>';
        }
        html += '</div>';
      }
      // Hashtags
      var ht = data.hashtags || [];
      if (ht.length) {
        html += '<div class="text-sm font-bold mb-2">#️⃣ Hashtags</div>';
        for (var j = 0; j < ht.length; j++) html += '<span class="chip mb-1" style="margin-right:4px">' + SS.utils.esc(ht[j].tag) + ' (' + ht[j].count + ')</span>';
        html += '<br>';
      }
      // Provinces
      var pv = data.provinces || [];
      if (pv.length) {
        html += '<div class="text-sm font-bold mb-2 mt-3">📍 Tinh/TP</div>';
        for (var k = 0; k < Math.min(pv.length, 5); k++) html += '<div class="flex justify-between text-xs p-1" style="border-bottom:1px solid var(--border-light)"><span>📍 ' + SS.utils.esc(pv[k].province) + '</span><span class="font-bold">' + pv[k].count + '</span></div>';
      }
      SS.ui.sheet({title: '📈 Xu huong v2', html: html});
    });
  }
};
