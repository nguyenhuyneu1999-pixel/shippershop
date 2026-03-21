/**
 * ShipperShop Component — Link Preview
 * Auto-detects URLs in text, fetches OG metadata, renders preview cards
 * Uses: SS.api, SS.utils
 */
window.SS = window.SS || {};

SS.LinkPreview = {
  _cache: {},

  // Extract first URL from text
  extractUrl: function(text) {
    if (!text) return null;
    var match = text.match(/https?:\/\/[^\s<>"']+/);
    return match ? match[0] : null;
  },

  // Fetch and render preview card
  render: function(url, containerId) {
    if (!url || !containerId) return;
    var el = document.getElementById(containerId);
    if (!el) return;

    // Check cache
    if (SS.LinkPreview._cache[url]) {
      SS.LinkPreview._renderCard(SS.LinkPreview._cache[url], el);
      return;
    }

    el.innerHTML = '<div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--bg);border-radius:8px;margin:8px 0"><div class="spin" style="width:14px;height:14px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%"></div><span class="text-xs text-muted">Đang tải preview...</span></div>';

    SS.api.get('/link-preview.php?url=' + encodeURIComponent(url)).then(function(d) {
      var meta = d.data || {};
      SS.LinkPreview._cache[url] = meta;
      SS.LinkPreview._renderCard(meta, el);
    }).catch(function() {
      el.innerHTML = '';
    });
  },

  _renderCard: function(meta, el) {
    if (!meta.title && !meta.description && !meta.image) {
      el.innerHTML = '';
      return;
    }

    var domain = '';
    try { domain = new URL(meta.url).hostname; } catch(e) {}

    el.innerHTML = '<a href="' + SS.utils.esc(meta.url) + '" target="_blank" rel="noopener" style="display:block;text-decoration:none;color:var(--text);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin:8px 0">'
      + (meta.image ? '<div style="aspect-ratio:1.91/1;max-height:200px;overflow:hidden;background:var(--bg)"><img src="' + SS.utils.esc(meta.image) + '" style="width:100%;height:100%;object-fit:cover" loading="lazy" onerror="this.parentNode.remove()"></div>' : '')
      + '<div style="padding:10px 12px">'
      + '<div class="text-xs text-muted" style="text-transform:uppercase">' + SS.utils.esc(meta.site_name || domain) + '</div>'
      + (meta.title ? '<div style="font-weight:600;font-size:14px;margin-top:2px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">' + SS.utils.esc(meta.title) + '</div>' : '')
      + (meta.description ? '<div class="text-xs text-muted mt-1" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4">' + SS.utils.esc(meta.description) + '</div>' : '')
      + '</div></a>';
  },

  // Auto-detect and render previews for all posts in a container
  autoDetect: function(containerSelector) {
    var posts = document.querySelectorAll(containerSelector || '.post-content');
    for (var i = 0; i < posts.length; i++) {
      var text = posts[i].textContent || '';
      var url = SS.LinkPreview.extractUrl(text);
      if (url) {
        var previewId = 'lp-' + Math.random().toString(36).substr(2, 6);
        var div = document.createElement('div');
        div.id = previewId;
        posts[i].parentNode.insertBefore(div, posts[i].nextSibling);
        SS.LinkPreview.render(url, previewId);
      }
    }
  }
};
