/**
 * ShipperShop Component — Conversation Auto-Archive
 */
window.SS = window.SS || {};

SS.ConvAutoArchive = {
  show: function() {
    SS.api.get('/conv-auto-archive.php').then(function(d) {
      var data = d.data || {};
      var s = data.settings || {};

      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold">' + (data.total_conversations || 0) + '</div><div class="text-xs text-muted">Tong</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--warning)">' + (data.inactive_count || 0) + '</div><div class="text-xs text-muted">Khong hoat dong</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold" style="color:var(--text-muted)">' + (data.archived_count || 0) + '</div><div class="text-xs text-muted">Da luu tru</div></div></div>';

      // Settings
      html += '<div class="card" style="padding:12px"><div class="flex justify-between items-center mb-2"><span class="text-sm font-bold">Tu dong luu tru</span>'
        + '<div style="width:40px;height:22px;border-radius:11px;background:' + (s.enabled ? 'var(--primary)' : 'var(--border)') + ';cursor:pointer;position:relative" onclick="SS.ConvAutoArchive.toggle()">'
        + '<div style="width:18px;height:18px;border-radius:50%;background:#fff;position:absolute;top:2px;' + (s.enabled ? 'right:2px' : 'left:2px') + ';transition:all .2s"></div></div></div>'
        + '<div class="text-xs text-muted">Luu tru cuoc tro chuyen khong hoat dong sau ' + (s.days_inactive || 30) + ' ngay</div></div>';

      SS.ui.sheet({title: 'Tu dong luu tru', html: html});
    });
  },

  toggle: function() {
    SS.api.get('/conv-auto-archive.php').then(function(d) {
      var s = (d.data || {}).settings || {};
      SS.api.post('/conv-auto-archive.php', {enabled: !s.enabled, days_inactive: s.days_inactive || 30}).then(function(r) {
        SS.ui.toast(r.message || 'OK', 'success');
        SS.ConvAutoArchive.show();
      });
    });
  }
};
