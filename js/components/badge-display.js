/**
 * ShipperShop Component — Badge Display
 * Renders user badges row, subscription indicator, badge tooltips
 * Uses: SS.api
 */
window.SS = window.SS || {};

SS.BadgeDisplay = {
  _cache: {},

  // Render badges for a user inline
  render: function(userId, containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    if (SS.BadgeDisplay._cache[userId]) {
      SS.BadgeDisplay._renderBadges(SS.BadgeDisplay._cache[userId], el);
      return;
    }
    SS.api.get('/badges.php?user_id=' + userId).then(function(d) {
      var badges = d.data || [];
      SS.BadgeDisplay._cache[userId] = badges;
      SS.BadgeDisplay._renderBadges(badges, el);
    }).catch(function() {});
  },

  _renderBadges: function(badges, el) {
    if (!badges.length) { el.innerHTML = ''; return; }
    var html = '<span style="display:inline-flex;gap:3px;align-items:center;margin-left:4px">';
    for (var i = 0; i < Math.min(badges.length, 4); i++) {
      var b = badges[i];
      html += '<span title="' + SS.utils.esc(b.desc || b.name) + '" style="font-size:11px;font-weight:700;padding:1px 5px;border-radius:4px;background:' + b.color + '18;color:' + b.color + ';cursor:default;white-space:nowrap">' + b.name + '</span>';
    }
    if (badges.length > 4) html += '<span style="font-size:10px;color:var(--text-muted)">+' + (badges.length - 4) + '</span>';
    html += '</span>';
    el.innerHTML = html;
  },

  // Get subscription badge HTML (for post cards)
  getSubBadge: function(planId) {
    if (!planId || planId <= 1) return '';
    var badges = {2:'⭐ PRO', 3:'👑 VIP', 4:'💎 PREMIUM'};
    var colors = {2:'#f59e0b', 3:'#8b5cf6', 4:'#ec4899'};
    var name = badges[planId] || '';
    if (!name) return '';
    return '<span style="font-size:10px;font-weight:700;padding:1px 5px;border-radius:4px;background:' + (colors[planId] || '#999') + '18;color:' + (colors[planId] || '#999') + '">' + name + '</span>';
  },

  // Show all badges modal for a user
  showAll: function(userId) {
    SS.api.get('/badges.php?user_id=' + userId).then(function(d) {
      var badges = d.data || [];
      if (!badges.length) { SS.ui.toast('Chưa có huy hiệu', 'info'); return; }
      var html = '';
      for (var i = 0; i < badges.length; i++) {
        var b = badges[i];
        html += '<div class="list-item" style="padding:10px 0">'
          + '<span style="font-size:20px;width:36px;text-align:center;display:inline-flex;align-items:center;justify-content:center;height:36px;border-radius:8px;background:' + b.color + '15">' + b.name.split(' ')[0] + '</span>'
          + '<div class="flex-1"><div class="text-sm font-medium">' + SS.utils.esc(b.name) + '</div><div class="text-xs text-muted">' + SS.utils.esc(b.desc || '') + '</div></div>'
          + '</div>';
      }
      SS.ui.sheet({title: 'Huy hiệu (' + badges.length + ')', html: html});
    });
  }
};
