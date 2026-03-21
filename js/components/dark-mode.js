/**
 * ShipperShop Component — Dark Mode Toggle
 * Reads system preference, allows manual override, persists in localStorage
 * CSS variables in design-system.css handle the actual theming
 */
window.SS = window.SS || {};

SS.DarkMode = {
  _active: false,

  init: function() {
    // Check saved preference or system
    var saved = localStorage.getItem('ss_dark_mode');
    if (saved === 'true') {
      SS.DarkMode._active = true;
    } else if (saved === 'false') {
      SS.DarkMode._active = false;
    } else {
      // Follow system preference
      SS.DarkMode._active = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    SS.DarkMode._apply();

    // Listen for system changes (only if no manual override)
    if (window.matchMedia && saved === null) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        if (localStorage.getItem('ss_dark_mode') === null) {
          SS.DarkMode._active = e.matches;
          SS.DarkMode._apply();
        }
      });
    }
  },

  toggle: function() {
    SS.DarkMode._active = !SS.DarkMode._active;
    localStorage.setItem('ss_dark_mode', SS.DarkMode._active ? 'true' : 'false');
    SS.DarkMode._apply();
  },

  isActive: function() {
    return SS.DarkMode._active;
  },

  _apply: function() {
    if (SS.DarkMode._active) {
      document.documentElement.setAttribute('data-theme', 'dark');
      document.documentElement.style.setProperty('--bg', '#18191a');
      document.documentElement.style.setProperty('--card', '#242526');
      document.documentElement.style.setProperty('--text', '#e4e6eb');
      document.documentElement.style.setProperty('--text-secondary', '#b0b3b8');
      document.documentElement.style.setProperty('--text-muted', '#777');
      document.documentElement.style.setProperty('--border', '#3e4042');
      document.documentElement.style.setProperty('--border-light', '#333');
      document.documentElement.style.setProperty('--primary-light', '#2d1b69');
      document.documentElement.style.setProperty('--primary-50', '#1a1033');
      document.documentElement.style.setProperty('--shadow-sm', '0 1px 2px rgba(0,0,0,.2)');
      document.documentElement.style.setProperty('--shadow-md', '0 4px 12px rgba(0,0,0,.3)');
      // Update meta theme-color
      var meta = document.querySelector('meta[name="theme-color"]');
      if (meta) meta.content = '#18191a';
    } else {
      document.documentElement.removeAttribute('data-theme');
      document.documentElement.style.removeProperty('--bg');
      document.documentElement.style.removeProperty('--card');
      document.documentElement.style.removeProperty('--text');
      document.documentElement.style.removeProperty('--text-secondary');
      document.documentElement.style.removeProperty('--text-muted');
      document.documentElement.style.removeProperty('--border');
      document.documentElement.style.removeProperty('--border-light');
      document.documentElement.style.removeProperty('--primary-light');
      document.documentElement.style.removeProperty('--primary-50');
      document.documentElement.style.removeProperty('--shadow-sm');
      document.documentElement.style.removeProperty('--shadow-md');
      var meta2 = document.querySelector('meta[name="theme-color"]');
      if (meta2) meta2.content = '#7C3AED';
    }

    // Update toggle button icon if exists
    var btn = document.getElementById('ss-dark-toggle');
    if (btn) {
      btn.innerHTML = SS.DarkMode._active
        ? '<i class="fa-solid fa-sun" style="color:#f59e0b"></i>'
        : '<i class="fa-solid fa-moon" style="color:#6366f1"></i>';
    }
  },

  // Render toggle button (call from nav)
  renderButton: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '<button id="ss-dark-toggle" class="btn btn-icon btn-ghost" onclick="SS.DarkMode.toggle()" title="Chế độ tối">'
      + (SS.DarkMode._active ? '<i class="fa-solid fa-sun" style="color:#f59e0b"></i>' : '<i class="fa-solid fa-moon" style="color:#6366f1"></i>')
      + '</button>';
  }
};

// Auto-init on load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { SS.DarkMode.init(); });
} else {
  SS.DarkMode.init();
}
