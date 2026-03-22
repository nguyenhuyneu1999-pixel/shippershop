/**
 * ShipperShop Component — Conversation Reactions Summary
 */
window.SS = window.SS || {};

SS.ConvReactionsSummary = {
  show: function(conversationId) {
    SS.api.get('/conv-reactions-summary.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var emojiCounts = data.emoji_counts || {};
      var topReactors = data.top_reactors || [];

      var html = '<div class="text-center mb-3"><div style="font-size:28px;font-weight:800;color:var(--primary)">' + (data.total_reactions || 0) + '</div><div class="text-xs text-muted">Tong phan hoi · ' + (data.messages_with_reactions || 0) + ' tin nhan</div></div>';

      // Emoji breakdown
      var emojis = Object.keys(emojiCounts);
      if (emojis.length) {
        html += '<div class="flex gap-3 justify-center flex-wrap mb-3">';
        for (var i = 0; i < emojis.length; i++) {
          html += '<div class="text-center"><div style="font-size:24px">' + emojis[i] + '</div><div class="text-xs font-bold">' + emojiCounts[emojis[i]] + '</div></div>';
        }
        html += '</div>';
      }

      // Top reactors
      if (topReactors.length) {
        html += '<div class="text-sm font-bold mb-2">Nguoi phan hoi nhieu nhat</div>';
        for (var j = 0; j < topReactors.length; j++) {
          var r = topReactors[j];
          html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
            + '<img src="' + (r.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
            + '<span class="text-sm flex-1">' + SS.utils.esc(r.fullname) + '</span>'
            + '<span class="text-xs font-bold" style="color:var(--primary)">' + r.count + '</span></div>';
        }
      }

      if (!data.total_reactions) html = '<div class="empty-state p-4"><div class="empty-icon">😊</div><div class="empty-text">Chua co phan hoi</div></div>';
      SS.ui.sheet({title: 'Phan hoi cuoc tro chuyen', html: html});
    });
  }
};
