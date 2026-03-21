/**
 * ShipperShop Component — Platform Stats
 * Public platform statistics for landing page / about
 */
window.SS = window.SS || {};

SS.PlatformStats = {
  render: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    SS.api.get('/platform-stats.php').then(function(d) {
      var data = d.data || {};
      var items = [
        {icon: '👥', value: data.users ? data.users.total : 0, label: 'Shipper'},
        {icon: '📝', value: data.posts ? data.posts.total : 0, label: 'Bai viet'},
        {icon: '📦', value: data.deliveries ? data.deliveries.total : 0, label: 'Don giao'},
        {icon: '💬', value: data.comments ? data.comments.total : 0, label: 'Ghi chu'},
        {icon: '👥', value: data.groups ? data.groups.total : 0, label: 'Nhom'},
        {icon: '📍', value: data.provinces_covered || 0, label: 'Tinh/Thanh'},
        {icon: '🚛', value: data.shipping_companies || 0, label: 'Hang van chuyen'},
        {icon: '🟢', value: data.online_now || 0, label: 'Online'},
      ];
      var html = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px">';
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        html += '<div style="text-align:center;padding:12px"><div style="font-size:20px">' + it.icon + '</div>'
          + '<div style="font-size:18px;font-weight:800;color:var(--primary)">' + SS.utils.fN(it.value) + '</div>'
          + '<div class="text-xs text-muted">' + it.label + '</div></div>';
      }
      html += '</div>';
      el.innerHTML = html;
    }).catch(function() {});
  },

  // Animated counter on scroll
  animateIn: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    var observer = new IntersectionObserver(function(entries) {
      if (entries[0].isIntersecting) {
        SS.PlatformStats.render(containerId);
        observer.disconnect();
      }
    });
    observer.observe(el);
  }
};
