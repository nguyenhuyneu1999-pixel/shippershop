window.SS = window.SS || {};
SS.AdminContentQuality = {
  show: function(arg) {
    var url = '/admin-content-quality.php' + (arg ? (typeof arg === 'number' ? '?conversation_id=' + arg + '&user_id=' + arg : '?text=' + encodeURIComponent(arg)) : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var html = '<pre style="font-size:11px;white-space:pre-wrap;max-height:300px;overflow:auto">' + JSON.stringify(data, null, 2).substring(0, 600) + '</pre>';
      SS.ui.sheet({title: 'AdminContentQuality', html: html});
    });
  }
};
