/**
 * ShipperShop Component — Copy Text Helper
 * One-click copy with visual feedback
 */
window.SS = window.SS || {};

SS.CopyText = {
  copy: function(text, successMsg) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function() {
        SS.ui.toast(successMsg || 'Da copy!', 'success', 1500);
      }).catch(function() {
        SS.CopyText._fallback(text, successMsg);
      });
    } else {
      SS.CopyText._fallback(text, successMsg);
    }
  },

  _fallback: function(text, successMsg) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;left:-9999px';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); SS.ui.toast(successMsg || 'Da copy!', 'success', 1500); }
    catch(e) { SS.ui.toast('Khong the copy', 'error'); }
    document.body.removeChild(ta);
  },

  // Copy with button render
  renderBtn: function(text, label) {
    return '<button class="btn btn-ghost btn-sm" onclick="SS.CopyText.copy(\'' + SS.utils.esc(text).replace(/'/g, '\\x27') + '\')"><i class="fa-solid fa-copy"></i> ' + (label || 'Copy') + '</button>';
  }
};
