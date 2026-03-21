/**
 * ShipperShop Component — Accessibility Helpers
 * Skip links, ARIA live announcer, keyboard nav, focus management
 */
window.SS = window.SS || {};

SS.A11y = {

  init: function() {
    SS.A11y._addSkipLink();
    SS.A11y._addLiveRegion();
    SS.A11y._enhanceKeyboard();
  },

  // Skip to main content link
  _addSkipLink: function() {
    if (document.getElementById('ss-skip-link')) return;
    var link = document.createElement('a');
    link.id = 'ss-skip-link';
    link.href = '#main-content';
    link.textContent = 'Chuyển đến nội dung chính';
    link.style.cssText = 'position:fixed;top:-100px;left:16px;z-index:10000;background:var(--primary);color:#fff;padding:8px 16px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;transition:top .2s';
    link.addEventListener('focus', function() { link.style.top = '16px'; });
    link.addEventListener('blur', function() { link.style.top = '-100px'; });
    document.body.insertBefore(link, document.body.firstChild);

    // Add main-content id to main or first feed
    var main = document.querySelector('main, #feed, .main-content, [role="main"]');
    if (main && !main.id) main.id = 'main-content';
  },

  // ARIA live region for screen reader announcements
  _addLiveRegion: function() {
    if (document.getElementById('ss-live')) return;
    var live = document.createElement('div');
    live.id = 'ss-live';
    live.setAttribute('role', 'status');
    live.setAttribute('aria-live', 'polite');
    live.setAttribute('aria-atomic', 'true');
    live.style.cssText = 'position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap';
    document.body.appendChild(live);
  },

  // Announce text to screen readers
  announce: function(text) {
    var live = document.getElementById('ss-live');
    if (live) {
      live.textContent = '';
      setTimeout(function() { live.textContent = text; }, 50);
    }
  },

  // Enhanced keyboard navigation
  _enhanceKeyboard: function() {
    // Tab key shows focus ring, mouse hides it
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Tab') document.body.classList.add('ss-keyboard-nav');
    });
    document.addEventListener('mousedown', function() {
      document.body.classList.remove('ss-keyboard-nav');
    });

    // Add focus styles
    if (!document.getElementById('ss-a11y-style')) {
      var style = document.createElement('style');
      style.id = 'ss-a11y-style';
      style.textContent = 'body:not(.ss-keyboard-nav) *:focus{outline:none}'
        + '.ss-keyboard-nav *:focus{outline:2px solid var(--primary);outline-offset:2px;border-radius:4px}'
        + '.ss-keyboard-nav .btn:focus,.ss-keyboard-nav a:focus{outline:2px solid var(--primary);outline-offset:2px}'
        + '@media(prefers-reduced-motion:reduce){*{animation-duration:0.01ms!important;transition-duration:0.01ms!important}}';
      document.head.appendChild(style);
    }
  },

  // Focus trap for modals
  trapFocus: function(container) {
    var focusable = container.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])');
    if (!focusable.length) return function() {};
    var first = focusable[0];
    var last = focusable[focusable.length - 1];
    first.focus();

    var handler = function(e) {
      if (e.key !== 'Tab') return;
      if (e.shiftKey) {
        if (document.activeElement === first) { last.focus(); e.preventDefault(); }
      } else {
        if (document.activeElement === last) { first.focus(); e.preventDefault(); }
      }
    };
    container.addEventListener('keydown', handler);
    return function() { container.removeEventListener('keydown', handler); };
  }
};

// Auto-init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { SS.A11y.init(); });
} else {
  SS.A11y.init();
}
