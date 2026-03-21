/**
 * ShipperShop Component — Post Reactions
 * Emoji reaction picker (long-press/hover on like button), reactor list
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.Reactions = SS.Reactions || {
  _EMOJIS: {like:'👍',love:'❤️',fire:'🔥',wow:'😮',sad:'😢',angry:'😠'},
  _LABELS: {like:'Thành công',love:'Yêu thích',fire:'Nổi bật',wow:'Ngạc nhiên',sad:'Buồn',angry:'Tức giận'},

  // Show reaction picker above button
  showPicker: function(postId, btnEl) {
    // Remove existing picker
    var old = document.getElementById('ss-reaction-picker');
    if (old) old.remove();

    var picker = document.createElement('div');
    picker.id = 'ss-reaction-picker';
    picker.style.cssText = 'position:absolute;background:var(--card);border-radius:24px;box-shadow:var(--shadow-lg);padding:6px 10px;display:flex;gap:4px;z-index:500;animation:slideUp .2s';

    var emojis = SS.Reactions._EMOJIS;
    var labels = SS.Reactions._LABELS;
    var html = '';
    for (var key in emojis) {
      html += '<button data-reaction="' + key + '" onclick="SS.Reactions.react(' + postId + ',\'' + key + '\',this)" title="' + labels[key] + '" style="font-size:24px;background:none;border:none;cursor:pointer;padding:4px;border-radius:50%;transition:transform .15s" onmouseenter="this.style.transform=\'scale(1.4)\'" onmouseleave="this.style.transform=\'scale(1)\'">' + emojis[key] + '</button>';
    }
    picker.innerHTML = html;

    // Position above button
    var rect = btnEl.getBoundingClientRect();
    picker.style.bottom = (window.innerHeight - rect.top + 8) + 'px';
    picker.style.left = rect.left + 'px';
    picker.style.position = 'fixed';
    document.body.appendChild(picker);

    // Close on click outside
    setTimeout(function() {
      var handler = function(e) {
        if (!picker.contains(e.target) && e.target !== btnEl) {
          picker.remove();
          document.removeEventListener('click', handler);
        }
      };
      document.addEventListener('click', handler);
    }, 0);

    // Auto-close after 5s
    setTimeout(function() { if (picker.parentNode) picker.remove(); }, 5000);
  },

  // Send reaction
  react: function(postId, reaction, btnEl) {
    var picker = document.getElementById('ss-reaction-picker');
    if (picker) picker.remove();

    SS.api.post('/reactions.php?action=react', {post_id: postId, reaction: reaction}).then(function(d) {
      var data = d.data || {};
      // Update UI — find the post's like button
      var card = document.querySelector('[data-post-id="' + postId + '"]');
      if (!card) return;
      var likeBtn = card.querySelector('.pa3-btn:first-child, [onclick*="likePost"]');
      if (likeBtn) {
        var emoji = SS.Reactions._EMOJIS[data.reaction] || '';
        if (data.action === 'removed') {
          likeBtn.classList.remove('pa3-active');
          likeBtn.innerHTML = '<i class="far fa-thumbs-up"></i> Thành công';
        } else {
          likeBtn.classList.add('pa3-active');
          likeBtn.innerHTML = emoji + ' ' + (SS.Reactions._LABELS[data.reaction] || 'Thành công');
        }
      }
    });
  },

  // Show who reacted (modal)
  showReactors: function(postId) {
    SS.api.get('/reactions.php?action=reactors&post_id=' + postId).then(function(d) {
      var reactors = d.data || [];
      if (!reactors.length) { SS.ui.toast('Chưa có reaction', 'info'); return; }
      var html = '';
      for (var i = 0; i < reactors.length; i++) {
        var r = reactors[i];
        var emoji = SS.Reactions._EMOJIS[r.reaction] || '👍';
        html += '<a href="/user.html?id=' + r.id + '" class="list-item" style="text-decoration:none;color:var(--text)">'
          + '<img class="avatar avatar-sm" src="' + (r.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
          + '<div class="flex-1"><div class="list-title">' + SS.utils.esc(r.fullname) + '</div>'
          + '<div class="list-subtitle">' + SS.utils.esc(r.shipping_company || '') + '</div></div>'
          + '<span style="font-size:20px">' + emoji + '</span></a>';
      }
      SS.ui.sheet({title: 'Reactions (' + reactors.length + ')', html: html});
    });
  },

  // Render reaction summary (for post stats)
  renderSummary: function(postId, containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    SS.api.get('/reactions.php?action=get&post_id=' + postId).then(function(d) {
      var data = d.data || {};
      if (!data.total) { el.innerHTML = ''; return; }
      var html = '<span onclick="SS.Reactions.showReactors(' + postId + ')" style="cursor:pointer;display:inline-flex;align-items:center;gap:2px">';
      var emojis = SS.Reactions._EMOJIS;
      for (var key in data.counts) {
        if (data.counts[key] > 0) html += '<span style="font-size:14px">' + (emojis[key] || '') + '</span>';
      }
      html += '<span class="text-muted text-xs" style="margin-left:2px">' + SS.utils.fN(data.total) + '</span></span>';
      el.innerHTML = html;
    }).catch(function() {});
  },

  // Attach long-press to like buttons
  attachLongPress: function(containerSelector) {
    var btns = document.querySelectorAll(containerSelector || '.pa3-btn:first-child');
    for (var i = 0; i < btns.length; i++) {
      (function(btn) {
        var timer = null;
        var postId = btn.closest('[data-post-id]');
        if (!postId) return;
        postId = postId.getAttribute('data-post-id');

        btn.addEventListener('touchstart', function(e) {
          timer = setTimeout(function() { SS.Reactions.showPicker(parseInt(postId), btn); }, 500);
        });
        btn.addEventListener('touchend', function() { clearTimeout(timer); });
        btn.addEventListener('touchmove', function() { clearTimeout(timer); });
        // Desktop: hover for 1s
        btn.addEventListener('mouseenter', function() {
          timer = setTimeout(function() { SS.Reactions.showPicker(parseInt(postId), btn); }, 800);
        });
        btn.addEventListener('mouseleave', function() { clearTimeout(timer); });
      })(btns[i]);
    }
  }
};
