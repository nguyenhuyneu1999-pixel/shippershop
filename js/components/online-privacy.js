/**
 * ShipperShop Component — Online Privacy Settings
 * Toggle visibility of online status, last seen, typing, etc.
 */
window.SS = window.SS || {};

SS.OnlinePrivacy = {
  show: function() {
    SS.api.get('/online-privacy.php').then(function(d) {
      var p = d.data || {};
      var items = [
        {key: 'show_online', label: 'Hien trang thai online', desc: 'Nguoi khac thay ban dang online'},
        {key: 'show_last_seen', label: 'Hien lan cuoi truy cap', desc: 'Hien thoi gian hoat dong gan nhat'},
        {key: 'show_read_receipts', label: 'Xac nhan da doc', desc: 'Nguoi khac biet ban da doc tin nhan'},
        {key: 'show_typing', label: 'Hien dang nhap', desc: 'Nguoi khac thay ban dang go'},
        {key: 'private_account', label: 'Tai khoan rieng tu', desc: 'Yeu cau phe duyet khi theo doi'},
        {key: 'hide_from_search', label: 'An khoi tim kiem', desc: 'Khong xuat hien trong ket qua tim kiem'},
      ];
      var html = '';
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        var checked = p[it.key] ? 'checked' : '';
        html += '<div class="list-item"><div class="flex-1"><div class="font-medium text-sm">' + it.label + '</div><div class="text-xs text-muted">' + it.desc + '</div></div>'
          + '<label class="toggle"><input type="checkbox" ' + checked + ' onchange="SS.OnlinePrivacy._save(\'' + it.key + '\',this.checked)"><span class="toggle-slider"></span></label></div>';
      }
      html += '<style>.toggle{position:relative;width:44px;height:24px;display:inline-block}.toggle input{opacity:0;width:0;height:0}.toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:24px;cursor:pointer;transition:.3s}.toggle-slider:before{content:"";position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s}.toggle input:checked+.toggle-slider{background:var(--primary)}.toggle input:checked+.toggle-slider:before{transform:translateX(20px)}</style>';
      SS.ui.sheet({title: 'Quyen rieng tu', html: html});
    });
  },
  _save: function(key, val) {
    var data = {};
    data[key] = val;
    SS.api.post('/online-privacy.php', data).then(function() {
      SS.ui.toast('Da luu!', 'success', 1500);
    });
  }
};
