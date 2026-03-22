window.SS = window.SS || {};
SS.PostBestTime = {
  show: function(arg) {
    SS.api.get('/post-best-time.php' + (arg ? '?conversation_id=' + arg : '')).then(function(d) {
      var data = d.data || {};
      var html = '<pre style="font-size:11px;white-space:pre-wrap">' + JSON.stringify(data, null, 2).substring(0, 500) + '</pre>';
      SS.ui.sheet({title: 'PostBestTime', html: html});
    });
  }
};
