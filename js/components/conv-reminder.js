window.SS = window.SS || {};
SS.ConvReminder = {
  show: function(arg) {
    var url = '/conv-reminder.php' + (arg ? (typeof arg === 'number' ? '?conversation_id=' + arg : '?days=' + arg) : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var html = '<pre style="font-size:11px;white-space:pre-wrap;max-height:300px;overflow:auto">' + JSON.stringify(data, null, 2).substring(0, 600) + '</pre>';
      SS.ui.sheet({title: 'ConvReminder', html: html});
    });
  }
};
