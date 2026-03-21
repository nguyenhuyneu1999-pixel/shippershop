/**
 * ShipperShop Component — Keyboard Shortcuts
 * Power user shortcuts for quick navigation and actions
 * Press ? to show help
 */
window.SS = window.SS || {};

SS.Shortcuts = {
  _enabled: true,

  init: function() {
    document.addEventListener('keydown', function(e) {
      if (!SS.Shortcuts._enabled) return;
      // Skip if typing in input/textarea
      var tag = e.target.tagName;
      if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable) return;

      var key = e.key;

      // ? — show shortcuts help
      if (key === '?') { e.preventDefault(); SS.Shortcuts.showHelp(); return; }

      // Navigation
      if (key === 'g') {
        SS.Shortcuts._waitForNext(function(k) {
          var routes = {h:'/',p:'/profile.html',m:'/messages.html',n:'/people.html',g:'/groups.html',
            s:'/marketplace.html',t:'/traffic.html',w:'/wallet.html',l:'/leaderboard.html'};
          if (routes[k]) window.location.href = routes[k];
        });
        return;
      }

      // Actions
      if (key === 'n' && e.ctrlKey) { e.preventDefault(); if (SS.PostCreate) SS.PostCreate.open(); return; }
      if (key === '/' && !e.ctrlKey) { e.preventDefault(); if (SS.SearchOverlay) SS.SearchOverlay.open(); return; }
      if (key === 'Escape') {
        if (SS.ui) { SS.ui.closeModal(); SS.ui.closeSheet(); }
        return;
      }
      if (key === 'd' && e.shiftKey) { e.preventDefault(); if (SS.DarkMode) SS.DarkMode.toggle(); return; }
    });
  },

  _nextHandler: null,
  _nextTimer: null,

  _waitForNext: function(handler) {
    SS.Shortcuts._nextHandler = handler;
    clearTimeout(SS.Shortcuts._nextTimer);
    SS.Shortcuts._nextTimer = setTimeout(function() { SS.Shortcuts._nextHandler = null; }, 1500);
    document.addEventListener('keydown', function once(e) {
      document.removeEventListener('keydown', once);
      if (SS.Shortcuts._nextHandler) {
        e.preventDefault();
        SS.Shortcuts._nextHandler(e.key);
        SS.Shortcuts._nextHandler = null;
      }
    }, {once: true});
  },

  showHelp: function() {
    var shortcuts = [
      {keys: '?', desc: 'Hiện bảng phím tắt'},
      {keys: '/', desc: 'Tìm kiếm'},
      {keys: 'Ctrl+N', desc: 'Tạo bài viết mới'},
      {keys: 'Shift+D', desc: 'Bật/tắt chế độ tối'},
      {keys: 'Esc', desc: 'Đóng modal/sheet'},
      {keys: 'g h', desc: 'Về trang chủ'},
      {keys: 'g p', desc: 'Hồ sơ'},
      {keys: 'g m', desc: 'Tin nhắn'},
      {keys: 'g n', desc: 'Bạn bè'},
      {keys: 'g g', desc: 'Nhóm'},
      {keys: 'g s', desc: 'Chợ'},
      {keys: 'g t', desc: 'Giao thông'},
      {keys: 'g w', desc: 'Ví tiền'},
    ];

    var html = '<div style="font-family:monospace">';
    for (var i = 0; i < shortcuts.length; i++) {
      var s = shortcuts[i];
      html += '<div class="list-item" style="padding:6px 0">'
        + '<kbd style="background:var(--bg);padding:3px 8px;border-radius:4px;font-size:12px;font-weight:700;border:1px solid var(--border);min-width:60px;text-align:center;display:inline-block">' + s.keys + '</kbd>'
        + '<div class="flex-1 text-sm" style="margin-left:12px">' + s.desc + '</div></div>';
    }
    html += '</div>';

    if (SS.ui) SS.ui.sheet({title: 'Phím tắt', html: html});
  }
};

// Auto-init on desktop only
if (window.innerWidth > 768) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { SS.Shortcuts.init(); });
  } else {
    SS.Shortcuts.init();
  }
}
