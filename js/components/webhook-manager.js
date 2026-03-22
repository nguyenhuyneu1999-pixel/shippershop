window.SS = window.SS || {};
SS.WebhookManager = {
  show: function() {
    SS.api.get('/webhook-manager.php').then(function(d) {
      var data = d.data || {};
      var hooks = data.webhooks || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.WebhookManager.add()"><i class="fa-solid fa-plus"></i> Them webhook</button>';
      html += '<div class="text-xs text-muted mb-2">' + (data.active || 0) + '/' + (data.count || 0) + ' active</div>';
      for (var i = 0; i < hooks.length; i++) {
        var h = hooks[i];
        html += '<div class="card mb-2" style="padding:10px;opacity:' + (h.active ? '1' : '0.5') + '">'
          + '<div class="flex justify-between"><div class="flex-1"><div class="text-xs font-bold text-ellipsis" style="overflow:hidden;white-space:nowrap">' + (h.active ? '🟢' : '⚪') + ' ' + SS.utils.esc(h.url).substring(0, 40) + '</div>'
          + '<div class="flex gap-1 flex-wrap mt-1">' + (h.events || []).map(function(e) { return '<span class="chip" style="font-size:8px">' + SS.utils.esc(e) + '</span>'; }).join('') + '</div>'
          + '<div class="text-xs text-muted">✅ ' + (h.success_count || 0) + ' / ❌ ' + (h.fail_count || 0) + (h.last_triggered ? ' · Last: ' + SS.utils.ago(h.last_triggered) : '') + '</div></div>'
          + '<div class="flex gap-1"><button class="btn btn-ghost btn-sm" onclick="SS.WebhookManager.toggle(' + h.id + ')" style="font-size:10px">' + (h.active ? '⏸' : '▶') + '</button></div></div></div>';
      }
      SS.ui.sheet({title: '🔗 Webhook Manager', html: html});
    });
  },
  add: function() {
    SS.ui.modal({title: 'Them webhook', html: '<input id="wm-url" class="form-input mb-2" placeholder="URL endpoint"><div class="text-xs text-muted mb-2">Events: post.created, user.registered, payment.received</div><input id="wm-events" class="form-input" placeholder="Events (comma separated)">', confirmText: 'Them',
      onConfirm: function() { var events = document.getElementById('wm-events').value.split(',').map(function(e) { return e.trim(); }).filter(Boolean); SS.api.post('/webhook-manager.php', {url: document.getElementById('wm-url').value, events: events}).then(function() { SS.WebhookManager.show(); }); }
    });
  },
  toggle: function(id) { SS.api.post('/webhook-manager.php?action=toggle', {webhook_id: id}).then(function() { SS.WebhookManager.show(); }); }
};
