/**
 * ShipperShop Component — Conversation Auto-Label
 */
window.SS = window.SS || {};

SS.ConvAutoLabel = {
  show: function(conversationId) {
    SS.api.get('/conv-auto-label.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var suggested = data.suggested || [];
      var labels = data.labels || [];

      var html = '';
      if (suggested.length) {
        html += '<div class="text-sm font-bold mb-2">De xuat tu dong</div><div class="flex gap-2 flex-wrap mb-3">';
        for (var i = 0; i < suggested.length; i++) {
          var s = suggested[i].label;
          html += '<div class="chip chip-active" style="cursor:pointer" onclick="SS.ConvAutoLabel.set(' + conversationId + ',\'' + s.id + '\')">'
            + s.icon + ' ' + SS.utils.esc(s.name) + ' <span class="text-xs">(' + suggested[i].score + ')</span></div>';
        }
        html += '</div>';
      }

      html += '<div class="text-sm font-bold mb-2">Tat ca nhan</div>';
      for (var j = 0; j < labels.length; j++) {
        var l = labels[j];
        html += '<div class="list-item" style="cursor:pointer;padding:10px" onclick="SS.ConvAutoLabel.set(' + conversationId + ',\'' + l.id + '\')">'
          + '<span style="font-size:18px">' + l.icon + '</span><span class="text-sm">' + SS.utils.esc(l.name) + '</span></div>';
      }
      SS.ui.sheet({title: '🏷️ Gan nhan cuoc tro chuyen', html: html});
    });
  },

  set: function(conversationId, labelId) {
    SS.ui.closeSheet();
    SS.api.post('/conv-auto-label.php', {conversation_id: conversationId, label_id: labelId}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success');
    });
  }
};
