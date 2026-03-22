window.SS = window.SS || {};
SS.EmptyList = {
  render: function(containerId, options) {
    var el = document.getElementById(containerId);
    if (!el) return;
    var opts = options || {};
    el.innerHTML = '<div class="text-center text-muted text-xs">EmptyList component</div>';
  }
};
