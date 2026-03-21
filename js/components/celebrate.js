/**
 * ShipperShop Component — Celebration
 * Confetti animation for milestones (first post, 100 likes, level up, etc.)
 * Pure CSS + JS, no libraries
 */
window.SS = window.SS || {};

SS.Celebrate = {

  // Show confetti burst
  confetti: function(opts) {
    opts = opts || {};
    var count = opts.count || 50;
    var duration = opts.duration || 2500;
    var colors = opts.colors || ['#7C3AED', '#EE4D2D', '#22c55e', '#3b82f6', '#f59e0b', '#ec4899', '#FBBF24'];

    var container = document.createElement('div');
    container.style.cssText = 'position:fixed;inset:0;z-index:99999;pointer-events:none;overflow:hidden';

    for (var i = 0; i < count; i++) {
      var p = document.createElement('div');
      var size = Math.random() * 8 + 4;
      var x = Math.random() * 100;
      var rotation = Math.random() * 360;
      var color = colors[Math.floor(Math.random() * colors.length)];
      var delay = Math.random() * 300;
      var shape = Math.random() > 0.5 ? '50%' : (Math.random() > 0.5 ? '0' : '2px');

      p.style.cssText = 'position:absolute;width:' + size + 'px;height:' + (size * (Math.random() * 0.5 + 0.5)) + 'px;background:' + color + ';left:' + x + '%;top:-10px;border-radius:' + shape + ';transform:rotate(' + rotation + 'deg);opacity:1;animation:confettiFall ' + (duration / 1000) + 's ease-out ' + (delay / 1000) + 's forwards';
      container.appendChild(p);
    }

    // Add keyframes
    if (!document.getElementById('ss-confetti-css')) {
      var style = document.createElement('style');
      style.id = 'ss-confetti-css';
      style.textContent = '@keyframes confettiFall{0%{transform:translateY(0) rotate(0deg);opacity:1}100%{transform:translateY(' + window.innerHeight + 'px) rotate(' + (360 + Math.random() * 720) + 'deg);opacity:0}}';
      document.head.appendChild(style);
    }

    document.body.appendChild(container);
    setTimeout(function() { container.remove(); }, duration + 500);
  },

  // Show celebration with message
  show: function(message, opts) {
    opts = opts || {};
    SS.Celebrate.confetti(opts);

    // Show message overlay
    if (message) {
      var ov = document.createElement('div');
      ov.style.cssText = 'position:fixed;inset:0;z-index:99998;display:flex;align-items:center;justify-content:center;pointer-events:none';
      ov.innerHTML = '<div style="text-align:center;animation:celebPop .4s ease-out"><div style="font-size:48px;margin-bottom:8px">' + (opts.emoji || '🎉') + '</div><div style="font-size:20px;font-weight:800;color:var(--text);text-shadow:0 2px 8px rgba(0,0,0,.1)">' + message + '</div></div>';

      if (!document.getElementById('ss-celeb-css')) {
        var style = document.createElement('style');
        style.id = 'ss-celeb-css';
        style.textContent = '@keyframes celebPop{0%{transform:scale(0);opacity:0}50%{transform:scale(1.2)}100%{transform:scale(1);opacity:1}}';
        document.head.appendChild(style);
      }

      document.body.appendChild(ov);
      setTimeout(function() { ov.style.opacity = '0'; ov.style.transition = 'opacity .5s'; }, 2000);
      setTimeout(function() { ov.remove(); }, 2500);
    }
  },

  // Check and trigger milestone celebrations
  checkMilestones: function(stats) {
    if (!stats) return;
    var milestones = [
      {key: 'first_post', check: stats.total_posts === 1, msg: 'Bài đăng đầu tiên!', emoji: '📝'},
      {key: 'ten_posts', check: stats.total_posts === 10, msg: '10 bài đăng!', emoji: '🔥'},
      {key: 'hundred_likes', check: stats.total_likes === 100, msg: '100 thành công!', emoji: '❤️'},
      {key: 'ten_followers', check: stats.total_followers === 10, msg: '10 người theo dõi!', emoji: '👥'},
      {key: 'verified', check: stats.is_verified === 1, msg: 'Đã xác minh!', emoji: '✓'},
    ];

    for (var i = 0; i < milestones.length; i++) {
      var m = milestones[i];
      if (m.check && !localStorage.getItem('ss_milestone_' + m.key)) {
        localStorage.setItem('ss_milestone_' + m.key, '1');
        setTimeout(function(msg, emoji) {
          return function() { SS.Celebrate.show(msg, {emoji: emoji}); };
        }(m.msg, m.emoji), 1000);
        break; // Only one at a time
      }
    }
  }
};
