/**
 * ShipperShop Component — Mention Picker
 * @mention autocomplete: type @ in textarea to trigger user suggestions
 * Uses: SS.api, SS.utils
 */
window.SS = window.SS || {};

SS.MentionPicker = {
  _active: false,
  _el: null,
  _input: null,
  _startPos: 0,

  // Attach to a textarea/input
  attach: function(inputId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    SS.MentionPicker._input = input;

    input.addEventListener('input', function() {
      var val = input.value;
      var pos = input.selectionStart;
      // Find @ before cursor
      var before = val.substring(0, pos);
      var atIdx = before.lastIndexOf('@');

      if (atIdx >= 0 && (atIdx === 0 || before[atIdx - 1] === ' ' || before[atIdx - 1] === '\n')) {
        var query = before.substring(atIdx + 1);
        if (query.length >= 1 && query.indexOf(' ') === -1 && query.length <= 20) {
          SS.MentionPicker._startPos = atIdx;
          SS.MentionPicker._search(query, input);
          return;
        }
      }
      SS.MentionPicker.close();
    });

    input.addEventListener('keydown', function(e) {
      if (!SS.MentionPicker._active) return;
      if (e.key === 'Escape') { SS.MentionPicker.close(); e.preventDefault(); }
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        var items = SS.MentionPicker._el ? SS.MentionPicker._el.querySelectorAll('.mp-item') : [];
        var active = SS.MentionPicker._el ? SS.MentionPicker._el.querySelector('.mp-item.active') : null;
        var idx = active ? Array.prototype.indexOf.call(items, active) : -1;
        if (e.key === 'ArrowDown') idx = Math.min(idx + 1, items.length - 1);
        else idx = Math.max(idx - 1, 0);
        for (var i = 0; i < items.length; i++) items[i].classList.toggle('active', i === idx);
      }
      if (e.key === 'Enter' || e.key === 'Tab') {
        var sel = SS.MentionPicker._el ? SS.MentionPicker._el.querySelector('.mp-item.active') : null;
        if (sel) { e.preventDefault(); SS.MentionPicker._select(sel.getAttribute('data-id'), sel.getAttribute('data-name')); }
      }
    });

    input.addEventListener('blur', function() {
      setTimeout(function() { SS.MentionPicker.close(); }, 200);
    });
  },

  _search: function(query, input) {
    SS.api.get('/mentions.php?action=suggest&q=' + encodeURIComponent(query)).then(function(d) {
      var users = d.data || [];
      if (!users.length) { SS.MentionPicker.close(); return; }

      if (!SS.MentionPicker._el) {
        var el = document.createElement('div');
        el.id = 'ss-mention-picker';
        el.style.cssText = 'position:absolute;background:var(--card);border:1px solid var(--border);border-radius:10px;box-shadow:var(--shadow-lg);max-height:200px;overflow-y:auto;z-index:1000;min-width:220px';
        document.body.appendChild(el);
        SS.MentionPicker._el = el;
      }

      // Position below input
      var rect = input.getBoundingClientRect();
      SS.MentionPicker._el.style.top = (rect.bottom + window.scrollY + 4) + 'px';
      SS.MentionPicker._el.style.left = rect.left + 'px';
      SS.MentionPicker._el.style.width = Math.min(rect.width, 300) + 'px';

      var html = '';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        html += '<div class="mp-item' + (i === 0 ? ' active' : '') + '" data-id="' + u.id + '" data-name="' + SS.utils.esc(u.fullname) + '" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer" onmouseenter="this.parentNode.querySelectorAll(\'.mp-item\').forEach(function(x){x.classList.remove(\'active\')});this.classList.add(\'active\')" onclick="SS.MentionPicker._select(' + u.id + ',\'' + SS.utils.esc(u.fullname).replace(/'/g, '\\x27') + '\')">'
          + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:28px;height:28px;border-radius:50%;object-fit:cover">'
          + '<div><div style="font-weight:600;font-size:13px">' + SS.utils.esc(u.fullname) + '</div>'
          + (u.shipping_company ? '<div style="font-size:11px;color:var(--text-muted)">' + SS.utils.esc(u.shipping_company) + '</div>' : '')
          + '</div></div>';
      }
      SS.MentionPicker._el.innerHTML = html;
      SS.MentionPicker._active = true;
    }).catch(function() { SS.MentionPicker.close(); });
  },

  _select: function(userId, fullname) {
    var input = SS.MentionPicker._input;
    if (!input) return;
    var val = input.value;
    var pos = input.selectionStart;
    // Replace @query with @[Name](userId)
    var before = val.substring(0, SS.MentionPicker._startPos);
    var after = val.substring(pos);
    var mention = '@[' + fullname + '](' + userId + ') ';
    input.value = before + mention + after;
    input.selectionStart = input.selectionEnd = before.length + mention.length;
    input.focus();
    SS.MentionPicker.close();
  },

  close: function() {
    if (SS.MentionPicker._el) {
      SS.MentionPicker._el.remove();
      SS.MentionPicker._el = null;
    }
    SS.MentionPicker._active = false;
  },

  // Render mentions in text for display (convert @[Name](id) to clickable)
  renderText: function(text) {
    if (!text) return '';
    return text.replace(/@\[([^\]]+)\]\((\d+)\)/g, function(match, name, id) {
      return '<a href="/user.html?id=' + id + '" style="color:var(--primary);font-weight:500;text-decoration:none">@' + SS.utils.esc(name) + '</a>';
    });
  }
};
