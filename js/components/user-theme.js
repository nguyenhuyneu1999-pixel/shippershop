/**
 * ShipperShop Component — User Theme Settings
 * Dark/light/auto + accent color + font size + accessibility
 */
window.SS = window.SS || {};

SS.UserTheme = {
  init: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    SS.api.get('/user-theme.php').then(function(d) {
      var t = d.data || {};
      SS.UserTheme._apply(t);
    }).catch(function() {});
  },

  _apply: function(t) {
    if (!t) return;
    // Mode
    if (t.mode === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    else if (t.mode === 'light') document.documentElement.removeAttribute('data-theme');
    // Accent
    if (t.accent && t.accent !== '#7C3AED') document.documentElement.style.setProperty('--primary', t.accent);
    // Font size
    if (t.font_size && t.font_size !== 16) document.documentElement.style.fontSize = t.font_size + 'px';
    // Reduce animations
    if (t.reduce_animations) document.body.classList.add('ss-reduce-motion');
    // High contrast
    if (t.high_contrast) document.body.classList.add('ss-high-contrast');
  },

  show: function() {
    SS.api.get('/user-theme.php').then(function(d) {
      var t = d.data || {};
      var modes = [{id:'auto',label:'Tu dong'},{id:'light',label:'Sang'},{id:'dark',label:'Toi'}];
      var accents = ['#7C3AED','#EE4D2D','#0ea5e9','#16a34a','#d97706','#db2777','#6366f1','#000000'];

      var html = '<div class="text-sm font-bold mb-2">Che do</div><div class="flex gap-2 mb-3">';
      for (var i = 0; i < modes.length; i++) {
        var active = t.mode === modes[i].id ? 'chip-active' : '';
        html += '<div class="chip ' + active + '" onclick="SS.UserTheme._save(\'mode\',\'' + modes[i].id + '\')" style="cursor:pointer">' + modes[i].label + '</div>';
      }
      html += '</div>';

      html += '<div class="text-sm font-bold mb-2">Mau chu dao</div><div class="flex gap-2 mb-3">';
      for (var j = 0; j < accents.length; j++) {
        var sel = t.accent === accents[j] ? ';box-shadow:0 0 0 2px var(--card),0 0 0 4px ' + accents[j] : '';
        html += '<div style="width:28px;height:28px;border-radius:50%;background:' + accents[j] + ';cursor:pointer' + sel + '" onclick="SS.UserTheme._save(\'accent\',\'' + accents[j] + '\')"></div>';
      }
      html += '</div>';

      html += '<div class="text-sm font-bold mb-2">Co chu: ' + (t.font_size || 16) + 'px</div>'
        + '<input type="range" min="12" max="24" value="' + (t.font_size || 16) + '" style="width:100%" oninput="SS.UserTheme._save(\'font_size\',parseInt(this.value))">';

      SS.ui.sheet({title: 'Giao dien', html: html});
    });
  },

  _save: function(key, val) {
    var data = {};
    data[key] = val;
    SS.api.post('/user-theme.php', data).then(function(d) {
      SS.UserTheme._apply(d.data || {});
      SS.ui.toast('Da luu!', 'success', 1500);
    });
  }
};
