/**
 * ShipperShop Component — Conversation Polls
 * Create and vote in conversation polls
 */
window.SS = window.SS || {};

SS.ConvPolls = {
  show: function(conversationId) {
    SS.api.get('/conv-polls.php?conversation_id=' + conversationId).then(function(d) {
      var poll = d.data;
      if (!poll || !poll.question) {
        SS.ui.sheet({title: 'Binh chon', html: '<div class="empty-state p-3"><div class="empty-icon">📊</div><div class="empty-text">Chua co binh chon</div><button class="btn btn-primary btn-sm mt-2" onclick="SS.ConvPolls.create(' + conversationId + ')">Tao binh chon</button></div>'});
        return;
      }

      var html = '<div class="font-bold text-lg mb-3">' + SS.utils.esc(poll.question) + '</div>';
      var options = poll.options || [];
      var votes = poll.votes || [];
      var total = poll.total_votes || 0;
      var myVote = poll.my_vote;

      // Count per option
      var counts = {};
      for (var v = 0; v < votes.length; v++) { var o = votes[v].option; counts[o] = (counts[o] || 0) + 1; }

      for (var i = 0; i < options.length; i++) {
        var count = counts[i] || 0;
        var pct = total > 0 ? Math.round(count / total * 100) : 0;
        var isMyVote = myVote === i;
        var bg = isMyVote ? 'var(--primary)' : 'var(--border)';
        html += '<div style="margin-bottom:8px;cursor:pointer" onclick="SS.ConvPolls.vote(' + conversationId + ',' + i + ')">'
          + '<div class="flex justify-between text-sm mb-1"><span>' + (isMyVote ? '✅ ' : '') + SS.utils.esc(options[i]) + '</span><span class="font-bold">' + pct + '%</span></div>'
          + '<div style="height:24px;background:var(--border-light);border-radius:6px;overflow:hidden">'
          + '<div style="width:' + pct + '%;height:100%;background:' + bg + ';border-radius:6px;transition:width .5s"></div></div>'
          + '<div class="text-xs text-muted">' + count + ' phieu</div></div>';
      }

      html += '<div class="text-xs text-muted text-center mt-2">' + total + ' nguoi da binh chon</div>';
      if (!poll.active) html += '<div class="text-center text-xs" style="color:var(--danger)">Binh chon da dong</div>';

      SS.ui.sheet({title: '📊 Binh chon', html: html});
    });
  },

  create: function(conversationId) {
    SS.ui.closeSheet();
    SS.ui.modal({
      title: 'Tao binh chon',
      html: '<input id="cp-q" class="form-input mb-2" placeholder="Cau hoi...">'
        + '<input id="cp-o1" class="form-input mb-1" placeholder="Lua chon 1">'
        + '<input id="cp-o2" class="form-input mb-1" placeholder="Lua chon 2">'
        + '<input id="cp-o3" class="form-input mb-1" placeholder="Lua chon 3 (tuy chon)">'
        + '<input id="cp-o4" class="form-input" placeholder="Lua chon 4 (tuy chon)">',
      confirmText: 'Tao',
      onConfirm: function() {
        var options = [document.getElementById('cp-o1').value, document.getElementById('cp-o2').value, document.getElementById('cp-o3').value, document.getElementById('cp-o4').value].filter(function(o) { return o.trim(); });
        SS.api.post('/conv-polls.php', {
          conversation_id: conversationId,
          question: document.getElementById('cp-q').value,
          options: options
        }).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); });
      }
    });
  },

  vote: function(conversationId, option) {
    SS.api.post('/conv-polls.php?action=vote', {conversation_id: conversationId, option: option}).then(function(d) {
      SS.ui.toast('Da binh chon!', 'success', 1500);
      SS.ConvPolls.show(conversationId);
    });
  }
};
