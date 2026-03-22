window.SS = window.SS || {};
SS.AudienceInsights = {
  show: function() {
    SS.api.get('/audience-insights.php').then(function(d) {
      var data = d.data || {};
      var ratio = data.follower_ratio || {};
      var html = '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:10px;flex:1"><div class="font-bold" style="color:var(--primary)">' + (ratio.followers || 0) + '</div><div class="text-xs text-muted">Followers</div></div>'
        + '<div class="card" style="padding:10px;flex:1"><div class="font-bold">' + (ratio.non_followers || 0) + '</div><div class="text-xs text-muted">Non-followers</div></div>'
        + '<div class="card" style="padding:10px;flex:1"><div class="font-bold">' + (ratio.total || 0) + '</div><div class="text-xs text-muted">Tong</div></div></div>';
      var likers = data.top_likers || [];
      if (likers.length) {
        html += '<div class="text-sm font-bold mb-2">Top nguoi thich</div>';
        for (var i = 0; i < Math.min(likers.length, 5); i++) {
          var l = likers[i];
          html += '<div class="flex items-center gap-2 p-1" style="border-bottom:1px solid var(--border-light)"><img src="' + (l.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy"><span class="text-sm flex-1">' + SS.utils.esc(l.fullname) + '</span><span class="text-xs font-bold" style="color:var(--danger)">❤️' + l.likes + '</span></div>';
        }
      }
      var companies = data.by_company || [];
      if (companies.length) {
        html += '<div class="text-sm font-bold mb-2 mt-3">Theo hang</div><div class="flex gap-2 flex-wrap">';
        for (var c = 0; c < companies.length; c++) html += '<span class="chip">' + SS.utils.esc(companies[c].company) + ' (' + companies[c].users + ')</span>';
        html += '</div>';
      }
      SS.ui.sheet({title: '👥 Audience Insights', html: html});
    });
  }
};
