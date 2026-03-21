/**
 * ShipperShop Component — Conversation Tags
 * Tag conversations with preset or custom labels
 */
window.SS = window.SS || {};

SS.ConvTags = {
  open: function(convId) {
    SS.api.get('/conv-tags.php?conversation_id=' + convId).then(function(d) {
      var data = d.data || {};
      var currentTags = data.tags || [];
      var presets = data.presets || [];
      var html = '';
      for (var i = 0; i < presets.length; i++) {
        var p = presets[i];
        var active = currentTags.indexOf(p.id) >= 0;
        html += '<div class="list-item" style="cursor:pointer' + (active ? ';background:var(--primary-light)' : '') + '" onclick="SS.ConvTags._toggle(' + convId + ',\'' + p.id + '\',' + JSON.stringify(currentTags).replace(/"/g, '&quot;') + ')">'
          + '<span style="font-size:16px">' + p.icon + '</span>'
          + '<div class="flex-1"><div class="font-medium text-sm">' + SS.utils.esc(p.name) + '</div></div>'
          + (active ? '<i class="fa-solid fa-check" style="color:var(--primary)"></i>' : '') + '</div>';
      }
      SS.ui.sheet({title: 'Tag hoi thoai', html: html});
    });
  },
  _toggle: function(convId, tagId, current) {
    var idx = current.indexOf(tagId);
    if (idx >= 0) current.splice(idx, 1); else current.push(tagId);
    SS.api.post('/conv-tags.php', {conversation_id: convId, tags: current}).then(function() {
      SS.ui.toast('Da cap nhat!', 'success', 1500);
      SS.ConvTags.open(convId);
    });
  },
  renderTags: function(convId, containerId) {
    SS.api.get('/conv-tags.php?conversation_id=' + convId).then(function(d) {
      var tags = (d.data || {}).tags || [];
      var presets = (d.data || {}).presets || [];
      var el = document.getElementById(containerId);
      if (!el || !tags.length) return;
      var presetMap = {};
      for (var i = 0; i < presets.length; i++) presetMap[presets[i].id] = presets[i];
      var html = '';
      for (var j = 0; j < tags.length; j++) {
        var p = presetMap[tags[j]];
        if (p) html += '<span style="display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;background:' + p.color + '20;color:' + p.color + '">' + p.icon + ' ' + p.name + '</span> ';
      }
      el.innerHTML = html;
    });
  }
};
