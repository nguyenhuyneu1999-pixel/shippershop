// ShipperShop Dark Mode Toggle
// Saves preference to localStorage, respects system preference
(function() {
    var KEY = 'ss_dark_mode';
    
    function isDark() {
        var saved = localStorage.getItem(KEY);
        if (saved !== null) return saved === '1';
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    
    function apply(dark) {
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem(KEY, dark ? '1' : '0');
        // Update meta theme-color
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.content = dark ? '#1a1a2e' : '#7C3AED';
    }
    
    // Apply on load
    apply(isDark());
    
    // Listen for system preference changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (localStorage.getItem(KEY) === null) apply(e.matches);
        });
    }
    
    // Toggle function
    window.toggleDarkMode = function() {
        var dark = !document.documentElement.classList.contains('dark');
        apply(dark);
        return dark;
    };
})();
