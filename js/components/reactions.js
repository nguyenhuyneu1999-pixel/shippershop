/**
 * ShipperShop Component — Post Reactions
 * Facebook-style emoji reaction picker (long-press or hover)
 * Reactions: like, love, haha, wow, sad, angry
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.Reactions = {
  _emojis: {
    like: {emoji: '👍', label: 'Thành công', color: '#7C3AED'},
    love: {emoji: '❤️', label: 'Yêu thích', color: '#e74c3c'},
    haha: {emoji: '😂', label: 'Haha', color: '#f1c40f'},
    wow:  {emoji: '😮', label: 'Wow', color: '#f39c12'},
    sad:  {emoji: '😢', label: 'Buồn', color: '#f1c40f'},
    angry:{emoji: '😡', label: 'Phẫn nộ', color: '#e67e22'}
  },
  _popup: null,
  _timer: null,

  // Show reaction picker above a button
  show: function(postId, anchorEl) {
    SS.Reactions.close();
    var rect = anchorEl.getBoundingClientRect();

    var popup = document.createElement('div');
    popup.id = 'ss-reaction-picker';
    popup.style.cssText = 'position:fixed;z-index:3000;background:var(--card);border-radius:28px;box-shadow:0 4px 20px rgba(0,0,0,.15);padding:6px 8px;display:flex;gap:2px;left:' + Math.max(8, rect.left - 40) + 'px;bottom:' + (window.innerHeight - rect.top + 8) + 'px;animation:slideUp .2s';

    var keys = Object.keys(SS.Reactions._emojis);
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      var r = SS.Reactions._emojis[k];
      popup.innerHTML += '<button data-reaction="' + k + '" data-postid="' + postId + '" style="font-size:28px;padding:4px 6px;border:none;background:none;cursor:pointer;transition:transform .15s;border-radius:50%" title="' + r.label + '" onmouseenter="this.style.transform=\'scale(1.4) translateY(-4px)\'" onmouseleave="this.style.transform=\'scale(1)\'" onclick="SS.Reactions.react(' + postId + ',\'' + k + '\');SS.Reactions.close()">' + r.emoji + '</button>';
    }

    document.body.appendChild(popup);
    SS.Reactions._popup = popup;

    // Close on outside click
    setTimeout(function() {
      document.addEventListener('click', SS.Reactions._outsideClick);
    }, 0);
  },

  _outsideClick: function(e) {
    if (SS.Reactions._popup && !SS.Reactions._popup.contains(e.target)) {
      SS.Reactions.close();
    }
  },

  close: function() {
    if (SS.Reactions._popup) {
      SS.Reactions._popup.remove();
      SS.Reactions._popup = null;
      document.removeEventListener('click', SS.Reactions._outsideClick);
    }
  },

  // Send reaction
  react: function(postId, reaction) {
    SS.api.post('/reactions.php?action=react', {post_id: postId, reaction: reaction}).then(function(d) {
      var data = d.data || {};
      SS.Reactions._updateUI(postId, data.reacted ? reaction : null);
    }).catch(function() {});
  },

  // Quick like (tap without long-press)
  quickLike: function(postId, btn) {
    SS.api.post('/reactions.php?action=react', {post_id: postId, reaction: 'like'}).then(function(d) {
      var data = d.data || {};
      SS.Reactions._updateUI(postId, data.reacted ? (data.reaction || 'like') : null);
    }).catch(function() {});
  },

  // Update button UI after reaction
  _updateUI: function(postId, reaction) {
    // Find all reaction buttons for this post
    var btns = document.querySelectorAll('[data-react-post="' + postId + '"]');
    for (var i = 0; i < btns.length; i++) {
      var btn = btns[i];
      if (reaction) {
        var r = SS.Reactions._emojis[reaction];
        btn.innerHTML = (r ? r.emoji + ' ' : '') + (r ? r.label : 'Thành công');
        btn.style.color = r ? r.color : '';
        btn.classList.add('pa3-active');
      } else {
        btn.innerHTML = '<i class="fa-regular fa-thumbs-up"></i> Thành công';
        btn.style.color = '';
        btn.classList.remove('pa3-active');
      }
    }
  },

  // Render reaction summary (for display under posts)
  renderSummary: function(breakdown, total) {
    if (!breakdown || total <= 0) return '';
    var html = '<div style="display:flex;align-items:center;gap:2px">';
    var sorted = Object.keys(breakdown).sort(function(a, b) { return breakdown[b] - breakdown[a]; });
    var shown = 0;
    for (var i = 0; i < sorted.length && shown < 3; i++) {
      var k = sorted[i];
      if (breakdown[k] > 0 && SS.Reactions._emojis[k]) {
        html += '<span style="font-size:14px">' + SS.Reactions._emojis[k].emoji + '</span>';
        shown++;
      }
    }
    html += '<span class="text-sm text-muted" style="margin-left:4px">' + SS.utils.fN(total) + '</span></div>';
    return html;
  },

  // Attach long-press to like buttons
  attachLongPress: function(btn, postId) {
    var timer = null;
    btn.setAttribute('data-react-post', postId);
    btn.addEventListener('mousedown', function(e) {
      timer = setTimeout(function() { SS.Reactions.show(postId, btn); }, 500);
    });
    btn.addEventListener('mouseup', function() { clearTimeout(timer); });
    btn.addEventListener('mouseleave', function() { clearTimeout(timer); });
    btn.addEventListener('touchstart', function(e) {
      timer = setTimeout(function() { SS.Reactions.show(postId, btn); }, 500);
    }, {passive: true});
    btn.addEventListener('touchend', function() { clearTimeout(timer); });
  }
};
