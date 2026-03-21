/**
 * ShipperShop Component — Post Type Badges
 * Visual category badges for posts: tìm hàng, giao hàng, cần ship, etc.
 */
window.SS = window.SS || {};

SS.PostTypes = {
  _types: {
    'post':     {label: 'Chia sẻ',     icon: '📝', color: '#7C3AED', bg: '#EDE9FE'},
    'delivery': {label: 'Giao hàng',   icon: '📦', color: '#22c55e', bg: '#dcfce7'},
    'pickup':   {label: 'Nhận hàng',   icon: '🏪', color: '#3b82f6', bg: '#dbeafe'},
    'search':   {label: 'Tìm hàng',   icon: '🔍', color: '#f59e0b', bg: '#fef3c7'},
    'job':      {label: 'Tuyển dụng',  icon: '💼', color: '#ec4899', bg: '#fce7f3'},
    'review':   {label: 'Đánh giá',   icon: '⭐', color: '#eab308', bg: '#fef9c3'},
    'question': {label: 'Hỏi đáp',    icon: '❓', color: '#6366f1', bg: '#e0e7ff'},
    'tip':      {label: 'Mẹo hay',    icon: '💡', color: '#14b8a6', bg: '#ccfbf1'},
    'alert':    {label: 'Cảnh báo',    icon: '⚠️', color: '#ef4444', bg: '#fee2e2'},
    'sale':     {label: 'Mua bán',     icon: '🛒', color: '#EE4D2D', bg: '#FFF3EF'},
  },

  // Get type info
  get: function(type) {
    return SS.PostTypes._types[type] || SS.PostTypes._types['post'];
  },

  // Render badge HTML
  badge: function(type) {
    var t = SS.PostTypes.get(type);
    return '<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:' + t.bg + ';color:' + t.color + '">' + t.icon + ' ' + t.label + '</span>';
  },

  // Render filter chips for feed
  renderFilters: function(containerId, activeType, onChange) {
    var el = document.getElementById(containerId);
    if (!el) return;
    var keys = Object.keys(SS.PostTypes._types);
    var html = '<div style="display:flex;gap:6px;overflow-x:auto;padding:4px 0;scrollbar-width:none">';
    html += '<div class="chip ' + (!activeType ? 'chip-active' : '') + '" onclick="(' + onChange + ')(\'\')">Tất cả</div>';
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      var t = SS.PostTypes._types[k];
      html += '<div class="chip ' + (activeType === k ? 'chip-active' : '') + '" onclick="(' + onChange + ')(\'' + k + '\')" style="white-space:nowrap">' + t.icon + ' ' + t.label + '</div>';
    }
    html += '</div>';
    el.innerHTML = html;
  },

  // Get all types (for select dropdown)
  all: function() {
    var result = [];
    var keys = Object.keys(SS.PostTypes._types);
    for (var i = 0; i < keys.length; i++) {
      var t = SS.PostTypes._types[keys[i]];
      result.push({key: keys[i], label: t.label, icon: t.icon});
    }
    return result;
  }
};
