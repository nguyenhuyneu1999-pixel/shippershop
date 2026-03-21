/**
 * ShipperShop Component — Subscription Badge
 * Shows Pro/VIP/Premium badge next to usernames in posts, profiles, comments
 */
window.SS = window.SS || {};

SS.SubBadge = {
  _badges: {
    2: {text: 'PRO', icon: '⭐', color: '#f59e0b', bg: '#fef3c7'},
    3: {text: 'VIP', icon: '👑', color: '#7C3AED', bg: '#EDE9FE'},
    4: {text: 'PREMIUM', icon: '💎', color: '#ec4899', bg: '#fce7f3'},
    5: {text: 'ELITE', icon: '🚀', color: '#3b82f6', bg: '#dbeafe'},
  },

  // Render badge HTML for a subscription plan ID
  render: function(planId) {
    planId = parseInt(planId);
    if (!planId || planId <= 1) return '';
    var b = SS.SubBadge._badges[planId];
    if (!b) return '';
    return ' <span style="display:inline-flex;align-items:center;gap:2px;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:700;background:' + b.bg + ';color:' + b.color + ';vertical-align:middle">' + b.icon + ' ' + b.text + '</span>';
  },

  // Render from user object (needs subscription_plan_id field)
  renderFromUser: function(user) {
    if (!user) return '';
    var planId = user.subscription_plan_id || user.plan_id || 0;
    return SS.SubBadge.render(planId);
  },

  // Render full badge card (for wallet/profile)
  renderCard: function(planId, planName) {
    planId = parseInt(planId);
    if (!planId || planId <= 1) return '';
    var b = SS.SubBadge._badges[planId] || {text: planName || '', icon: '📦', color: '#666', bg: '#f0f0f0'};
    return '<div style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:12px;background:' + b.bg + ';border:1px solid ' + b.color + '20">'
      + '<span style="font-size:18px">' + b.icon + '</span>'
      + '<span style="font-weight:700;color:' + b.color + '">' + (planName || b.text) + '</span>'
      + '</div>';
  }
};
