window.SS = window.SS || {};
SS.ConvShiftSwap = {
  show: function(arg) {
    var url = '/conv-shift-swap.php' + (arg ? '?conversation_id=' + arg : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var keys = Object.keys(data);
      var html = '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;text-align:center">';
      for (var i = 0; i < Math.min(keys.length, 8); i++) {
        var val = data[keys[i]];
        if (typeof val === 'object') continue;
        html += '<div class="card" style="padding:8px"><div class="font-bold">' + val + '</div><div class="text-xs text-muted">' + SS.utils.esc(keys[i]) + '</div></div>';
      }
      html += '</div>';
      SS.ui.sheet({title: 'ConvShiftSwap', html: html});
    });
  }
};
