/**
 * ShipperShop Component — Admin Export UI
 * Download users, posts, transactions as CSV/JSON
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.AdminExport = {

  open: function() {
    SS.api.get('/admin-export.php?action=overview').then(function(d) {
      var data = d.data || {};
      var html = '<div class="card mb-3"><div class="card-body">'
        + '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;text-align:center">'
        + '<div><div style="font-size:20px;font-weight:800;color:var(--primary)">' + SS.utils.fN(data.total_users || 0) + '</div><div class="text-xs text-muted">Users</div></div>'
        + '<div><div style="font-size:20px;font-weight:800;color:var(--info)">' + SS.utils.fN(data.total_posts || 0) + '</div><div class="text-xs text-muted">Posts</div></div>'
        + '<div><div style="font-size:20px;font-weight:800;color:var(--success)">' + SS.utils.formatMoney(data.total_revenue || 0) + '</div><div class="text-xs text-muted">Revenue</div></div>'
        + '<div><div style="font-size:20px;font-weight:800;color:var(--warning)">' + SS.utils.fN(data.monthly_active || 0) + '</div><div class="text-xs text-muted">MAU</div></div>'
        + '</div></div></div>'
        + '<div class="text-sm font-bold mb-2">Xuất dữ liệu</div>'
        + '<div class="list-item" onclick="SS.AdminExport._download(\'users\',\'csv\')" style="cursor:pointer"><i class="fa-solid fa-users" style="color:var(--primary);width:24px"></i><div class="flex-1">Users (CSV)</div><i class="fa-solid fa-download text-muted"></i></div>'
        + '<div class="list-item" onclick="SS.AdminExport._download(\'posts\',\'csv\')" style="cursor:pointer"><i class="fa-solid fa-newspaper" style="color:var(--info);width:24px"></i><div class="flex-1">Posts (CSV)</div><i class="fa-solid fa-download text-muted"></i></div>'
        + '<div class="list-item" onclick="SS.AdminExport._download(\'transactions\',\'csv\')" style="cursor:pointer"><i class="fa-solid fa-money-bill-wave" style="color:var(--success);width:24px"></i><div class="flex-1">Transactions (CSV)</div><i class="fa-solid fa-download text-muted"></i></div>'
        + '<div class="divider"></div>'
        + '<div class="list-item" onclick="SS.AdminExport._download(\'users\',\'json\')" style="cursor:pointer"><i class="fa-solid fa-code" style="color:var(--text-muted);width:24px"></i><div class="flex-1">Users (JSON)</div><i class="fa-solid fa-download text-muted"></i></div>'
        + '<div class="list-item" onclick="SS.AdminExport._download(\'posts\',\'json\')" style="cursor:pointer"><i class="fa-solid fa-code" style="color:var(--text-muted);width:24px"></i><div class="flex-1">Posts (JSON)</div><i class="fa-solid fa-download text-muted"></i></div>';

      SS.ui.sheet({title: 'Xuất dữ liệu', html: html});
    });
  },

  _download: function(type, format) {
    var url = '/api/v2/admin-export.php?action=' + type + '&format=' + format;
    if (format === 'csv') {
      // Direct download
      var a = document.createElement('a');
      a.href = url;
      a.download = type + '_' + new Date().toISOString().split('T')[0] + '.' + format;
      var tk = localStorage.getItem('token');
      // For CSV, append token as query param (workaround for download)
      a.href = url + '&token=' + encodeURIComponent(tk || '');
      a.click();
      SS.ui.toast('Đang tải ' + type + '...', 'info');
    } else {
      // JSON — fetch and download blob
      SS.api.get('/admin-export.php?action=' + type + '&format=json').then(function(d) {
        var json = JSON.stringify(d.data, null, 2);
        var blob = new Blob([json], {type: 'application/json'});
        var url2 = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url2;
        a.download = type + '_' + new Date().toISOString().split('T')[0] + '.json';
        a.click();
        URL.revokeObjectURL(url2);
        SS.ui.toast('Đã tải ' + type + '!', 'success');
      });
    }
  }
};
