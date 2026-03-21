/**
 * ShipperShop Component — Post Card
 * Renders post cards for feed, profile, groups
 * KHÔNG template literal. KHÔNG arrow functions.
 */
window.SS = window.SS || {};

SS.PostCard = {

  // Company badge colors
  _shipColors: {
    'GHTK':'#00b14f','J&T':'#d32f2f','GHN':'#ff6600','Viettel Post':'#e21a1a',
    'BEST':'#ffc107','Ninja Van':'#c41230','SPX':'#EE4D2D','Ahamove':'#f5a623',
    'Grab Express':'#00b14f','Be':'#5bc500','Gojek':'#00aa13'
  },

  render: function(p) {
    var u = SS.utils || {};
    var uid = SS.store ? SS.store.userId() : 0;
    var isOwner = uid && parseInt(p.user_id) === uid;
    var isAdmin = SS.store && SS.store.isAdmin();

    // Ship badge
    var ship = p.shipping_company || '';
    var shipColor = SS.PostCard._shipColors[ship] || '#7C3AED';
    var shipBadge = ship ? '<span style="color:' + shipColor + ';font-weight:600;font-size:11px">' + u.esc(ship) + '</span>' : '';

    // Subscription badge
    var subBadge = '';
    if (p.subscription_badge) subBadge = ' <span class="badge badge-vip">' + u.esc(p.subscription_badge) + '</span>';

    // Location
    var loc = '';
    if (p.district) loc = ' · <span style="color:#3b82f6;font-size:11px">📍 ' + u.esc(p.district) + '</span>';

    // Type badge
    var typeBadge = '';
    if (p.type && p.type !== 'post') {
      var typeMap = {'question':'❓ Hỏi đáp','review':'⭐ Đánh giá','news':'📢 Tin tức','tip':'💡 Mẹo hay'};
      typeBadge = ' <span class="badge badge-primary" style="font-size:10px">' + (typeMap[p.type] || p.type) + '</span>';
    }

    // Edited
    var edited = p.edited_at ? ' · <span style="font-size:10px;color:var(--text-muted)">đã sửa</span>' : '';

    // Pinned
    var pinned = p.is_pinned == 1 ? '<div style="padding:4px 12px;background:var(--primary-50);color:var(--primary);font-size:12px;font-weight:500"><i class="fa-solid fa-thumbtack"></i> Bài ghim</div>' : '';

    // Content with "Xem thêm"
    var content = u.esc(p.content || '');
    var showMore = '';
    if (content.length > 300) {
      showMore = '<span class="text-primary cursor-pointer" onclick="this.parentNode.querySelector(\'.pc-full\').style.display=\'block\';this.parentNode.querySelector(\'.pc-short\').style.display=\'none\'"> Xem thêm</span>';
      content = '<span class="pc-short">' + content.substring(0, 300) + '...' + showMore + '</span><span class="pc-full" style="display:none">' + u.esc(p.content) + '</span>';
    }

    // Images
    var imgs = '';
    var imageList = p.images;
    if (typeof imageList === 'string') {
      try { imageList = JSON.parse(imageList); } catch(e) { imageList = imageList ? [imageList] : []; }
    }
    if (imageList && imageList.length) {
      if (imageList.length === 1) {
        imgs = '<div class="post-images" style="display:block;width:100%"><img src="' + imageList[0] + '" loading="lazy" style="width:100%;display:block;cursor:pointer" onclick="SS.ImageViewer&&SS.ImageViewer.open(this.src,' + JSON.stringify(imageList).replace(/"/g, '\\x22') + ')"></div>';
      } else {
        imgs = '<div class="post-images" style="display:grid;grid-template-columns:1fr 1fr;gap:2px">';
        for (var i = 0; i < Math.min(imageList.length, 4); i++) {
          var overlay = (i === 3 && imageList.length > 4) ? '<div style="position:absolute;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;font-weight:700">+' + (imageList.length - 4) + '</div>' : '';
          imgs += '<div style="position:relative;aspect-ratio:1;overflow:hidden"><img src="' + imageList[i] + '" loading="lazy" style="width:100%;height:100%;object-fit:cover;cursor:pointer" onclick="SS.ImageViewer&&SS.ImageViewer.open(this.src,' + JSON.stringify(imageList).replace(/"/g, '\\x22') + ')">' + overlay + '</div>';
        }
        imgs += '</div>';
      }
    }

    // Video
    var video = '';
    if (p.video_url) {
      video = '<div style="width:100%"><video src="' + p.video_url + '" preload="metadata" playsinline muted style="width:100%;display:block;max-height:500px" onclick="this.paused?this.play():this.pause()"></video></div>';
    }

    // Stats
    var likeCount = parseInt(p.likes_count) || 0;
    var cmtCount = parseInt(p.comments_count) || 0;
    var shareCount = parseInt(p.shares_count) || 0;

    // Active states
    var likeActive = p.user_liked ? ' pa3-active' : '';
    var saveActive = p.user_saved ? ' pa3-active' : '';

    // 3-dot menu items
    var menuItems = '';
    if (isOwner) {
      menuItems += '<div class="dropdown-item" onclick="SS.PostCard.edit(' + p.id + ')"><i class="fa-solid fa-pen"></i> Sửa bài</div>';
      menuItems += '<div class="dropdown-item danger" onclick="SS.PostCard.del(' + p.id + ')"><i class="fa-solid fa-trash"></i> Xóa bài</div>';
    }
    menuItems += '<div class="dropdown-item" onclick="SS.PostCard.save(' + p.id + ',this)"><i class="fa-solid fa-bookmark"></i> ' + (p.user_saved ? 'Bỏ lưu' : 'Lưu bài') + '</div>';
    if (!isOwner) {
      menuItems += '<div class="dropdown-item danger" onclick="SS.PostCard.report(' + p.id + ')"><i class="fa-solid fa-flag"></i> Báo cáo</div>';
    }
    if (isAdmin && !p.is_pinned) {
      menuItems += '<div class="dropdown-item" onclick="SS.PostCard.pin(' + p.id + ')"><i class="fa-solid fa-thumbtack"></i> Ghim bài</div>';
    }

    var html = '<div class="post-card card mb-3" id="pc-' + p.id + '">'
      + pinned
      + '<div class="post-body" style="padding:0;width:100%">'
      + '<div class="post-meta" style="padding:8px 12px 4px;display:flex;gap:10px;align-items:flex-start">'
      + '<a href="/user.html?id=' + p.user_id + '"><img class="avatar" src="' + (p.user_avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy"></a>'
      + '<div style="flex:1;min-width:0">'
      + '<div class="flex items-center justify-between">'
      + '<a href="/user.html?id=' + p.user_id + '" style="font-weight:600;font-size:14px;color:var(--text);text-decoration:none">' + u.esc(p.user_name || 'User') + '</a>' + subBadge
      + '<div style="position:relative"><button class="btn btn-icon btn-ghost btn-sm" onclick="SS.PostCard._menu(this,' + p.id + ')"><i class="fa-solid fa-ellipsis"></i></button></div>'
      + '</div>'
      + '<div style="font-size:11px;color:var(--text-muted)">' + shipBadge + (ship ? ' · ' : '') + u.ago(p.created_at) + loc + typeBadge + edited + '</div>'
      + '</div></div>'
      + '<div class="post-content" style="padding:4px 12px 8px;font-size:14px;line-height:1.6;white-space:pre-wrap;word-break:break-word">' + content + '</div>'
      + imgs + video
      + '</div>'
      + '<div class="pa3-stats" style="display:flex;padding:4px 12px;font-size:12px;color:var(--text-muted)">'
      + '<span style="flex:1;text-align:center">' + u.fN(likeCount) + ' đơn giao thành công</span>'
      + '<span style="flex:1;text-align:center">' + u.fN(cmtCount) + ' ghi chú</span>'
      + '<span style="flex:1;text-align:center">' + u.fN(shareCount) + ' đơn chuyển tiếp</span>'
      + '</div>'
      + '<div class="post-actions-3" style="display:flex;border-top:1px solid var(--border)">'
      + '<button class="pa3-btn' + likeActive + '" style="flex:1;padding:8px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:500;display:flex;align-items:center;justify-content:center;gap:5px;color:var(--text-secondary)" onclick="SS.PostCard.like(' + p.id + ',this)"><i class="fa-solid fa-check-circle"></i> Thành công</button>'
      + '<button class="pa3-btn" style="flex:1;padding:8px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:500;display:flex;align-items:center;justify-content:center;gap:5px;color:var(--text-secondary)" onclick="SS.CommentSheet&&SS.CommentSheet.open(' + p.id + ')"><i class="fa-regular fa-comment"></i> Ghi chú</button>'
      + '<button class="pa3-btn" style="flex:1;padding:8px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:500;display:flex;align-items:center;justify-content:center;gap:5px;color:var(--text-secondary)" onclick="SS.PostCard.share(' + p.id + ')"><i class="fa-solid fa-share"></i> Chuyển tiếp</button>'
      + '</div></div>';

    return html;
  },

  renderFeed: function(posts) {
    if (!posts || !posts.length) return '<div class="empty-state"><div class="empty-icon">📭</div><div class="empty-text">Chưa có bài viết nào</div></div>';
    var html = '';
    for (var i = 0; i < posts.length; i++) {
      html += SS.PostCard.render(posts[i]);
    }
    return html;
  },

  skeleton: function(count) {
    count = count || 3;
    var html = '';
    for (var i = 0; i < count; i++) {
      html += '<div class="card mb-3" style="padding:16px">'
        + '<div class="flex gap-3 mb-3"><div class="skeleton skeleton-avatar"></div><div style="flex:1"><div class="skeleton skeleton-text" style="width:40%"></div><div class="skeleton skeleton-text" style="width:25%"></div></div></div>'
        + '<div class="skeleton skeleton-text"></div><div class="skeleton skeleton-text" style="width:70%"></div>'
        + '<div class="skeleton skeleton-image" style="margin-top:12px;height:180px;border-radius:0"></div>'
        + '</div>';
    }
    return html;
  },

  // ========== ACTIONS ==========

  _menu: function(btn, pid) {
    var card = document.getElementById('pc-' + pid);
    if (!card) return;
    var p = card._postData || {};
    var uid = SS.store ? SS.store.userId() : 0;
    var isOwner = uid && parseInt(p.user_id || 0) === uid;
    var items = [];
    if (isOwner) {
      items.push({icon: 'fa-pen', label: ' Sửa bài', onClick: function() { SS.PostCard.edit(pid); }});
      items.push({icon: 'fa-trash', label: ' Xóa bài', onClick: function() { SS.PostCard.del(pid); }, danger: true});
    }
    items.push({icon: 'fa-bookmark', label: ' Lưu bài', onClick: function() { SS.PostCard.save(pid); }});
    if (!isOwner) items.push({icon: 'fa-flag', label: ' Báo cáo', onClick: function() { SS.PostCard.report(pid); }, danger: true});
    SS.ui.dropdown(btn.parentNode, items);
  },

  like: function(pid, btn) {
    SS.api.post('/posts.php?action=vote', {post_id: pid}).then(function(d) {
      var data = d.data || {};
      var card = document.getElementById('pc-' + pid);
      if (!card) return;
      var stats = card.querySelectorAll('.pa3-stats span');
      if (stats[0]) stats[0].textContent = SS.utils.fN(data.score || 0) + ' đơn giao thành công';
      if (btn) {
        if (data.user_vote) {
          btn.classList.add('pa3-active');
          btn.style.color = '#7C3AED';
          btn.style.background = '#EDE9FE';
        } else {
          btn.classList.remove('pa3-active');
          btn.style.color = '';
          btn.style.background = '';
        }
      }
    });
  },

  share: function(pid) {
    if (navigator.share) {
      navigator.share({title: 'ShipperShop', url: 'https://shippershop.vn/post-detail.html?id=' + pid});
    } else {
      SS.utils.copyText('https://shippershop.vn/post-detail.html?id=' + pid);
    }
    SS.api.post('/posts.php?action=share', {post_id: pid});
  },

  save: function(pid) {
    SS.api.post('/posts.php?action=save', {post_id: pid}).then(function(d) {
      var saved = d.data && d.data.saved;
      SS.ui.toast(saved ? 'Đã lưu bài' : 'Đã bỏ lưu', 'success');
    });
  },

  report: function(pid) {
    var reasons = [
      {value:'spam', label:'Spam'},
      {value:'inappropriate', label:'Nội dung không phù hợp'},
      {value:'harassment', label:'Quấy rối'},
      {value:'misinformation', label:'Thông tin sai'},
      {value:'other', label:'Khác'}
    ];
    var html = '<div style="display:flex;flex-direction:column;gap:8px">';
    for (var i = 0; i < reasons.length; i++) {
      html += '<button class="btn btn-ghost w-full" style="justify-content:flex-start" onclick="SS.PostCard._submitReport(' + pid + ',\'' + reasons[i].value + '\')">' + reasons[i].label + '</button>';
    }
    html += '</div>';
    SS.ui.sheet({title: 'Báo cáo bài viết', html: html});
  },

  _submitReport: function(pid, reason) {
    SS.ui.closeSheet();
    SS.api.post('/posts.php?action=report', {post_id: pid, reason: reason}).then(function() {
      SS.ui.toast('Đã gửi báo cáo. Cảm ơn bạn!', 'success');
    });
  },

  edit: function(pid) {
    var card = document.getElementById('pc-' + pid);
    var contentEl = card ? card.querySelector('.post-content') : null;
    var currentText = contentEl ? contentEl.textContent.trim() : '';
    SS.ui.modal({
      title: 'Sửa bài viết',
      html: '<textarea class="form-textarea" id="edit-content" rows="6" style="width:100%">' + SS.utils.esc(currentText) + '</textarea>',
      confirmText: 'Lưu',
      onConfirm: function() {
        var newContent = document.getElementById('edit-content').value.trim();
        if (newContent.length < 3) { SS.ui.toast('Tối thiểu 3 ký tự', 'error'); return; }
        SS.api.post('/posts.php?action=edit', {post_id: pid, content: newContent}).then(function() {
          SS.ui.toast('Đã sửa!', 'success');
          if (contentEl) contentEl.textContent = newContent;
        });
      }
    });
  },

  del: function(pid) {
    SS.ui.confirm('Xóa bài viết này?', function() {
      SS.api.post('/posts.php?action=delete', {post_id: pid}).then(function() {
        var card = document.getElementById('pc-' + pid);
        if (card) card.style.display = 'none';
        SS.ui.toast('Đã xóa', 'success');
      });
    }, {danger: true, confirmText: 'Xóa'});
  },

  pin: function(pid) {
    SS.api.post('/posts.php?action=pin', {post_id: pid}).then(function() {
      SS.ui.toast('Đã ghim/bỏ ghim', 'success');
    });
  }
};
