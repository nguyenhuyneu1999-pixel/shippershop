/**
 * ShipperShop Component — Tooltip
 * Lightweight reusable tooltip on hover/tap
 * Usage: <span data-tooltip="Hello">hover me</span>
 */
window.SS = window.SS || {};

SS.Tooltip = {
  _el: null,

  init: function() {
    document.addEventListener('mouseenter', function(e) {
      var target = e.target.closest('[data-tooltip]');
      if (target && window.innerWidth > 768) SS.Tooltip._show(target);
    }, true);
    document.addEventListener('mouseleave', function(e) {
      var target = e.target.closest('[data-tooltip]');
      if (target) SS.Tooltip._hide();
    }, true);
  },

  _show: function(el) {
    SS.Tooltip._hide();
    var text = el.getAttribute('data-tooltip');
    if (!text) return;

    var tip = document.createElement('div');
    tip.id = 'ss-tooltip';
    tip.style.cssText = 'position:fixed;z-index:10000;background:#1a1a1a;color:#fff;padding:4px 10px;border-radius:6px;font-size:12px;pointer-events:none;white-space:nowrap;max-width:250px;animation:fadeIn .15s';
    tip.textContent = text;
    document.body.appendChild(tip);

    var rect = el.getBoundingClientRect();
    var tipW = tip.offsetWidth;
    tip.style.top = (rect.top - tip.offsetHeight - 6) + 'px';
    tip.style.left = Math.max(8, Math.min(rect.left + rect.width / 2 - tipW / 2, window.innerWidth - tipW - 8)) + 'px';

    if (rect.top - tip.offsetHeight - 6 < 0) {
      tip.style.top = (rect.bottom + 6) + 'px';
    }

    SS.Tooltip._el = tip;
  },

  _hide: function() {
    if (SS.Tooltip._el) {
      SS.Tooltip._el.remove();
      SS.Tooltip._el = null;
    }
  }
};

// Auto-init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { SS.Tooltip.init(); });
} else {
  SS.Tooltip.init();
}
