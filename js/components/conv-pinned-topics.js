/**
 * ShipperShop Component — Conversation Pinned Topics
 */
window.SS = window.SS || {};

SS.ConvPinnedTopics = {
  show: function(conversationId) {
    SS.api.get('/conv-pinned-topics.php?conversation_id=' + conversationId).then(function(d) {
      var topics = (d.data || {}).topics || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvPinnedTopics.pin(' + conversationId + ')"><i class="fa-solid fa-thumbtack"></i> Ghim chu de</button>';
      if (!topics.length) html += '<div class="empty-state p-3"><div class="empty-icon">📌</div><div class="empty-text">Chua co chu de ghim</div></div>';
      for (var i = 0; i < topics.length; i++) {
        var t = topics[i];
        html += '<div class="card mb-2" style="padding:10px;border-left:3px solid var(--primary)"><div class="flex justify-between"><span class="font-bold text-sm">📌 ' + SS.utils.esc(t.title) + '</span>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.ConvPinnedTopics.unpin(' + conversationId + ',' + t.id + ')"><i class="fa-solid fa-xmark text-muted" style="font-size:10px"></i></button></div>'
          + (t.description ? '<div class="text-xs text-muted mt-1">' + SS.utils.esc(t.description) + '</div>' : '')
          + '<div class="text-xs text-muted mt-1">Boi ' + SS.utils.esc(t.pinner_name || '') + ' · ' + SS.utils.ago(t.pinned_at) + '</div></div>';
      }
      SS.ui.sheet({title: '📌 Chu de ghim (' + topics.length + '/10)', html: html});
    });
  },
  pin: function(convId) {
    SS.ui.modal({title: 'Ghim chu de', html: '<input id="cpt-title" class="form-input mb-2" placeholder="Tieu de chu de"><textarea id="cpt-desc" class="form-textarea" rows="2" placeholder="Mo ta (tuy chon)"></textarea>', confirmText: 'Ghim',
      onConfirm: function() {
        SS.api.post('/conv-pinned-topics.php', {conversation_id: convId, title: document.getElementById('cpt-title').value, description: document.getElementById('cpt-desc').value}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvPinnedTopics.show(convId); });
      }
    });
  },
  unpin: function(convId, topicId) { SS.api.post('/conv-pinned-topics.php?action=unpin', {conversation_id: convId, topic_id: topicId}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ConvPinnedTopics.show(convId); }); }
};
