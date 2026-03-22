window.SS = window.SS || {};
SS.LoadingSkeleton = {
  render: function(containerId, options) {
    var el = document.getElementById(containerId);
    if (!el) return;
    var opts = options || {};
    el.innerHTML = '<div class="text-center text-muted text-xs">LoadingSkeleton component</div>';
  }
};
