/**
 * ShipperShop Component — Conversation Threads
 * Reply to specific messages (Slack-style threads)
 */
window.SS = window.SS || {};

SS.ConvThreads = {
  show: function(parentId) {
    SS.api.get('/conv-threads.php?parent_id=' + parentId).then(function(d) {
      var replies = (d.data || {}).replies || [];
      var html = '<div class="text-sm text-muted mb-2">' + replies.length + ' tra loi</div>';

      for (var i = 0; i < replies.length; i++) {
        var r = replies[i];
        html += '<div class="flex gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<img src="' + (r.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><div class="text-xs"><span class="font-medium">' + SS.utils.esc(r.fullname || '') + '</span> <span class="text-muted">' + SS.utils.ago(r.created_at) + '</span></div>'
          + '<div class="text-sm mt-1">' + SS.utils.esc(r.content) + '</div></div></div>';
      }

      // Reply input
      html += '<div class="flex gap-2 mt-3">'
        + '<input id="ct-reply" class="form-input flex-1" placeholder="Tra loi...">'
        + '<button class="btn btn-primary btn-sm" onclick="SS.ConvThreads._reply(' + parentId + ')"><i class="fa-solid fa-reply"></i></button></div>';

      SS.ui.sheet({title: 'Thread', html: html});
    });
  },

  _reply: function(parentId) {
    var input = document.getElementById('ct-reply');
    if (!input || !input.value.trim()) return;
    SS.api.post('/conv-threads.php', {parent_id: parentId, content: input.value.trim()}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success');
      SS.ConvThreads.show(parentId); // Refresh
    });
  },

  // Render thread indicator on message
  renderBtn: function(messageId, count) {
    if (!count) return '';
    return '<button class="btn btn-ghost btn-sm" style="font-size:11px" onclick="SS.ConvThreads.show(' + messageId + ')">💬 ' + count + ' tra loi</button>';
  }
};
