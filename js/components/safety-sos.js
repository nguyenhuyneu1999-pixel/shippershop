window.SS = window.SS || {};
SS.SafetySOS = {
  show: function() {
    SS.api.get('/safety-sos.php').then(function(d) {
      var types = (d.data || {}).types || [];
      var html = '<div class="text-center mb-3" style="color:var(--danger)"><div style="font-size:48px">🆘</div><div class="font-bold">SOS Khan Cap</div><div class="text-xs text-muted">Nhan de gui canh bao</div></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">';
      for (var i = 0; i < types.length; i++) {
        var t = types[i];
        var bgColor = t.severity === 'critical' ? '#dc262615' : (t.severity === 'high' ? '#f59e0b15' : 'var(--border-light)30');
        html += '<button class="card" style="padding:16px;text-align:center;cursor:pointer;border:none;background:' + bgColor + '" onclick="SS.SafetySOS.send(\'' + t.id + '\')"><div style="font-size:28px">' + t.icon + '</div><div class="text-xs font-bold mt-1">' + SS.utils.esc(t.name) + '</div></button>';
      }
      html += '</div><div class="flex gap-2 mt-3"><button class="btn btn-ghost btn-sm flex-1" onclick="SS.SafetySOS.contacts()">👥 Lien he</button><button class="btn btn-ghost btn-sm flex-1" onclick="SS.SafetySOS.history()">📋 Lich su</button></div>';
      SS.ui.sheet({title: '🆘 SOS Khan Cap', html: html});
    });
  },
  send: function(type) {
    if (!navigator.geolocation) { SS.api.post('/safety-sos.php', {type: type}).then(function(d) { SS.ui.toast(d.message, 'error'); }); return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
      SS.api.post('/safety-sos.php', {type: type, lat: pos.coords.latitude, lng: pos.coords.longitude}).then(function(d) { SS.ui.toast(d.message, 'error'); });
    }, function() { SS.api.post('/safety-sos.php', {type: type}).then(function(d) { SS.ui.toast(d.message, 'error'); }); });
  },
  contacts: function() { SS.api.get('/safety-sos.php?action=contacts').then(function(d) { var contacts = (d.data || {}).contacts || []; SS.ui.toast(contacts.length + ' lien he khan cap', 'info'); }); },
  history: function() { SS.api.get('/safety-sos.php?action=history').then(function(d) { SS.ui.toast((d.data || {}).count + ' su co da bao', 'info'); }); }
};
