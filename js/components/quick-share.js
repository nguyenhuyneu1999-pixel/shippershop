/**
 * ShipperShop Component — Quick share via clipboard or native share API
 */
window.SS = window.SS || {};

SS.QuickShare = {
  share: function(text, url) {
    if (navigator.share) {
      navigator.share({text: text, url: url || window.location.href}).catch(function() {});
    } else {
      SS.utils.copyText(text + ' ' + (url || window.location.href));
      SS.ui.toast('Da copy!', 'success');
    }
  }
};
