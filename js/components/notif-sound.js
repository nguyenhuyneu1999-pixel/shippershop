/**
 * ShipperShop Component — Notification Sound
 * Plays subtle sound + vibrate for new notifications and messages
 * Uses Web Audio API — no external sound files needed
 */
window.SS = window.SS || {};

SS.NotifSound = {
  _audioCtx: null,
  _enabled: true,

  init: function() {
    // Check user preference
    var pref = localStorage.getItem('ss_notif_sound');
    SS.NotifSound._enabled = pref !== 'false';
  },

  toggle: function() {
    SS.NotifSound._enabled = !SS.NotifSound._enabled;
    localStorage.setItem('ss_notif_sound', SS.NotifSound._enabled ? 'true' : 'false');
    if (SS.NotifSound._enabled) SS.NotifSound.play('notification');
    return SS.NotifSound._enabled;
  },

  isEnabled: function() {
    return SS.NotifSound._enabled;
  },

  play: function(type) {
    if (!SS.NotifSound._enabled) return;
    type = type || 'notification';

    try {
      if (!SS.NotifSound._audioCtx) {
        SS.NotifSound._audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      }
      var ctx = SS.NotifSound._audioCtx;
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);

      if (type === 'notification') {
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.08, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.3);
      } else if (type === 'message') {
        osc.frequency.setValueAtTime(660, ctx.currentTime);
        osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08);
        osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.16);
        gain.gain.setValueAtTime(0.06, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.25);
      } else if (type === 'success') {
        osc.frequency.setValueAtTime(523, ctx.currentTime);
        osc.frequency.setValueAtTime(659, ctx.currentTime + 0.12);
        osc.frequency.setValueAtTime(784, ctx.currentTime + 0.24);
        gain.gain.setValueAtTime(0.07, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.4);
      }
    } catch(e) {}

    // Vibrate on mobile
    if (navigator.vibrate) {
      navigator.vibrate(type === 'message' ? [100, 50, 100] : [80]);
    }
  }
};

// Auto-init
SS.NotifSound.init();
