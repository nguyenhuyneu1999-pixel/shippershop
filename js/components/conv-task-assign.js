window.SS = window.SS || {};
SS.ConvTaskAssign = {
  show: function(conversationId) {
    SS.api.get('/conv-task-assign.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var tasks = data.tasks || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.ConvTaskAssign.assign(' + conversationId + ')"><i class="fa-solid fa-user-plus"></i> Giao viec</button>';
      html += '<div class="flex gap-2 mb-2 text-xs"><span class="chip">⏳ ' + (data.pending || 0) + '</span><span class="chip" style="background:var(--success)15">✅ ' + (data.done || 0) + '</span></div>';
      var prioIcons = {urgent: '🔴', high: '🟠', normal: '🔵', low: '⚪'};
      for (var i = 0; i < tasks.length; i++) {
        var t = tasks[i];
        var isDone = t.status === 'done';
        html += '<div class="card mb-2" style="padding:10px' + (isDone ? ';opacity:0.6' : '') + '">'
          + '<div class="flex justify-between"><span class="text-sm ' + (isDone ? '' : 'font-bold') + '" style="' + (isDone ? 'text-decoration:line-through' : '') + '">' + (prioIcons[t.priority] || '🔵') + ' ' + SS.utils.esc(t.task) + '</span>'
          + (!isDone ? '<button class="btn btn-ghost btn-sm" onclick="SS.ConvTaskAssign.complete(' + conversationId + ',' + t.id + ')" style="font-size:10px">✅</button>' : '') + '</div>'
          + '<div class="text-xs text-muted">📤 ' + SS.utils.esc(t.assigner_name || '') + ' → 📥 ' + SS.utils.esc(t.assignee_name || 'Chua giao') + (t.deadline ? ' · ⏰ ' + SS.utils.esc(t.deadline) : '') + '</div></div>';
      }
      SS.ui.sheet({title: '📋 Cong viec (' + tasks.length + ')', html: html});
    });
  },
  assign: function(convId) {
    SS.ui.modal({title: 'Giao viec', html: '<input id="cta-task" class="form-input mb-2" placeholder="Cong viec"><input id="cta-to" class="form-input mb-2" type="number" placeholder="User ID nguoi nhan"><select id="cta-prio" class="form-select mb-2"><option value="normal">🔵 Binh thuong</option><option value="urgent">🔴 Gap</option><option value="high">🟠 Cao</option><option value="low">⚪ Thap</option></select><input id="cta-dl" class="form-input" type="datetime-local" placeholder="Deadline">', confirmText: 'Giao',
      onConfirm: function() { SS.api.post('/conv-task-assign.php', {conversation_id: convId, task: document.getElementById('cta-task').value, assigned_to: parseInt(document.getElementById('cta-to').value) || 0, priority: document.getElementById('cta-prio').value, deadline: document.getElementById('cta-dl').value}).then(function() { SS.ConvTaskAssign.show(convId); }); }
    });
  },
  complete: function(convId, taskId) { SS.api.post('/conv-task-assign.php?action=complete', {conversation_id: convId, task_id: taskId}).then(function() { SS.ui.toast('Hoan thanh!', 'success'); SS.ConvTaskAssign.show(convId); }); }
};
