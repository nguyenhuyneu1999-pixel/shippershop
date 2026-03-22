window.SS = window.SS || {};
SS.ConvOrderBoard = {
  show: function(conversationId) {
    SS.api.get('/conv-order-board.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var board = data.board || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvOrderBoard.add(' + conversationId + ')"><i class="fa-solid fa-plus"></i> Them don</button>';
      html += '<div class="flex justify-between mb-2"><span class="text-xs text-muted">' + (data.done || 0) + '/' + (data.total || 0) + ' hoan thanh</span><span class="text-xs font-bold" style="color:var(--primary)">' + (data.completion || 0) + '%</span></div>';
      for (var c = 0; c < board.length; c++) {
        var col = board[c];
        if (!col.items.length && col.column.id !== 'pending') continue;
        html += '<div class="mb-3"><div class="text-xs font-bold mb-1" style="color:' + col.column.color + '">' + col.column.icon + ' ' + SS.utils.esc(col.column.name) + ' (' + col.count + ')</div>';
        for (var i = 0; i < col.items.length; i++) {
          var o = col.items[i];
          html += '<div class="card mb-1" style="padding:8px;border-left:3px solid ' + col.column.color + '"><div class="flex justify-between"><span class="text-sm">' + SS.utils.esc(o.title) + '</span>'
            + '<div class="flex gap-1">';
          // Move buttons
          var moves = {pending: ['picked'], picked: ['delivering'], delivering: ['done', 'failed']};
          var nextCols = moves[col.column.id] || [];
          for (var n = 0; n < nextCols.length; n++) {
            var colIcons = {picked: '📦', delivering: '🏍️', done: '✅', failed: '❌'};
            html += '<button class="btn btn-ghost btn-sm" onclick="SS.ConvOrderBoard.move(' + conversationId + ',' + o.id + ',\'' + nextCols[n] + '\')" style="font-size:10px">' + (colIcons[nextCols[n]] || '→') + '</button>';
          }
          html += '</div></div>';
          if (o.recipient) html += '<div class="text-xs text-muted">👤 ' + SS.utils.esc(o.recipient) + (o.cod ? ' · COD ' + SS.utils.formatMoney(o.cod) + 'd' : '') + '</div>';
          if (o.assignee_name) html += '<div class="text-xs text-muted">📤 ' + SS.utils.esc(o.assignee_name) + '</div>';
          html += '</div>';
        }
        html += '</div>';
      }
      SS.ui.sheet({title: '📋 Order Board', html: html});
    });
  },
  add: function(convId) {
    SS.ui.modal({title: 'Them don', html: '<input id="cob-title" class="form-input mb-2" placeholder="Tieu de don"><input id="cob-rec" class="form-input mb-2" placeholder="Nguoi nhan"><input id="cob-addr" class="form-input mb-2" placeholder="Dia chi"><input id="cob-cod" class="form-input" type="number" placeholder="COD (VND)">', confirmText: 'Them',
      onConfirm: function() { SS.api.post('/conv-order-board.php', {conversation_id: convId, title: document.getElementById('cob-title').value, recipient: document.getElementById('cob-rec').value, address: document.getElementById('cob-addr').value, cod: parseInt(document.getElementById('cob-cod').value) || 0}).then(function() { SS.ConvOrderBoard.show(convId); }); }
    });
  },
  move: function(convId, orderId, toCol) { SS.api.post('/conv-order-board.php?action=move', {conversation_id: convId, order_id: orderId, to_column: toCol}).then(function() { SS.ConvOrderBoard.show(convId); }); }
};
