/**
 * ShipperShop Component — Scroll Progress
 * Thin progress bar at top showing reading position on long pages
 */
window.SS = window.SS || {};

SS.ScrollProgress = {
  _bar: null,

  init: function() {
    // Only on content pages
    var page = window.location.pathname;
    if (page.indexOf('post-detail') === -1 && page.indexOf('group') === -1 && page.indexOf('listing') === -1) return;

    if (SS.ScrollProgress._bar) return;
    var bar = document.createElement('div');
    bar.id = 'ss-scroll-progress';
    bar.style.cssText = 'position:fixed;top:0;left:0;height:3px;background:var(--primary);z-index:10001;width:0;transition:width .1s linear;pointer-events:none';
    document.body.appendChild(bar);
    SS.ScrollProgress._bar = bar;

    window.addEventListener('scroll', SS.ScrollProgress._update, {passive: true});
  },

  _update: function() {
    var bar = SS.ScrollProgress._bar;
    if (!bar) return;
    var scrollTop = window.scrollY || document.documentElement.scrollTop;
    var docHeight = document.documentElement.scrollHeight - window.innerHeight;
    var pct = docHeight > 0 ? Math.min(100, Math.round(scrollTop / docHeight * 100)) : 0;
    bar.style.width = pct + '%';
  },

  destroy: function() {
    if (SS.ScrollProgress._bar) {
      SS.ScrollProgress._bar.remove();
      SS.ScrollProgress._bar = null;
    }
    window.removeEventListener('scroll', SS.ScrollProgress._update);
  }
};

// Auto-init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { SS.ScrollProgress.init(); });
} else {
  SS.ScrollProgress.init();
}
