/**
 * ShipperShop Component — Profile Theme Picker
 * Select profile header gradient theme
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.ThemePicker = {

  open: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/profile-theme.php?action=list').then(function(d) {
      var themes = d.data || [];
      var html = '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px">';
      for (var i = 0; i < themes.length; i++) {
        var t = themes[i];
        html += '<div onclick="SS.ThemePicker._select(\'' + t.id + '\')" style="cursor:pointer;border-radius:12px;overflow:hidden;border:2px solid var(--border);transition:border-color .2s" onmouseenter="this.style.borderColor=\'var(--primary)\'" onmouseleave="this.style.borderColor=\'var(--border)\'">'
          + '<div style="height:60px;background:' + t.gradient + ';display:flex;align-items:center;justify-content:center">'
          + '<span style="color:' + t.text + ';font-weight:700;font-size:13px">' + SS.utils.esc(t.name) + '</span>'
          + '</div></div>';
      }
      html += '</div>';
      SS.ui.sheet({title: 'Chọn theme hồ sơ', html: html});
    });
  },

  _select: function(themeId) {
    SS.api.post('/profile-theme.php', {theme_id: themeId}).then(function(d) {
      SS.ui.toast(d.message || 'Đã đổi theme!', 'success');
      SS.ui.closeSheet();
      if (SS.NotifSound) SS.NotifSound.play('success');
    });
  },

  // Render theme header for a user profile
  renderHeader: function(containerId, userId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    SS.api.get('/profile-theme.php?user_id=' + userId).then(function(d) {
      var theme = d.data || {};
      var gradient = theme.custom_gradient || theme.gradient || 'linear-gradient(135deg,#7C3AED,#5B21B6)';
      el.style.background = gradient;
      el.style.color = theme.text || '#fff';
    }).catch(function() {});
  }
};
