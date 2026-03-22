/**
 * ShipperShop Component — Content Warnings
 */
window.SS = window.SS || {};

SS.ContentWarnings = {
  show: function(postId) {
    SS.api.get('/content-warnings.php?post_id=' + postId).then(function(d) {
      var data = d.data || {};
      var warnings = data.warnings || [];
      var types = data.types || [];

      var html = '<div class="text-sm text-muted mb-3">Chon canh bao noi dung cho bai viet</div>';
      for (var i = 0; i < types.length; i++) {
        var t = types[i];
        var active = warnings.indexOf(t.id) >= 0;
        html += '<div class="list-item" style="cursor:pointer;padding:10px" onclick="SS.ContentWarnings.toggle(' + postId + ',\'' + t.id + '\',\'' + (active ? 'remove' : 'add') + '\')">'
          + '<span style="font-size:18px">' + t.icon + '</span>'
          + '<div class="flex-1"><div class="text-sm font-medium">' + SS.utils.esc(t.name) + '</div><div class="text-xs text-muted">' + SS.utils.esc(t.desc) + '</div></div>'
          + (active ? '<span style="color:var(--warning)">✅</span>' : '<span class="text-muted">○</span>') + '</div>';
      }
      SS.ui.sheet({title: '⚠️ Canh bao noi dung', html: html});
    });
  },

  toggle: function(postId, warningId, action) {
    SS.ui.closeSheet();
    SS.api.post('/content-warnings.php?action=' + action, {post_id: postId, warning_id: warningId}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success');
      SS.ContentWarnings.show(postId);
    });
  },

  // Render warning badge on post
  renderBadge: function(postId, containerId) {
    SS.api.get('/content-warnings.php?post_id=' + postId).then(function(d) {
      var el = document.getElementById(containerId);
      if (!el) return;
      var warnings = (d.data || {}).warnings || [];
      if (!warnings.length) { el.innerHTML = ''; return; }
      el.innerHTML = '<span class="chip" style="font-size:10px;background:var(--warning);color:#fff;cursor:pointer" onclick="SS.ContentWarnings.show(' + postId + ')">⚠️ ' + warnings.length + ' canh bao</span>';
    }).catch(function() {});
  }
};
