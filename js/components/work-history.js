/**
 * ShipperShop Component — Work History
 * Shipper work experience log
 */
window.SS = window.SS || {};

SS.WorkHistory = {
  show: function(userId) {
    SS.api.get('/work-history.php' + (userId ? '?user_id=' + userId : '')).then(function(d) {
      var history = (d.data || {}).history || [];
      var isOwn = !userId || (SS.store && SS.store.getUser() && SS.store.getUser().id == userId);
      var html = '';
      if (isOwn) html += '<button class="btn btn-primary btn-sm mb-3" onclick="SS.WorkHistory.add()"><i class="fa-solid fa-plus"></i> Them kinh nghiem</button>';
      if (!history.length) {
        html += '<div class="empty-state p-3"><div class="empty-icon">💼</div><div class="empty-text">Chua co kinh nghiem</div></div>';
      }
      for (var i = 0; i < history.length; i++) {
        var h = history[i];
        html += '<div class="card mb-2" style="padding:12px;border-left:3px solid var(--primary)">'
          + '<div class="flex justify-between"><div class="font-bold text-sm">' + SS.utils.esc(h.company) + '</div>'
          + (isOwn ? '<button class="btn btn-ghost btn-sm" onclick="SS.WorkHistory.del(' + h.id + ')" style="padding:2px"><i class="fa-solid fa-trash text-danger" style="font-size:11px"></i></button>' : '') + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(h.role || 'Shipper') + (h.area ? ' · ' + SS.utils.esc(h.area) : '') + '</div>'
          + '<div class="text-xs mt-1">' + SS.utils.esc(h.start_date || '') + ' - ' + (h.current ? '<span style="color:var(--success)">Hien tai</span>' : SS.utils.esc(h.end_date || '')) + '</div></div>';
      }
      SS.ui.sheet({title: 'Kinh nghiem lam viec (' + history.length + ')', html: html});
    });
  },
  add: function() {
    SS.ui.modal({
      title: 'Them kinh nghiem',
      html: '<input id="wh-company" class="form-input mb-2" placeholder="Ten cong ty (VD: GHTK, GHN)">'
        + '<input id="wh-role" class="form-input mb-2" placeholder="Vi tri (VD: Shipper, Team Lead)" value="Shipper">'
        + '<input id="wh-area" class="form-input mb-2" placeholder="Khu vuc (VD: TPHCM, Ha Noi)">'
        + '<div class="flex gap-2 mb-2"><input id="wh-start" class="form-input" type="month" placeholder="Bat dau"><input id="wh-end" class="form-input" type="month" placeholder="Ket thuc"></div>'
        + '<label class="flex items-center gap-2 text-sm"><input type="checkbox" id="wh-current"> Dang lam o day</label>',
      confirmText: 'Them',
      onConfirm: function() {
        SS.api.post('/work-history.php?action=add', {
          company: document.getElementById('wh-company').value,
          role: document.getElementById('wh-role').value,
          area: document.getElementById('wh-area').value,
          start_date: document.getElementById('wh-start').value,
          end_date: document.getElementById('wh-end').value,
          current: document.getElementById('wh-current').checked
        }).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); SS.WorkHistory.show(); });
      }
    });
  },
  del: function(id) {
    SS.ui.confirm('Xoa muc nay?', function() {
      SS.api.post('/work-history.php?action=delete', {item_id: id}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.WorkHistory.show(); });
    });
  }
};
