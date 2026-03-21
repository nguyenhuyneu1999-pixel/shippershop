/**
 * ShipperShop Component — Emoji Picker
 * Compact grid, category tabs, search, frequent
 */
window.SS = window.SS || {};

SS.EmojiPicker = {
  _el: null,
  _callback: null,

  _emojis: {
    'Hay dùng': ['😀','😂','❤️','👍','🔥','😍','🤣','😊','👏','💪','✅','🚚','📦','💰','⭐','🏆','💎','🎉','🙏','😎'],
    'Cảm xúc': ['😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','😋','😎','😍','😘','🥰','😗','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😯','😪','😫','🥱','😴','😌','😛','😜','😝','🤤','😒','😓','😔','😕','🙃','🤑','😲','😖','😞','😟','😤','😢','😭','😦','😧','😨','😩','🤯','😬','😰','😱','🥵','🥶','😳','🤪','😵','🥴','😡','🤬','😷','🤒','🤕','🤢','🤮','🤧'],
    'Shipper': ['🚚','📦','🏍️','🛵','🚗','🗺️','📍','📱','💵','💰','💸','⏰','🔔','📋','✅','❌','🚦','🌧️','☀️','⛽','🔑','🏠','🏢','🏪','🛒'],
    'Ký hiệu': ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','❗','❓','‼️','⁉️','✨','💥','💫','🌟','⭐','🏅','🥇','🥈','🥉','🏆','💎','👑','✈️','🎯','🎉','🎊']
  },

  open: function(targetEl, callback) {
    SS.EmojiPicker._callback = callback;
    if (SS.EmojiPicker._el) { SS.EmojiPicker.close(); return; }

    var el = document.createElement('div');
    el.id = 'ss-emoji-picker';
    el.style.cssText = 'position:fixed;bottom:60px;left:50%;transform:translateX(-50%);width:320px;max-width:95vw;background:var(--card);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.2);z-index:1500;overflow:hidden;animation:slideUp .2s';

    // Header with category tabs
    var cats = Object.keys(SS.EmojiPicker._emojis);
    var tabHtml = '<div style="display:flex;overflow-x:auto;border-bottom:1px solid var(--border);padding:4px 8px;gap:4px;-webkit-overflow-scrolling:touch">';
    for (var i = 0; i < cats.length; i++) {
      tabHtml += '<button style="padding:6px 10px;border:none;background:' + (i === 0 ? 'var(--primary-light)' : 'none') + ';border-radius:6px;font-size:12px;white-space:nowrap;cursor:pointer;color:' + (i === 0 ? 'var(--primary)' : 'var(--text-secondary)') + '" onclick="SS.EmojiPicker._switchTab(' + i + ',this)" data-tab="' + i + '">' + cats[i] + '</button>';
    }
    tabHtml += '</div>';

    // Emoji grid
    var gridHtml = '<div id="ep-grid" style="padding:8px;max-height:200px;overflow-y:auto;display:grid;grid-template-columns:repeat(8,1fr);gap:2px">';
    var firstCat = SS.EmojiPicker._emojis[cats[0]];
    for (var j = 0; j < firstCat.length; j++) {
      gridHtml += '<button style="width:36px;height:36px;border:none;background:none;font-size:20px;cursor:pointer;border-radius:6px;display:flex;align-items:center;justify-content:center" onclick="SS.EmojiPicker._select(\'' + firstCat[j] + '\')" onmouseenter="this.style.background=\'var(--border-light)\'" onmouseleave="this.style.background=\'none\'">' + firstCat[j] + '</button>';
    }
    gridHtml += '</div>';

    el.innerHTML = tabHtml + gridHtml;
    document.body.appendChild(el);
    SS.EmojiPicker._el = el;

    // Close on outside click
    setTimeout(function() {
      document.addEventListener('click', SS.EmojiPicker._outsideClick);
    }, 0);
  },

  close: function() {
    if (SS.EmojiPicker._el) {
      document.body.removeChild(SS.EmojiPicker._el);
      SS.EmojiPicker._el = null;
      document.removeEventListener('click', SS.EmojiPicker._outsideClick);
    }
  },

  _outsideClick: function(e) {
    if (SS.EmojiPicker._el && !SS.EmojiPicker._el.contains(e.target)) {
      SS.EmojiPicker.close();
    }
  },

  _switchTab: function(index, btn) {
    var cats = Object.keys(SS.EmojiPicker._emojis);
    var emojis = SS.EmojiPicker._emojis[cats[index]];
    var grid = document.getElementById('ep-grid');
    if (!grid) return;

    var html = '';
    for (var i = 0; i < emojis.length; i++) {
      html += '<button style="width:36px;height:36px;border:none;background:none;font-size:20px;cursor:pointer;border-radius:6px;display:flex;align-items:center;justify-content:center" onclick="SS.EmojiPicker._select(\'' + emojis[i] + '\')" onmouseenter="this.style.background=\'var(--border-light)\'" onmouseleave="this.style.background=\'none\'">' + emojis[i] + '</button>';
    }
    grid.innerHTML = html;

    // Update tab styles
    var tabs = btn.parentNode.querySelectorAll('button');
    for (var j = 0; j < tabs.length; j++) {
      tabs[j].style.background = j === index ? 'var(--primary-light)' : 'none';
      tabs[j].style.color = j === index ? 'var(--primary)' : 'var(--text-secondary)';
    }
  },

  _select: function(emoji) {
    if (SS.EmojiPicker._callback) SS.EmojiPicker._callback(emoji);
    SS.EmojiPicker.close();
  }
};
