/**
 * ShipperShop Component — Clipboard Utility
 * Copy text to clipboard with fallback, visual feedback
 */
window.SS = window.SS || {};

SS.Clipboard = {

  copy: function(text, successMsg) {
    if (!text) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function() {
        if (SS.ui) SS.ui.toast(successMsg || 'Đã sao chép!', 'success', 1500);
      }).catch(function() {
        SS.Clipboard._fallback(text, successMsg);
      });
    } else {
      SS.Clipboard._fallback(text, successMsg);
    }
  },

  _fallback: function(text, successMsg) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;left:-9999px;top:0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      if (SS.ui) SS.ui.toast(successMsg || 'Đã sao chép!', 'success', 1500);
    } catch(e) {
      if (SS.ui) SS.ui.toast('Không thể sao chép', 'error');
    }
    ta.remove();
  },

  // Copy post link
  postLink: function(postId) {
    SS.Clipboard.copy('https://shippershop.vn/post-detail.html?id=' + postId, 'Đã sao chép link bài viết!');
  },

  // Copy user link
  userLink: function(userId) {
    SS.Clipboard.copy('https://shippershop.vn/user.html?id=' + userId, 'Đã sao chép link hồ sơ!');
  },

  // Copy group link
  groupLink: function(groupId) {
    SS.Clipboard.copy('https://shippershop.vn/group.html?id=' + groupId, 'Đã sao chép link nhóm!');
  },

  // Copy referral link
  referralLink: function(code) {
    SS.Clipboard.copy('https://shippershop.vn/r/' + code, 'Đã sao chép link giới thiệu!');
  }
};
