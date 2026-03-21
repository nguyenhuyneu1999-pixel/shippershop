/**
 * ShipperShop Component — Hashtags
 * Renders clickable hashtags in text, trending widget for sidebar
 */
window.SS = window.SS || {};

SS.Hashtags = {

  // Convert #hashtag in text to clickable links
  linkify: function(text) {
    if (!text) return '';
    return text.replace(/#([a-zA-Z0-9_\u00C0-\u024F]+)/g, function(match, tag) {
      return '<a href="/index.html?hashtag=' + encodeURIComponent(tag) + '" style="color:var(--primary);font-weight:500;text-decoration:none">' + match + '</a>';
    });
  },

  // Render trending tags widget for sidebar
  renderTrending: function(containerId, opts) {
    opts = opts || {};
    var el = document.getElementById(containerId);
    if (!el) return;

    el.innerHTML = '<div class="card"><div class="card-header">Xu hướng</div><div class="card-body" id="ht-trending-list" style="padding:0"><div class="p-4 text-center"><div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div></div></div>';

    SS.api.get('/hashtags.php?action=trending&limit=' + (opts.limit || 10) + '&period=' + (opts.period || 'week')).then(function(d) {
      var tags = d.data || [];
      var list = document.getElementById('ht-trending-list');
      if (!list) return;

      if (!tags.length) {
        list.innerHTML = '<div class="p-4 text-center text-muted text-sm">Chưa có xu hướng</div>';
        return;
      }

      var html = '';
      for (var i = 0; i < tags.length; i++) {
        var t = tags[i];
        html += '<a href="/index.html?hashtag=' + encodeURIComponent(t.tag) + '" class="list-item" style="text-decoration:none;color:var(--text);padding:10px 16px">'
          + '<div style="font-size:14px;color:var(--text-muted);width:24px;text-align:center;font-weight:700">' + (i + 1) + '</div>'
          + '<div class="flex-1">'
          + '<div class="font-medium" style="color:var(--primary)">#' + SS.utils.esc(t.tag) + '</div>'
          + '<div class="text-xs text-muted">' + t.count + ' bài viết</div>'
          + '</div></a>';
      }
      list.innerHTML = html;
    }).catch(function() {
      var list = document.getElementById('ht-trending-list');
      if (list) list.innerHTML = '<div class="p-4 text-center text-muted text-sm">Lỗi tải</div>';
    });
  },

  // Render tag chips (for filter UI)
  renderChips: function(containerId, tags) {
    var el = document.getElementById(containerId);
    if (!el || !tags) return;
    var html = '';
    for (var i = 0; i < tags.length; i++) {
      html += '<a href="/index.html?hashtag=' + encodeURIComponent(tags[i].tag || tags[i]) + '" class="chip" style="text-decoration:none">#' + SS.utils.esc(tags[i].tag || tags[i]) + '</a>';
    }
    el.innerHTML = html;
  }
};
