/**
 * ShipperShop Component — Post View Tracker
 * Auto-tracks post impressions in feed using IntersectionObserver
 * Batches requests to minimize network calls
 */
window.SS = window.SS || {};

SS.ViewTracker = {
  _observer: null,
  _queue: [],
  _timer: null,
  _sent: {},

  init: function() {
    if (!window.IntersectionObserver) return;

    SS.ViewTracker._observer = new IntersectionObserver(function(entries) {
      for (var i = 0; i < entries.length; i++) {
        if (entries[i].isIntersecting) {
          var el = entries[i].target;
          var pid = el.getAttribute('data-post-id');
          if (pid && !SS.ViewTracker._sent[pid]) {
            SS.ViewTracker._sent[pid] = true;
            SS.ViewTracker._queue.push(parseInt(pid));
            SS.ViewTracker._observer.unobserve(el);
          }
        }
      }
      // Batch send after 2s of quiet
      clearTimeout(SS.ViewTracker._timer);
      SS.ViewTracker._timer = setTimeout(SS.ViewTracker._flush, 2000);
    }, {threshold: 0.5}); // 50% visible = viewed

    SS.ViewTracker.scan();
  },

  scan: function() {
    if (!SS.ViewTracker._observer) return;
    var cards = document.querySelectorAll('[data-post-id]:not([data-viewed])');
    for (var i = 0; i < cards.length; i++) {
      SS.ViewTracker._observer.observe(cards[i]);
      cards[i].setAttribute('data-viewed', 'pending');
    }
  },

  _flush: function() {
    if (!SS.ViewTracker._queue.length) return;
    var batch = SS.ViewTracker._queue.splice(0, 50);
    SS.api.post('/post-views.php?action=batch', {post_ids: batch}).catch(function() {});
  },

  // Track single post view (for detail page)
  trackSingle: function(postId) {
    if (!postId || SS.ViewTracker._sent[postId]) return;
    SS.ViewTracker._sent[postId] = true;
    SS.api.post('/post-views.php?action=track', {post_id: postId}).catch(function() {});
  },

  // Show analytics for a post (author only)
  showStats: function(postId) {
    SS.api.get('/post-views.php?action=stats&post_id=' + postId).then(function(d) {
      var data = d.data || {};
      SS.ui.sheet({
        title: 'Thống kê bài viết',
        html: '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;text-align:center;padding:8px 0">'
          + '<div><div style="font-size:24px;font-weight:800;color:var(--primary)">' + SS.utils.fN(data.total || 0) + '</div><div class="text-xs text-muted">Lượt xem</div></div>'
          + '<div><div style="font-size:24px;font-weight:800;color:var(--info)">' + SS.utils.fN(data.unique || 0) + '</div><div class="text-xs text-muted">Người xem</div></div>'
          + '<div><div style="font-size:24px;font-weight:800;color:var(--success)">' + SS.utils.fN(data.today || 0) + '</div><div class="text-xs text-muted">Hôm nay</div></div>'
          + '</div>'
      });
    });
  }
};

// Auto-init + rescan on DOM changes
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { SS.ViewTracker.init(); });
} else {
  SS.ViewTracker.init();
}
if (window.MutationObserver) {
  var vmo = new MutationObserver(function() { SS.ViewTracker.scan(); });
  if (document.body) vmo.observe(document.body, {childList: true, subtree: true});
  else document.addEventListener('DOMContentLoaded', function() { vmo.observe(document.body, {childList: true, subtree: true}); });
}
