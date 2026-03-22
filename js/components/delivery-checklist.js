window.SS = window.SS || {};
SS.DeliveryChecklist = {
  show: function(date) {
    date = date || new Date().toISOString().split('T')[0];
    SS.api.get('/delivery-checklist.php?date=' + date).then(function(d) {
      var data = d.data || {};
      var items = data.items || [];
      var cats = data.categories || {};
      var pct = data.progress || 0;
      var color = pct >= 80 ? 'var(--success)' : (pct >= 50 ? 'var(--warning)' : 'var(--danger)');
      var html = '<div class="text-center mb-3"><div class="font-bold text-lg" style="color:' + color + '">' + pct + '%</div>'
        + '<div style="height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + pct + '%;height:100%;background:' + color + ';border-radius:4px;transition:width .3s"></div></div>'
        + '<div class="text-xs text-muted mt-1">' + (data.checked || 0) + '/' + (data.total || 0) + (data.ready ? ' ✅ San sang!' : ' ⏳ Chua du') + '</div></div>';
      var currentCat = '';
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        if (it.category !== currentCat) { currentCat = it.category; html += '<div class="text-xs font-bold text-muted mt-3 mb-1">' + (cats[currentCat] || currentCat).toUpperCase() + '</div>'; }
        html += '<div class="flex items-center gap-2 p-2" style="cursor:pointer;border-radius:8px;' + (it.checked ? 'opacity:0.5' : '') + '" onclick="SS.DeliveryChecklist.toggle(\'' + date + '\',' + it.id + ')">'
          + '<div style="width:22px;height:22px;border-radius:6px;border:2px solid ' + (it.checked ? 'var(--success)' : 'var(--border)') + ';display:flex;align-items:center;justify-content:center;background:' + (it.checked ? 'var(--success)' : 'transparent') + '">' + (it.checked ? '<span style="color:#fff;font-size:12px">✓</span>' : '') + '</div>'
          + '<span class="text-sm" style="' + (it.checked ? 'text-decoration:line-through' : '') + '">' + it.icon + ' ' + SS.utils.esc(it.text) + '</span></div>';
      }
      SS.ui.sheet({title: '✅ Checklist giao hang', html: html});
    });
  },
  toggle: function(date, itemId) { SS.api.post('/delivery-checklist.php', {item_id: itemId}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.DeliveryChecklist.show(date); }); }
};
