/**
 * ShipperShop Component — Mention Autocomplete
 * @mention popup for post/comment text inputs
 * Attaches to textarea, shows user dropdown on @
 * Uses: SS.api
 */
window.SS = window.SS || {};

SS.Mention = {
  _timer: null,
  _dropdown: null,
  _target: null,

  // Attach to a textarea element
  attach: function(textarea) {
    if (!textarea) return;
    textarea.addEventListener('input', SS.Mention._onInput);
    textarea.addEventListener('keydown', SS.Mention._onKeydown);
    textarea.addEventListener('blur', function() {
      setTimeout(SS.Mention._close, 200);
    });
  },

  _onInput: function(e) {
    clearTimeout(SS.Mention._timer);
    var el = e.target;
    var val = el.value;
    var pos = el.selectionStart;

    // Find @ before cursor
    var before = val.substring(0, pos);
    var match = before.match(/@(\S*)$/);

    if (match && match[1].length >= 1) {
      SS.Mention._target = el;
      SS.Mention._timer = setTimeout(function() {
        SS.Mention._search(match[1], el);
      }, 300);
    } else {
      SS.Mention._close();
    }
  },

  _search: function(query, el) {
    SS.api.get('/mentions.php?action=search&q=' + encodeURIComponent(query)).then(function(d) {
      var users = d.data || [];
      if (!users.length) { SS.Mention._close(); return; }
      SS.Mention._showDropdown(users, el);
    });
  },

  _showDropdown: function(users, el) {
    SS.Mention._close();
    var rect = el.getBoundingClientRect();
    var dd = document.createElement('div');
    dd.id = 'ss-mention-dd';
    dd.style.cssText = 'position:fixed;z-index:3000;background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow-md);max-height:200px;overflow-y:auto;width:220px;left:' + rect.left + 'px;bottom:' + (window.innerHeight - rect.top + 4) + 'px';

    var html = '';
    for (var i = 0; i < users.length; i++) {
      var u = users[i];
      html += '<div class="mention-item" data-name="' + SS.utils.esc(u.fullname) + '" data-id="' + u.id + '" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px" onmousedown="SS.Mention._select(this)">'
        + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:28px;height:28px;border-radius:50%;object-fit:cover">'
        + '<div><div class="font-medium">' + SS.utils.esc(u.fullname) + '</div>'
        + (u.shipping_company ? '<div class="text-xs text-muted">' + SS.utils.esc(u.shipping_company) + '</div>' : '')
        + '</div></div>';
    }
    dd.innerHTML = html;
    document.body.appendChild(dd);
    SS.Mention._dropdown = dd;
  },

  _select: function(item) {
    var name = item.getAttribute('data-name');
    var el = SS.Mention._target;
    if (!el || !name) return;

    var val = el.value;
    var pos = el.selectionStart;
    var before = val.substring(0, pos);
    var after = val.substring(pos);
    var replaced = before.replace(/@\S*$/, '@' + name + ' ');
    el.value = replaced + after;
    el.selectionStart = el.selectionEnd = replaced.length;
    el.focus();
    SS.Mention._close();
  },

  _onKeydown: function(e) {
    if (!SS.Mention._dropdown) return;
    if (e.key === 'Escape') { SS.Mention._close(); e.preventDefault(); }
  },

  _close: function() {
    if (SS.Mention._dropdown) {
      SS.Mention._dropdown.remove();
      SS.Mention._dropdown = null;
    }
  },

  // Linkify @mentions in rendered text
  linkify: function(text) {
    if (!text) return '';
    return text.replace(/@(\S+)/g, function(match, name) {
      return '<a href="/people.html?search=' + encodeURIComponent(name) + '" style="color:var(--primary);font-weight:500;text-decoration:none">' + match + '</a>';
    });
  }
};
