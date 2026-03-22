/**
 * ShipperShop Component — Skill Tags
 * Shipper skill tagging: vehicle, area, experience
 */
window.SS = window.SS || {};

SS.SkillTags = {
  show: function(userId) {
    SS.api.get('/skill-tags.php' + (userId ? '?user_id=' + userId : '')).then(function(d) {
      var data = d.data || {};
      var tags = data.tags || [];
      var presets = data.presets || [];
      var isOwn = !userId || (SS.store && SS.store.getUser() && SS.store.getUser().id == userId);
      var catNames = {vehicle: '🏍️ Phuong tien', area: '📍 Khu vuc', type: '📦 Loai hang', experience: '⏱️ Kinh nghiem', time: '⏰ Thoi gian'};

      var html = '';
      if (tags.length) {
        html += '<div class="flex gap-2 flex-wrap mb-3">';
        for (var i = 0; i < tags.length; i++) html += '<span class="chip">' + SS.utils.esc(tags[i]) + '</span>';
        html += '</div>';
      }

      if (isOwn && presets.length) {
        html += '<div class="divider mb-3"></div><div class="text-sm font-bold mb-2">Chon ky nang</div>';
        for (var c = 0; c < presets.length; c++) {
          var cat = presets[c];
          html += '<div class="text-xs text-muted mt-2 mb-1">' + (catNames[cat.cat] || cat.cat) + '</div><div class="flex gap-2 flex-wrap">';
          for (var t = 0; t < cat.tags.length; t++) {
            var active = tags.indexOf(cat.tags[t]) >= 0;
            html += '<span class="chip ' + (active ? 'chip-active' : '') + '" style="cursor:pointer" onclick="SS.SkillTags._toggle(\'' + SS.utils.esc(cat.tags[t]).replace(/'/g, '\\x27') + '\')">' + SS.utils.esc(cat.tags[t]) + '</span>';
          }
          html += '</div>';
        }
      }

      if (!tags.length && !isOwn) html = '<div class="text-sm text-muted text-center p-3">Chua cap nhat ky nang</div>';
      SS.ui.sheet({title: 'Ky nang Shipper' + (tags.length ? ' (' + tags.length + ')' : ''), html: html});
      SS.SkillTags._current = tags.slice();
    });
  },

  _current: [],

  _toggle: function(tag) {
    var idx = SS.SkillTags._current.indexOf(tag);
    if (idx >= 0) SS.SkillTags._current.splice(idx, 1);
    else SS.SkillTags._current.push(tag);
    SS.api.post('/skill-tags.php', {tags: SS.SkillTags._current}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success', 1500);
      SS.SkillTags.show();
    });
  }
};
