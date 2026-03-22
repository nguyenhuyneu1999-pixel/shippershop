/**
 * ShipperShop Component — User Ratings
 * Rate shippers on 5 categories with stars
 */
window.SS = window.SS || {};

SS.UserRatings = {
  show: function(userId) {
    SS.api.get('/user-ratings.php?user_id=' + userId).then(function(d) {
      var data = d.data || {};
      var cats = data.categories || [];
      var byCat = data.by_category || {};

      // Overall star
      var overall = data.overall || 0;
      var stars = '';
      for (var s = 1; s <= 5; s++) stars += '<span style="color:' + (s <= Math.round(overall) ? '#fbbf24' : '#e5e7eb') + ';font-size:20px">★</span>';

      var html = '<div class="text-center mb-3"><div>' + stars + '</div><div class="font-bold text-lg">' + overall + '/5</div><div class="text-xs text-muted">' + (data.total_reviews || 0) + ' danh gia</div></div>';

      // Per category bars
      for (var i = 0; i < cats.length; i++) {
        var c = cats[i];
        var avg = byCat[c.id] || 0;
        var w = Math.round(avg / 5 * 100);
        html += '<div class="flex items-center gap-2 mb-2"><span class="text-xs" style="width:70px">' + c.icon + ' ' + SS.utils.esc(c.name) + '</span>'
          + '<div style="flex:1;height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + w + '%;height:100%;background:#fbbf24;border-radius:4px"></div></div>'
          + '<span class="text-xs font-bold" style="width:24px;text-align:right">' + avg + '</span></div>';
      }

      // Rate button
      html += '<div class="text-center mt-3"><button class="btn btn-primary btn-sm" onclick="SS.UserRatings.rate(' + userId + ')"><i class="fa-solid fa-star"></i> Danh gia</button></div>';
      SS.ui.sheet({title: 'Danh gia Shipper', html: html});
    });
  },

  rate: function(userId) {
    SS.ui.closeSheet();
    var cats = [{id:'speed',name:'Toc do'},{id:'communication',name:'Giao tiep'},{id:'reliability',name:'Tin cay'},{id:'attitude',name:'Thai do'},{id:'packaging',name:'Dong goi'}];
    var html = '';
    for (var i = 0; i < cats.length; i++) {
      html += '<div class="flex justify-between items-center mb-2"><span class="text-sm">' + cats[i].name + '</span>'
        + '<select id="ur-' + cats[i].id + '" class="form-select" style="width:80px"><option value="5">5 ⭐</option><option value="4">4 ⭐</option><option value="3" selected>3 ⭐</option><option value="2">2 ⭐</option><option value="1">1 ⭐</option></select></div>';
    }
    html += '<textarea id="ur-comment" class="form-textarea mt-2" rows="2" placeholder="Nhan xet (tuy chon)"></textarea>';

    SS.ui.modal({
      title: 'Danh gia Shipper',
      html: html,
      confirmText: 'Gui danh gia',
      onConfirm: function() {
        var scores = {};
        for (var j = 0; j < cats.length; j++) scores[cats[j].id] = parseInt(document.getElementById('ur-' + cats[j].id).value) || 3;
        SS.api.post('/user-ratings.php', {user_id: userId, scores: scores, comment: document.getElementById('ur-comment').value}).then(function(d) {
          SS.ui.toast(d.message || 'OK', 'success');
        });
      }
    });
  }
};
