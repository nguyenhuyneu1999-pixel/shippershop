/**
 * ShipperShop Component — User Mentions
 * @mention autocomplete dropdown for text inputs + linkify @names in rendered text
 * Uses: SS.api, SS.utils
 */
window.SS = window.SS || {};

SS.Mentions = {
  _dropdown: null,
  _input: null,
  _callback: null,

  // Attach autocomplete to a textarea/input
  attach: function(inputEl, onSelect) {
    if (!inputEl) return;
    SS.Mentions._input = inputEl;
    SS.Mentions._callback = onSelect;

    inputEl.addEventListener('input', function() {
      var val = inputEl.value;
      var cursor = inputEl.selectionStart || val.length;
      // Find @ before cursor
      var before = val.substring(0, cursor);
      var match = before.match(/@([a-zA-Z0-9\u00C0-\u024F\s]{1,30})$/);
      if (match) {
        SS.Mentions._search(match[1].trim(), inputEl);
      } else {
        SS.Mentions._hideDropdown();
      }
    });

    inputEl.addEventListener('keydown', function(e) {
      if (!SS.Mentions._dropdown || SS.Mentions._dropdown.style.display === 'none') return;
      var items = SS.Mentions._dropdown.querySelectorAll('.mention-item');
      var active = SS.Mentions._dropdown.querySelector('.mention-item.active');
      var idx = -1;
      for (var i = 0; i < items.length; i++) { if (items[i] === active) idx = i; }

      if (e.key === 'ArrowDown') { e.preventDefault(); idx = Math.min(idx + 1, items.length - 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); idx = Math.max(idx - 1, 0); }
      else if (e.key === 'Enter' && active) { e.preventDefault(); active.click(); return; }
      else if (e.key === 'Escape') { SS.Mentions._hideDropdown(); return; }
      else return;

      for (var j = 0; j < items.length; j++) items[j].classList.toggle('active', j === idx);
    });
  },

  _search: function(query, inputEl) {
    if (query.length < 1) { SS.Mentions._hideDropdown(); return; }
    SS.api.get('/mentions.php?action=search&q=' + encodeURIComponent(query)).then(function(d) {
      var users = d.data || [];
      if (!users.length) { SS.Mentions._hideDropdown(); return; }
      SS.Mentions._showDropdown(users, inputEl);
    });
  },

  _showDropdown: function(users, inputEl) {
    if (!SS.Mentions._dropdown) {
      var dd = document.createElement('div');
      dd.id = 'ss-mention-dropdown';
      dd.style.cssText = 'position:fixed;z-index:3000;background:var(--card);border:1px solid var(--border);border-radius:10px;box-shadow:var(--shadow-lg);max-height:240px;overflow-y:auto;width:260px';
      document.body.appendChild(dd);
      SS.Mentions._dropdown = dd;
    }

    var dd = SS.Mentions._dropdown;
    var rect = inputEl.getBoundingClientRect();
    dd.style.left = rect.left + 'px';
    dd.style.top = (rect.bottom + 4) + 'px';
    dd.style.display = 'block';

    var html = '';
    for (var i = 0; i < users.length; i++) {
      var u = users[i];
      var verified = parseInt(u.is_verified) ? ' <i class="fa-solid fa-circle-check" style="color:#3b82f6;font-size:11px"></i>' : '';
      html += '<div class="mention-item' + (i === 0 ? ' active' : '') + '" data-id="' + u.id + '" data-name="' + SS.utils.esc(u.fullname) + '" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer" onmouseenter="this.classList.add(\'active\');var s=this.parentNode.querySelectorAll(\'.mention-item\');for(var i=0;i<s.length;i++)if(s[i]!==this)s[i].classList.remove(\'active\')" onclick="SS.Mentions._select(this)">'
        + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:28px;height:28px;border-radius:50%;object-fit:cover" loading="lazy">'
        + '<div style="flex:1;min-width:0"><div style="font-size:13px;font-weight:600">' + SS.utils.esc(u.fullname) + verified + '</div>'
        + (u.shipping_company ? '<div style="font-size:11px;color:var(--text-muted)">' + SS.utils.esc(u.shipping_company) + '</div>' : '')
        + '</div></div>';
    }
    dd.innerHTML = html;
  },

  _select: function(el) {
    var name = el.getAttribute('data-name');
    var input = SS.Mentions._input;
    if (!input || !name) return;

    var val = input.value;
    var cursor = input.selectionStart || val.length;
    var before = val.substring(0, cursor);
    var after = val.substring(cursor);
    var newBefore = before.replace(/@[a-zA-Z0-9\u00C0-\u024F\s]*$/, '@' + name + ' ');
    input.value = newBefore + after;
    input.selectionStart = input.selectionEnd = newBefore.length;
    input.focus();

    SS.Mentions._hideDropdown();
    if (SS.Mentions._callback) SS.Mentions._callback(name, el.getAttribute('data-id'));
  },

  _hideDropdown: function() {
    if (SS.Mentions._dropdown) SS.Mentions._dropdown.style.display = 'none';
  },

  // Convert @Name in text to clickable links (call when rendering post content)
  linkify: function(text) {
    if (!text) return '';
    return text.replace(/@([a-zA-Z0-9\u00C0-\u024F\s]{2,30})/g, function(match, name) {
      return '<a href="/people.html?search=' + encodeURIComponent(name.trim()) + '" style="color:var(--primary);font-weight:500;text-decoration:none">' + match + '</a>';
    });
  }
};
