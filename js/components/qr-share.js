/**
 * ShipperShop Component — QR Share
 * Show QR code modal for sharing profiles, groups, posts
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.QRShare = {

  show: function(type, id, title) {
    // type: profile, group, post, invite
    SS.api.get('/qr.php?type=' + type + '&id=' + id + '&size=250').then(function(d) {
      var data = d.data || {};
      var html = '<div style="text-align:center;padding:16px 0">'
        + '<img src="' + SS.utils.esc(data.qr_image || '') + '" style="width:250px;height:250px;border-radius:12px;border:1px solid var(--border)" alt="QR Code">'
        + '<div class="font-bold mt-3">' + SS.utils.esc(title || 'ShipperShop') + '</div>'
        + '<div class="text-sm text-muted mt-1">' + SS.utils.esc(data.url || '') + '</div>'
        + '<div class="flex gap-2 justify-center mt-4">'
        + '<button class="btn btn-primary btn-sm" onclick="SS.utils.copyText(\'' + SS.utils.esc(data.url || '').replace(/'/g, '\\x27') + '\');SS.ui.toast(\'Đã copy link!\',\'success\')"><i class="fa-solid fa-copy"></i> Copy link</button>'
        + '<button class="btn btn-outline btn-sm" onclick="SS.QRShare._download(\'' + SS.utils.esc(data.qr_image || '').replace(/'/g, '\\x27') + '\')"><i class="fa-solid fa-download"></i> Tải QR</button>'
        + '</div></div>';
      SS.ui.modal({title: 'Chia sẻ QR', html: html, hideConfirm: true});
    }).catch(function() {
      SS.ui.toast('Lỗi tạo QR', 'error');
    });
  },

  _download: function(url) {
    var a = document.createElement('a');
    a.href = url;
    a.download = 'shippershop-qr.png';
    a.target = '_blank';
    a.click();
  },

  // Quick share buttons for profile page
  renderProfileShare: function(userId, userName) {
    return '<div class="flex gap-2">'
      + '<button class="btn btn-ghost btn-sm" onclick="SS.QRShare.show(\'profile\',' + userId + ',\'' + SS.utils.esc(userName || '').replace(/'/g, '\\x27') + '\')"><i class="fa-solid fa-qrcode"></i> QR</button>'
      + '<button class="btn btn-ghost btn-sm" onclick="SS.ShareSheet&&SS.ShareSheet.share({url:\'https://shippershop.vn/user.html?id=' + userId + '\',title:\'' + SS.utils.esc(userName || '').replace(/'/g, '\\x27') + '\'})"><i class="fa-solid fa-share-nodes"></i> Chia sẻ</button>'
      + '</div>';
  }
};
