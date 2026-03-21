/**
 * ShipperShop Component — Short Link
 * Create and copy short links for sharing
 */
window.SS = window.SS || {};

SS.ShortLink = {
  create: function(type, id, onDone) {
    SS.api.post('/short-link.php', {type: type, id: id}).then(function(d) {
      var data = d.data || {};
      SS.utils.copyText(data.short_url || '');
      SS.ui.toast('Da copy link ngan!', 'success');
      if (onDone) onDone(data);
    }).catch(function() { SS.ui.toast('Loi tao link', 'error'); });
  },

  // Share button with short link
  renderBtn: function(type, id) {
    return '<button class="btn btn-ghost btn-sm" onclick="SS.ShortLink.create(\'' + type + '\',' + id + ')"><i class="fa-solid fa-link"></i> Link ngan</button>';
  }
};
