window.SS = window.SS || {};
SS.CustomerContacts = {
  show: function() {
    SS.api.get('/customer-contacts.php').then(function(d) {
      var data = d.data || {};
      var contacts = data.contacts || [];
      var html = '<div class="flex gap-2 mb-3"><input id="cc-search" class="form-input flex-1" placeholder="Tim ten, SDT, dia chi..."><button class="btn btn-primary btn-sm" onclick="SS.CustomerContacts.search()"><i class="fa-solid fa-search"></i></button></div>';
      html += '<button class="btn btn-ghost btn-sm mb-3" onclick="SS.CustomerContacts.add()"><i class="fa-solid fa-user-plus"></i> Them lien he</button>';
      var prefIcons = {any: '🕐', morning: '🌅', afternoon: '☀️', evening: '🌙'};
      for (var i = 0; i < Math.min(contacts.length, 20); i++) {
        var c = contacts[i];
        html += '<div class="card mb-2" style="padding:10px"><div class="flex justify-between"><div class="flex-1"><div class="font-bold text-sm">' + (c.favorite ? '⭐ ' : '') + SS.utils.esc(c.name) + '</div>'
          + '<div class="text-xs text-muted">📞 ' + SS.utils.esc(c.phone) + (c.district ? ' · 📍 ' + SS.utils.esc(c.district) : '') + ' · ' + (prefIcons[c.preference] || '🕐') + '</div>'
          + (c.address ? '<div class="text-xs text-muted">' + SS.utils.esc(c.address).substring(0, 50) + '</div>' : '') + '</div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.CustomerContacts.fav(' + c.id + ')" style="font-size:14px">' + (c.favorite ? '⭐' : '☆') + '</button></div></div>';
      }
      if (!contacts.length) html += '<div class="empty-state p-3"><div class="empty-icon">👥</div><div class="empty-text">Chua co lien he</div></div>';
      SS.ui.sheet({title: '👥 Khach hang (' + (data.count || 0) + ')', html: html});
    });
  },
  search: function() { var q = document.getElementById('cc-search').value; SS.api.get('/customer-contacts.php?search=' + encodeURIComponent(q)).then(function(d) { SS.ui.toast((d.data || {}).count + ' ket qua', 'info'); }); },
  add: function() {
    SS.ui.modal({title: 'Them lien he', html: '<input id="cc-name" class="form-input mb-2" placeholder="Ten"><input id="cc-phone" class="form-input mb-2" placeholder="SDT"><input id="cc-addr" class="form-input mb-2" placeholder="Dia chi"><input id="cc-dist" class="form-input mb-2" placeholder="Quan/Huyen"><select id="cc-pref" class="form-select"><option value="any">🕐 Bat ky</option><option value="morning">🌅 Sang</option><option value="afternoon">☀️ Chieu</option><option value="evening">🌙 Toi</option></select>', confirmText: 'Them',
      onConfirm: function() { SS.api.post('/customer-contacts.php', {name: document.getElementById('cc-name').value, phone: document.getElementById('cc-phone').value, address: document.getElementById('cc-addr').value, district: document.getElementById('cc-dist').value, preference: document.getElementById('cc-pref').value}).then(function() { SS.CustomerContacts.show(); }); }
    });
  },
  fav: function(id) { SS.api.post('/customer-contacts.php?action=favorite', {contact_id: id}).then(function() { SS.CustomerContacts.show(); }); }
};
