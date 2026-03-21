/**
 * ShipperShop Component — Stories
 * Horizontal story bar (Instagram-style circles) + fullscreen viewer
 * Uses: SS.api, SS.ui, SS.store
 */
window.SS = window.SS || {};

SS.Stories = {
  _data: [],
  _currentGroup: 0,
  _currentStory: 0,
  _timer: null,
  _viewerEl: null,

  // Render story bar (horizontal scrollable circles)
  renderBar: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.style.cssText = 'display:flex;gap:12px;padding:12px 16px;overflow-x:auto;-webkit-overflow-scrolling:touch;background:var(--card);margin-bottom:6px;scrollbar-width:none';
    el.innerHTML = '<div class="p-2 text-center text-muted text-sm">Đang tải...</div>';

    SS.api.get('/stories.php?action=feed').then(function(d) {
      SS.Stories._data = d.data || [];
      var groups = SS.Stories._data;

      var html = '';
      // Add "Create" button if logged in
      if (SS.store && SS.store.isLoggedIn()) {
        var user = SS.store.user();
        html += '<div style="text-align:center;flex-shrink:0;cursor:pointer;width:68px" onclick="SS.Stories.openCreate()">'
          + '<div style="position:relative;width:56px;height:56px;margin:0 auto">'
          + '<img src="' + SS.utils.esc(user.avatar || '/assets/img/defaults/avatar.svg') + '" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--border)" loading="lazy">'
          + '<div style="position:absolute;bottom:-2px;right:-2px;width:20px;height:20px;background:var(--primary);border-radius:50%;border:2px solid var(--card);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px">+</div>'
          + '</div><div style="font-size:10px;margin-top:4px;color:var(--text-muted)">Của bạn</div></div>';
      }

      if (!groups.length) {
        el.innerHTML = html || '<div class="p-2 text-center text-muted text-sm">Chưa có story</div>';
        return;
      }

      for (var i = 0; i < groups.length; i++) {
        var g = groups[i];
        var borderColor = g.has_unviewed ? 'var(--primary)' : 'var(--border)';
        html += '<div style="text-align:center;flex-shrink:0;cursor:pointer;width:68px" onclick="SS.Stories.open(' + i + ')">'
          + '<div style="width:56px;height:56px;border-radius:50%;padding:2px;background:' + borderColor + ';margin:0 auto">'
          + '<img src="' + SS.utils.esc(g.user_avatar || '/assets/img/defaults/avatar.svg') + '" style="width:100%;height:100%;border-radius:50%;object-fit:cover;border:2px solid var(--card)" loading="lazy">'
          + '</div><div style="font-size:10px;margin-top:4px;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + SS.utils.esc((g.user_name || '').split(' ').pop()) + '</div></div>';
      }
      el.innerHTML = html;
    }).catch(function() {
      el.innerHTML = '';
    });
  },

  // Open fullscreen viewer at group index
  open: function(groupIdx) {
    SS.Stories._currentGroup = groupIdx;
    SS.Stories._currentStory = 0;

    var ov = document.createElement('div');
    ov.id = 'ss-story-viewer';
    ov.style.cssText = 'position:fixed;inset:0;z-index:2000;background:#000;display:flex;align-items:center;justify-content:center';

    ov.innerHTML = '<div id="sv-progress" style="position:absolute;top:8px;left:8px;right:8px;display:flex;gap:3px;z-index:10"></div>'
      + '<div id="sv-header" style="position:absolute;top:24px;left:12px;right:12px;display:flex;align-items:center;gap:10px;z-index:10"></div>'
      + '<div id="sv-content" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;position:relative"></div>'
      + '<div style="position:absolute;top:0;left:0;width:30%;height:100%;z-index:5" onclick="SS.Stories.prev()"></div>'
      + '<div style="position:absolute;top:0;right:0;width:70%;height:100%;z-index:5" onclick="SS.Stories.next()"></div>'
      + '<button style="position:absolute;top:24px;right:12px;background:none;border:none;color:#fff;font-size:20px;z-index:20;cursor:pointer" onclick="SS.Stories.close()"><i class="fa-solid fa-xmark"></i></button>';

    document.body.appendChild(ov);
    document.body.style.overflow = 'hidden';
    SS.Stories._viewerEl = ov;

    SS.Stories._show();
    document.addEventListener('keydown', SS.Stories._onKey);
  },

  _show: function() {
    var groups = SS.Stories._data;
    if (SS.Stories._currentGroup >= groups.length) { SS.Stories.close(); return; }
    var group = groups[SS.Stories._currentGroup];
    var stories = group.stories || [];
    if (SS.Stories._currentStory >= stories.length) {
      // Next group
      SS.Stories._currentGroup++;
      SS.Stories._currentStory = 0;
      SS.Stories._show();
      return;
    }

    var story = stories[SS.Stories._currentStory];

    // Progress bars
    var progEl = document.getElementById('sv-progress');
    if (progEl) {
      var progHtml = '';
      for (var i = 0; i < stories.length; i++) {
        var fill = i < SS.Stories._currentStory ? '100%' : (i === SS.Stories._currentStory ? '0%' : '0%');
        progHtml += '<div style="flex:1;height:2px;background:rgba(255,255,255,.3);border-radius:1px;overflow:hidden"><div id="sv-bar-' + i + '" style="height:100%;background:#fff;width:' + fill + ';transition:width 5s linear"></div></div>';
      }
      progEl.innerHTML = progHtml;
      // Animate current bar
      setTimeout(function() {
        var bar = document.getElementById('sv-bar-' + SS.Stories._currentStory);
        if (bar) bar.style.width = '100%';
      }, 50);
    }

    // Header (user info + time)
    var headerEl = document.getElementById('sv-header');
    if (headerEl) {
      headerEl.innerHTML = '<img src="' + SS.utils.esc(group.user_avatar || '/assets/img/defaults/avatar.svg') + '" style="width:32px;height:32px;border-radius:50%;object-fit:cover">'
        + '<div style="flex:1"><div style="color:#fff;font-weight:600;font-size:13px">' + SS.utils.esc(group.user_name || '') + '</div>'
        + '<div style="color:rgba(255,255,255,.6);font-size:11px">' + SS.utils.ago(story.created_at) + '</div></div>';
    }

    // Content
    var contentEl = document.getElementById('sv-content');
    if (contentEl) {
      if (story.image_url) {
        contentEl.innerHTML = '<img src="' + SS.utils.esc(story.image_url) + '" style="max-width:100%;max-height:100%;object-fit:contain">';
      } else if (story.video_url) {
        contentEl.innerHTML = '<video src="' + SS.utils.esc(story.video_url) + '" autoplay playsinline muted style="max-width:100%;max-height:100%"></video>';
      } else {
        contentEl.style.background = story.background || '#7C3AED';
        contentEl.innerHTML = '<div style="padding:40px 24px;text-align:center;color:#fff;font-size:' + (story.font_size || 18) + 'px;line-height:1.6;font-weight:600;max-width:400px">' + SS.utils.esc(story.content || '') + '</div>';
      }
    }

    // Mark as viewed
    if (SS.store && SS.store.isLoggedIn()) {
      SS.api.post('/stories.php?action=view', {story_id: story.id}).catch(function() {});
    }

    // Auto-advance after 5s
    clearTimeout(SS.Stories._timer);
    SS.Stories._timer = setTimeout(function() { SS.Stories.next(); }, 5000);
  },

  next: function() {
    clearTimeout(SS.Stories._timer);
    var group = SS.Stories._data[SS.Stories._currentGroup];
    if (!group) { SS.Stories.close(); return; }
    SS.Stories._currentStory++;
    SS.Stories._show();
  },

  prev: function() {
    clearTimeout(SS.Stories._timer);
    if (SS.Stories._currentStory > 0) {
      SS.Stories._currentStory--;
    } else if (SS.Stories._currentGroup > 0) {
      SS.Stories._currentGroup--;
      var prevGroup = SS.Stories._data[SS.Stories._currentGroup];
      SS.Stories._currentStory = prevGroup ? prevGroup.stories.length - 1 : 0;
    }
    SS.Stories._show();
  },

  close: function() {
    clearTimeout(SS.Stories._timer);
    if (SS.Stories._viewerEl) {
      document.body.removeChild(SS.Stories._viewerEl);
      SS.Stories._viewerEl = null;
      document.body.style.overflow = '';
      document.removeEventListener('keydown', SS.Stories._onKey);
    }
  },

  _onKey: function(e) {
    if (e.key === 'Escape') SS.Stories.close();
    if (e.key === 'ArrowRight') SS.Stories.next();
    if (e.key === 'ArrowLeft') SS.Stories.prev();
  },

  // Create story modal
  openCreate: function() {
    if (!SS.store || !SS.store.isLoggedIn()) { window.location.href = '/login.html'; return; }

    var bgs = ['#7C3AED','#EE4D2D','#22c55e','#3b82f6','#f59e0b','#ec4899','#1a1a2e','#000000',
      'linear-gradient(135deg,#7C3AED,#EE4D2D)','linear-gradient(135deg,#22c55e,#3b82f6)','linear-gradient(135deg,#f59e0b,#ec4899)'];

    var bgHtml = '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px">';
    for (var i = 0; i < bgs.length; i++) {
      bgHtml += '<div onclick="document.getElementById(\'sc-bg\').value=\'' + bgs[i] + '\';this.parentNode.querySelectorAll(\'div\').forEach(function(d){d.style.border=\'2px solid transparent\'});this.style.border=\'2px solid var(--primary)\'" style="width:32px;height:32px;border-radius:8px;background:' + bgs[i] + ';cursor:pointer;border:2px solid ' + (i === 0 ? 'var(--primary)' : 'transparent') + '"></div>';
    }
    bgHtml += '</div>';

    SS.ui.modal({
      title: 'Tạo Story',
      html: '<textarea id="sc-text" class="form-textarea" rows="4" placeholder="Viết gì đó... (hoặc để trống nếu đăng ảnh)" style="font-size:16px"></textarea>'
        + '<div class="form-group mt-3"><label class="form-label">Màu nền</label>' + bgHtml + '<input type="hidden" id="sc-bg" value="#7C3AED"></div>'
        + '<div class="form-group"><label class="form-label">Ảnh (tùy chọn)</label><input type="file" id="sc-img" accept="image/*" class="form-input"></div>'
        + '<div class="text-sm text-muted mt-2">Story sẽ hết hạn sau 24 giờ</div>',
      confirmText: 'Đăng Story',
      onConfirm: function() {
        var text = document.getElementById('sc-text').value.trim();
        var bg = document.getElementById('sc-bg').value;
        var imgInput = document.getElementById('sc-img');
        // For now, text-only stories (image upload needs FormData)
        if (!text && (!imgInput || !imgInput.files || !imgInput.files[0])) {
          SS.ui.toast('Nhập nội dung hoặc chọn ảnh', 'warning');
          return;
        }
        SS.api.post('/stories.php?action=create', {
          content: text,
          background: bg,
          hours: 24
        }).then(function(d) {
          SS.ui.toast('Đã đăng story!', 'success');
          // Refresh story bar
          SS.Stories.renderBar('ss-story-bar');
        });
      }
    });
  }
};
