window.SS = window.SS || {};
SS.AdminTableStats = {
  show: function(arg) {
    var url = '/admin-table-stats.php' + (arg ? (typeof arg === 'number' ? '?conversation_id=' + arg : '?days=' + arg) : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var html = '<pre style="font-size:11px;white-space:pre-wrap;max-height:300px;overflow:auto">' + JSON.stringify(data, null, 2).substring(0, 600) + '</pre>';
      SS.ui.sheet({title: 'AdminTableStats', html: html});
    });
  }
};
