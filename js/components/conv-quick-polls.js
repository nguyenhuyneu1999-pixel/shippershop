/**
 * ShipperShop Component — Conversation Quick Polls
 */
window.SS = window.SS || {};

SS.ConvQuickPolls = {
  show: function(conversationId) {
    SS.api.get('/conv-quick-polls.php?conversation_id=' + conversationId).then(function(d) {
      var polls = (d.data || {}).polls || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvQuickPolls.create(' + conversationId + ')"><i class="fa-solid fa-poll-h"></i> Tao khao sat</button>';
      if (!polls.length) html += '<div class="empty-state p-3"><div class="empty-icon">📊</div><div class="empty-text">Chua co khao sat</div></div>';
      for (var i = 0; i < polls.length; i++) {
        var p = polls[i];
        html += '<div class="card mb-2" style="padding:12px"><div class="font-bold text-sm mb-2">' + SS.utils.esc(p.question) + '</div>';
        var opts = p.options || [];
        for (var o = 0; o < opts.length; o++) {
          var opt = opts[o];
          var votes = (opt.voters || []).length;
          var pct = p.total_votes > 0 ? Math.round(votes / p.total_votes * 100) : 0;
          html += '<div style="margin-bottom:4px;cursor:pointer" onclick="SS.ConvQuickPolls.vote(' + conversationId + ',' + p.id + ',' + opt.id + ')">'
            + '<div class="flex justify-between text-xs mb-1"><span>' + SS.utils.esc(opt.text) + '</span><span class="font-bold">' + votes + ' (' + pct + '%)</span></div>'
            + '<div style="height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + pct + '%;height:100%;background:var(--primary);border-radius:4px;transition:width .3s"></div></div></div>';
        }
        html += '<div class="text-xs text-muted mt-1">' + SS.utils.esc(p.creator_name || '') + ' · ' + p.total_votes + ' phieu · ' + SS.utils.ago(p.created_at) + '</div></div>';
      }
      SS.ui.sheet({title: '📊 Khao sat (' + polls.length + ')', html: html});
    });
  },
  create: function(convId) {
    SS.ui.modal({title: 'Tao khao sat', html: '<input id="cqp-q" class="form-input mb-2" placeholder="Cau hoi"><input id="cqp-a" class="form-input mb-1" placeholder="Lua chon 1" value="Co"><input id="cqp-b" class="form-input mb-1" placeholder="Lua chon 2" value="Khong"><input id="cqp-c" class="form-input" placeholder="Lua chon 3 (tuy chon)">', confirmText: 'Tao',
      onConfirm: function() {
        var opts = [document.getElementById('cqp-a').value, document.getElementById('cqp-b').value, document.getElementById('cqp-c').value].filter(function(v) { return v.trim(); });
        SS.api.post('/conv-quick-polls.php', {conversation_id: convId, question: document.getElementById('cqp-q').value, options: opts}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvQuickPolls.show(convId); });
      }
    });
  },
  vote: function(convId, pollId, optId) {
    SS.api.post('/conv-quick-polls.php?action=vote', {conversation_id: convId, poll_id: pollId, option_id: optId}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvQuickPolls.show(convId); });
  }
};
