/**
 * ShipperShop Component — Queued notification toast manager
 */
window.SS = window.SS || {};

SS.ToastQueue = {
  _queue: [],
  _showing: false,
  add: function(msg, type, duration) {
    SS.ToastQueue._queue.push({msg: msg, type: type || 'info', duration: duration || 3000});
    if (!SS.ToastQueue._showing) SS.ToastQueue._next();
  },
  _next: function() {
    if (!SS.ToastQueue._queue.length) { SS.ToastQueue._showing = false; return; }
    SS.ToastQueue._showing = true;
    var item = SS.ToastQueue._queue.shift();
    SS.ui.toast(item.msg, item.type, item.duration);
    setTimeout(SS.ToastQueue._next, item.duration + 300);
  }
};
