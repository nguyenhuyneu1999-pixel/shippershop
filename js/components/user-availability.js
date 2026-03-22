/**
 * ShipperShop Component — User Availability
 */
window.SS = window.SS || {};

SS.UserAvailability = {
  show: function() {
    SS.api.get('/user-availability.php?user_id=0').then(function(d) {
      var statuses = (d.data || {}).statuses || [];
      var html = '<div class="text-sm text-muted mb-3">Chon trang thai cua ban</div>';
      for (var i = 0; i < statuses.length; i++) {
        var s = statuses[i];
        html += '<div class="list-item" style="cursor:pointer;padding:12px" onclick="SS.UserAvailability.set(\'' + s.id + '\')">'
          + '<span style="font-size:20px">' + s.icon + '</span>'
          + '<span class="text-sm font-medium" style="color:' + s.color + '">' + SS.utils.esc(s.name) + '</span></div>';
      }
      SS.ui.sheet({title: 'Trang thai', html: html});
    });
  },
  set: function(status) {
    SS.ui.closeSheet();
    SS.api.post('/user-availability.php', {status: status}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success');
    });
  },
  getBadge: function(userId, containerId) {
    SS.api.get('/user-availability.php?user_id=' + userId).then(function(d) {
      var el = document.getElementById(containerId);
      if (!el) return;
      var cur = (d.data || {}).current || {};
      var st = (d.data || {}).statuses || [];
      var found = null;
      for (var i = 0; i < st.length; i++) { if (st[i].id === cur.status) { found = st[i]; break; } }
      if (found) el.innerHTML = '<span style="font-size:10px" title="' + SS.utils.esc(found.name) + '">' + found.icon + '</span>';
    }).catch(function() {});
  }
};
