/**
 * ShipperShop Component — Post Collaboration
 * Invite collaborators, manage shared drafts
 */
window.SS = window.SS || {};

SS.PostCollab = {
  show: function() {
    SS.api.get('/post-collab.php').then(function(d) {
      var collabs = (d.data || {}).collaborations || [];
      if (!collabs.length) {
        SS.ui.sheet({title: 'Cong tac', html: '<div class="empty-state p-4"><div class="empty-icon">🤝</div><div class="empty-text">Chua co loi moi cong tac</div></div>'});
        return;
      }
      var html = '';
      for (var i = 0; i < collabs.length; i++) {
        var c = collabs[i];
        var statusColors = {pending: 'var(--warning)', accepted: 'var(--success)', declined: 'var(--danger)'};
        var statusLabels = {pending: 'Cho duyet', accepted: 'Da chap nhan', declined: 'Da tu choi'};
        html += '<div class="card mb-2" style="padding:10px">'
          + '<div class="flex justify-between"><span class="text-sm">Bai #' + c.post_id + ' · ' + SS.utils.esc(c.role) + '</span>'
          + '<span class="text-xs" style="color:' + (statusColors[c.status] || '#999') + '">' + (statusLabels[c.status] || c.status) + '</span></div>'
          + '<div class="text-xs text-muted">' + SS.utils.ago(c.created_at) + '</div>';
        if (c.status === 'pending') {
          html += '<div class="flex gap-2 mt-2"><button class="btn btn-primary btn-sm" onclick="SS.PostCollab._respond(' + c.post_id + ',\'accept\')">Chap nhan</button>'
            + '<button class="btn btn-ghost btn-sm" onclick="SS.PostCollab._respond(' + c.post_id + ',\'decline\')">Tu choi</button></div>';
        }
        html += '</div>';
      }
      SS.ui.sheet({title: 'Cong tac (' + collabs.length + ')', html: html});
    });
  },

  invite: function(postId) {
    SS.ui.modal({
      title: 'Moi cong tac',
      html: '<input id="pc-uid" class="form-input mb-2" type="number" placeholder="ID nguoi dung">'
        + '<select id="pc-role" class="form-select"><option value="editor">Bien tap</option><option value="reviewer">Duyet bai</option></select>',
      confirmText: 'Moi',
      onConfirm: function() {
        SS.api.post('/post-collab.php', {
          post_id: postId,
          user_id: parseInt(document.getElementById('pc-uid').value),
          role: document.getElementById('pc-role').value
        }).then(function(d) { SS.ui.toast(d.message || 'OK', 'success'); });
      }
    });
  },

  _respond: function(postId, action) {
    SS.api.post('/post-collab.php?action=' + action, {post_id: postId}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success');
      SS.PostCollab.show();
    });
  }
};
