/**
 * ShipperShop Component — Reputation Tiers
 * Shows user tier, progression, rewards
 */
window.SS = window.SS || {};

SS.ReputationTiers = {
  show: function(userId) {
    SS.api.get('/reputation-tiers.php?action=user&user_id=' + (userId || '')).then(function(d) {
      var data = d.data || {};
      var tier = data.tier || {};
      var next = data.next_tier;
      var bd = data.breakdown || {};

      // Tier badge
      var html = '<div class="text-center mb-3" style="padding:16px;background:linear-gradient(135deg,' + (tier.color || '#999') + '20,' + (tier.color || '#999') + '05);border-radius:12px">'
        + '<div style="font-size:48px">' + (tier.icon || '') + '</div>'
        + '<div style="font-size:22px;font-weight:800;color:' + (tier.color || '#999') + '">' + SS.utils.esc(tier.name || '') + '</div>'
        + '<div class="text-sm">Diem: <span class="font-bold">' + (data.score || 0) + '</span></div></div>';

      // Progress to next
      if (next) {
        html += '<div class="card mb-3" style="padding:12px"><div class="flex justify-between text-sm mb-1"><span>Tien den ' + next.icon + ' ' + SS.utils.esc(next.name) + '</span><span class="font-bold">' + (data.progress || 0) + '%</span></div>'
          + '<div style="height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + (data.progress || 0) + '%;height:100%;background:' + (next.color || 'var(--primary)') + ';border-radius:4px;transition:width 1s"></div></div>'
          + '<div class="text-xs text-muted mt-1">Can ' + (next.min_score - data.score) + ' diem nua</div></div>';
      }

      // Breakdown
      html += '<div class="text-sm font-bold mb-2">Chi tiet diem</div>';
      var items = [
        {label: 'Bai viet (x2)', value: bd.posts || 0, icon: '📝'},
        {label: 'Luot thich', value: bd.likes || 0, icon: '❤️'},
        {label: 'Binh luan', value: bd.comments || 0, icon: '💬'},
        {label: 'Follower (x5)', value: bd.followers || 0, icon: '👥'},
        {label: 'Don giao (/10)', value: bd.deliveries || 0, icon: '📦'},
        {label: 'XP (/5)', value: bd.xp || 0, icon: '⭐'},
        {label: 'Huy hieu (x10)', value: bd.badges || 0, icon: '🏅'},
      ];
      for (var i = 0; i < items.length; i++) {
        html += '<div class="flex justify-between text-sm p-1" style="border-bottom:1px solid var(--border-light)"><span>' + items[i].icon + ' ' + items[i].label + '</span><span class="font-bold">+' + items[i].value + '</span></div>';
      }

      // Perks
      if (tier.perks && tier.perks.length) {
        html += '<div class="text-sm font-bold mt-3 mb-2">Dac quyen ' + SS.utils.esc(tier.name) + '</div><div class="flex gap-2 flex-wrap">';
        for (var j = 0; j < tier.perks.length; j++) html += '<span class="chip">✅ ' + SS.utils.esc(tier.perks[j]) + '</span>';
        html += '</div>';
      }

      SS.ui.sheet({title: 'Cap bac Shipper', html: html});
    });
  },

  // Compact tier badge
  renderBadge: function(userId, containerId) {
    SS.api.get('/reputation-tiers.php?action=user&user_id=' + userId).then(function(d) {
      var el = document.getElementById(containerId);
      if (!el) return;
      var tier = (d.data || {}).tier || {};
      if (!tier.icon) return;
      el.innerHTML = '<span style="display:inline-flex;align-items:center;gap:2px;padding:1px 6px;border-radius:6px;font-size:11px;font-weight:600;background:' + (tier.color || '#999') + '15;color:' + (tier.color || '#999') + ';cursor:pointer" onclick="SS.ReputationTiers.show(' + userId + ')">' + tier.icon + ' ' + SS.utils.esc(tier.name) + '</span>';
    }).catch(function() {});
  },

  // All tiers overview
  showAll: function() {
    SS.api.get('/reputation-tiers.php?action=tiers').then(function(d) {
      var tiers = (d.data || {}).tiers || [];
      var html = '';
      for (var i = 0; i < tiers.length; i++) {
        var t = tiers[i];
        html += '<div class="card mb-2" style="padding:12px;border-left:4px solid ' + t.color + '">'
          + '<div class="flex items-center gap-2"><span style="font-size:24px">' + t.icon + '</span><div><div class="font-bold" style="color:' + t.color + '">' + SS.utils.esc(t.name) + '</div><div class="text-xs text-muted">' + t.min_score + '+ diem</div></div></div>'
          + '<div class="flex gap-1 flex-wrap mt-2">';
        for (var j = 0; j < (t.perks || []).length; j++) html += '<span class="chip" style="font-size:10px">' + SS.utils.esc(t.perks[j]) + '</span>';
        html += '</div></div>';
      }
      SS.ui.sheet({title: 'He thong cap bac', html: html});
    });
  }
};
