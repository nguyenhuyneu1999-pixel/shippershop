/**
 * ShipperShop Component — User Notes
 * Private notepad for shippers (delivery notes, addresses, reminders)
 */
window.SS = window.SS || {};

SS.UserNotes = {
  show: function(category) {
    var url = '/user-notes.php' + (category ? '?category=' + category : '');
    SS.api.get(url).then(function(d) {
      var notes = (d.data || {}).notes || [];
      var cats = ['general', 'delivery', 'address', 'reminder'];
      var catNames = {general: 'Chung', delivery: 'Giao hang', address: 'Dia chi', reminder: 'Nhac nho'};
      var catIcons = {general: '📝', delivery: '📦', address: '📍', reminder: '⏰'};

      var html = '<div class="flex gap-2 mb-3" style="overflow-x:auto">'
        + '<div class="chip ' + (!category ? 'chip-active' : '') + '" onclick="SS.UserNotes.show()" style="cursor:pointer">Tat ca</div>';
      for (var c = 0; c < cats.length; c++) {
        html += '<div class="chip ' + (category === cats[c] ? 'chip-active' : '') + '" onclick="SS.UserNotes.show(\'' + cats[c] + '\')" style="cursor:pointer">' + catIcons[cats[c]] + ' ' + catNames[cats[c]] + '</div>';
      }
      html += '</div>';
      html += '<button class="btn btn-primary btn-sm mb-3" onclick="SS.UserNotes.add()"><i class="fa-solid fa-plus"></i> Tao ghi chu</button>';

      if (!notes.length) {
        html += '<div class="empty-state p-4"><div class="empty-icon">📋</div><div class="empty-text">Chua co ghi chu</div></div>';
      }
      for (var i = 0; i < notes.length; i++) {
        var n = notes[i];
        var pinIcon = n.pinned ? '<i class="fa-solid fa-thumbtack" style="color:var(--primary)"></i> ' : '';
        html += '<div class="card mb-2" style="padding:12px' + (n.pinned ? ';border-left:3px solid var(--primary)' : '') + '">'
          + '<div class="flex justify-between items-start"><div class="font-medium text-sm">' + pinIcon + SS.utils.esc(n.title || 'Khong tieu de') + '</div>'
          + '<div class="flex gap-1">'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.UserNotes.pin(' + n.id + ')" style="padding:2px 4px"><i class="fa-solid fa-thumbtack" style="font-size:11px"></i></button>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.UserNotes.del(' + n.id + ')" style="padding:2px 4px"><i class="fa-solid fa-trash text-danger" style="font-size:11px"></i></button>'
          + '</div></div>'
          + (n.content ? '<div class="text-sm text-muted mt-1" style="white-space:pre-wrap">' + SS.utils.esc(n.content.substring(0, 200)) + '</div>' : '')
          + '<div class="text-xs text-muted mt-1">' + (catIcons[n.category] || '') + ' ' + (catNames[n.category] || n.category) + ' · ' + SS.utils.ago(n.updated_at || n.created_at) + '</div></div>';
      }
      SS.ui.sheet({title: 'Ghi chu ca nhan (' + notes.length + ')', html: html});
    });
  },

  add: function() {
    SS.ui.modal({
      title: 'Tao ghi chu',
      html: '<input id="un-title" class="form-input mb-2" placeholder="Tieu de...">'
        + '<textarea id="un-content" class="form-textarea" rows="4" placeholder="Noi dung..."></textarea>'
        + '<select id="un-cat" class="form-select mt-2"><option value="general">Chung</option><option value="delivery">Giao hang</option><option value="address">Dia chi</option><option value="reminder">Nhac nho</option></select>',
      confirmText: 'Luu',
      onConfirm: function() {
        SS.api.post('/user-notes.php?action=add', {
          title: document.getElementById('un-title').value,
          content: document.getElementById('un-content').value,
          category: document.getElementById('un-cat').value
        }).then(function() { SS.ui.toast('Da luu!', 'success'); SS.UserNotes.show(); });
      }
    });
  },

  pin: function(id) {
    SS.api.post('/user-notes.php?action=pin', {note_id: id}).then(function() { SS.UserNotes.show(); });
  },
  del: function(id) {
    SS.ui.confirm('Xoa ghi chu nay?', function() {
      SS.api.post('/user-notes.php?action=delete', {note_id: id}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.UserNotes.show(); });
    });
  }
};
