/**
 * ShipperShop Component — Profile Themes
 * Theme picker for user profile pages
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.ProfileThemes = {

  // Show theme picker
  open: function() {
    SS.api.get('/profile-themes.php?action=presets').then(function(d) {
      var presets = d.data.presets || [];
      var html = '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px">';
      for (var i = 0; i < presets.length; i++) {
        var t = presets[i];
        html += '<div class="card card-hover" style="cursor:pointer;overflow:hidden" onclick="SS.ProfileThemes._apply(\'' + t.id + '\')">'
          + '<div style="height:40px;background:linear-gradient(135deg,' + t.primary + ',' + t.bg + ')"></div>'
          + '<div style="padding:8px;background:' + t.card + '">'
          + '<div style="font-size:12px;font-weight:700;color:' + t.primary + '">' + SS.utils.esc(t.name) + '</div>'
          + '<div class="flex gap-1 mt-1">'
          + '<div style="width:14px;height:14px;border-radius:50%;background:' + t.primary + ';border:1px solid #ddd"></div>'
          + '<div style="width:14px;height:14px;border-radius:50%;background:' + t.bg + ';border:1px solid #ddd"></div>'
          + '<div style="width:14px;height:14px;border-radius:50%;background:' + t.card + ';border:1px solid #ddd"></div>'
          + '</div></div></div>';
      }
      html += '</div>';
      SS.ui.sheet({title: 'Chọn theme hồ sơ', html: html});
    });
  },

  _apply: function(themeId) {
    SS.ui.closeSheet();
    SS.api.post('/profile-themes.php', {theme_id: themeId}).then(function(d) {
      SS.ui.toast(d.message || 'Đã đổi theme!', 'success');
    });
  },

  // Apply theme to profile page
  applyToPage: function(userId) {
    SS.api.get('/profile-themes.php?action=get&user_id=' + userId).then(function(d) {
      var theme = (d.data || {}).theme;
      if (!theme || theme.id === 'default') return;
      var style = document.createElement('style');
      style.id = 'ss-profile-theme';
      style.textContent = '.profile-header{background:linear-gradient(135deg,' + theme.primary + ',' + theme.bg + ')!important}'
        + '.profile-name{color:' + theme.primary + '!important}';
      var old = document.getElementById('ss-profile-theme');
      if (old) old.remove();
      document.head.appendChild(style);
    });
  }
};
