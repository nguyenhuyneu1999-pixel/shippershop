window.SS = window.SS || {};
SS.PlatformCentury = {
  show: function() {
    SS.api.get('/platform-century.php').then(function(d) {
      var data = d.data || {};
      var comm = data.community || {};
      var plat = data.platform || {};
      var rev = data.revenue || {};
      var html = '<div class="text-center mb-4" style="padding:16px;background:linear-gradient(135deg,#7c3aed,#a855f7);border-radius:12px;color:#fff"><div style="font-size:48px">🏆</div><div class="font-bold text-lg">SESSION 100</div><div style="font-size:12px;opacity:0.8">' + SS.utils.esc(data.version || '') + ' · ' + SS.utils.esc(data.milestone || '') + '</div></div>';
      html += '<div class="text-sm font-bold mb-2">👥 Cong dong</div><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:16px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (comm.users || 0) + '</div><div class="text-muted">Users</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (comm.posts || 0) + '</div><div class="text-muted">Posts</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (comm.comments || 0) + '</div><div class="text-muted">Comments</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (comm.likes || 0) + '</div><div class="text-muted">Likes</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (comm.groups || 0) + '</div><div class="text-muted">Groups</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (comm.follows || 0) + '</div><div class="text-muted">Follows</div></div></div>';
      html += '<div class="text-sm font-bold mb-2">⚙️ Platform</div><div class="flex gap-2 flex-wrap mb-3">'
        + '<span class="chip">' + (plat.apis || 0) + ' APIs</span>'
        + '<span class="chip">' + (plat.js_components || 0) + ' JS</span>'
        + '<span class="chip">' + (plat.tables || 0) + ' Tables</span>'
        + '<span class="chip">' + (plat.db_mb || 0) + ' MB</span></div>';
      html += '<div class="text-sm font-bold mb-2">💰 Revenue</div><div class="flex gap-2"><span class="chip">' + SS.utils.formatMoney(rev.total || 0) + 'd total</span><span class="chip">⭐ ' + (rev.subscribers || 0) + ' subs</span></div>';
      SS.ui.sheet({title: '🏆 Session 100', html: html});
    });
  }
};
