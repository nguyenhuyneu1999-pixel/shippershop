/**
 * ShipperShop Component — Conversation Checklist
 */
window.SS = window.SS || {};

SS.ConvChecklist = {
  show: function(conversationId) {
    SS.api.get('/conv-checklist.php?conversation_id=' + conversationId).then(function(d) {
      var data = d.data || {};
      var items = data.items || [];
      var html = '<div class="flex gap-2 mb-3"><input id="ccl-input" class="form-input flex-1" placeholder="Them muc moi..." onkeydown="if(event.key===\'Enter\')SS.ConvChecklist.add(' + conversationId + ')">'
        + '<button class="btn btn-primary btn-sm" onclick="SS.ConvChecklist.add(' + conversationId + ')"><i class="fa-solid fa-plus"></i></button></div>';
      html += '<div class="flex justify-between mb-2"><span class="text-xs text-muted">' + (data.completed || 0) + '/' + (data.total || 0) + ' hoan thanh</span>'
        + '<div style="width:60px;height:6px;background:var(--border-light);border-radius:3px"><div style="width:' + (data.progress || 0) + '%;height:100%;background:var(--success);border-radius:3px"></div></div></div>';
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<div style="width:22px;height:22px;border-radius:6px;border:2px solid ' + (it.done ? 'var(--success)' : 'var(--border)') + ';display:flex;align-items:center;justify-content:center;cursor:pointer;background:' + (it.done ? 'var(--success)' : 'transparent') + '" onclick="SS.ConvChecklist.toggle(' + conversationId + ',' + it.id + ')">' + (it.done ? '<span style="color:#fff;font-size:12px">✓</span>' : '') + '</div>'
          + '<span class="text-sm flex-1" style="' + (it.done ? 'text-decoration:line-through;opacity:0.5' : '') + '">' + SS.utils.esc(it.text) + '</span>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.ConvChecklist.del(' + conversationId + ',' + it.id + ')" style="font-size:10px"><i class="fa-solid fa-xmark text-muted"></i></button></div>';
      }
      if (data.completed > 0) html += '<button class="btn btn-ghost btn-sm mt-2" onclick="SS.ConvChecklist.clearDone(' + conversationId + ')">🗑 Xoa da hoan thanh</button>';
      SS.ui.sheet({title: '☑️ Checklist (' + (data.progress || 0) + '%)', html: html});
    });
  },
  add: function(convId) {
    var input = document.getElementById('ccl-input');
    if (!input || !input.value.trim()) return;
    SS.api.post('/conv-checklist.php', {conversation_id: convId, text: input.value.trim()}).then(function() { SS.ConvChecklist.show(convId); });
  },
  toggle: function(convId, itemId) { SS.api.post('/conv-checklist.php?action=toggle', {conversation_id: convId, item_id: itemId}).then(function() { SS.ConvChecklist.show(convId); }); },
  del: function(convId, itemId) { SS.api.post('/conv-checklist.php?action=delete', {conversation_id: convId, item_id: itemId}).then(function() { SS.ConvChecklist.show(convId); }); },
  clearDone: function(convId) { SS.api.post('/conv-checklist.php?action=clear_done', {conversation_id: convId}).then(function() { SS.ui.toast('Da xoa', 'success'); SS.ConvChecklist.show(convId); }); }
};
