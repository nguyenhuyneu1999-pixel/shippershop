window.SS = window.SS || {};
SS.DeliveryNotes = {
  show: function() {
    SS.api.get('/delivery-notes.php').then(function(d) {
      var notes = (d.data || {}).notes || [];
      var html = '<div class="flex gap-2 mb-3"><input id="dn-search" class="form-input flex-1" placeholder="Tim theo dia chi..."><button class="btn btn-primary btn-sm" onclick="SS.DeliveryNotes.search()"><i class="fa-solid fa-search"></i></button></div>';
      html += '<button class="btn btn-ghost btn-sm mb-3" onclick="SS.DeliveryNotes.add()"><i class="fa-solid fa-plus"></i> Them ghi chu</button>';
      if (!notes.length) html += '<div class="empty-state p-3"><div class="empty-icon">📝</div><div class="empty-text">Chua co ghi chu giao hang</div></div>';
      for (var i = 0; i < Math.min(notes.length, 15); i++) {
        var n = notes[i];
        html += '<div class="card mb-2" style="padding:10px"><div class="font-bold text-sm">📍 ' + SS.utils.esc(n.address) + '</div>'
          + '<div class="text-xs mt-1">' + SS.utils.esc(n.note) + '</div>'
          + (n.tags && n.tags.length ? '<div class="flex gap-1 mt-1">' + n.tags.map(function(t) { return '<span class="chip" style="font-size:10px">' + SS.utils.esc(t) + '</span>'; }).join('') + '</div>' : '')
          + '<div class="text-xs text-muted mt-1">' + SS.utils.ago(n.created_at) + '</div></div>';
      }
      SS.ui.sheet({title: '📝 Ghi chu giao hang (' + (d.data || {}).count + ')', html: html});
    });
  },
  search: function() { var q = document.getElementById('dn-search').value; SS.api.get('/delivery-notes.php?search=' + encodeURIComponent(q)).then(function(d) { SS.ui.toast((d.data || {}).count + ' ket qua', 'info'); }); },
  add: function() {
    SS.ui.modal({title: 'Them ghi chu', html: '<input id="dn-addr" class="form-input mb-2" placeholder="Dia chi"><textarea id="dn-note" class="form-textarea" rows="3" placeholder="Ghi chu (ma cong, huong dan...)"></textarea>', confirmText: 'Luu',
      onConfirm: function() { SS.api.post('/delivery-notes.php', {address: document.getElementById('dn-addr').value, note: document.getElementById('dn-note').value}).then(function() { SS.DeliveryNotes.show(); }); }
    });
  }
};
