/**
 * ShipperShop Component — Dark/light mode toggle with persistence
 */
window.SS = window.SS || {};

SS.ThemeToggle = {
  init: function() {
    var saved = localStorage.getItem('ss_theme') || 'light';
    SS.ThemeToggle.set(saved);
  },
  toggle: function() {
    var current = document.documentElement.getAttribute('data-theme') || 'light';
    SS.ThemeToggle.set(current === 'dark' ? 'light' : 'dark');
  },
  set: function(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('ss_theme', theme);
  },
  isDark: function() { return document.documentElement.getAttribute('data-theme') === 'dark'; }
};
