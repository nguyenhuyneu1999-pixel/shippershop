/**
 * ShipperShop Page — Post Detail (post-detail.html)
 * Loads single post + comments using v2 API
 * Uses: SS.api, SS.PostCard, SS.CommentSheet, SS.ui
 */
window.SS = window.SS || {};

SS.PostDetail = {
  _postId: null,

  init: function(postId) {
    SS.PostDetail._postId = postId;
    if (!postId) return;

    var container = document.getElementById('pd-post');
    var cmtContainer = document.getElementById('pd-comments');

    if (container) container.innerHTML = SS.PostCard ? SS.PostCard.skeleton(1) : '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/posts.php?id=' + postId).then(function(d) {
      var post = d.data;
      if (!post) {
        if (container) container.innerHTML = '<div class="empty-state"><div class="empty-icon">😕</div><div class="empty-text">Bài viết không tồn tại hoặc đã bị xóa</div><a href="/" class="btn btn-primary mt-3">Về trang chủ</a></div>';
        return;
      }

      if (container && SS.PostCard) {
        container.innerHTML = SS.PostCard.render(post);
      }

      // Load comments
      SS.PostDetail.loadComments();

      // Update page title
      var title = (post.user_name || 'ShipperShop') + ' — ' + (post.content || '').substring(0, 60);
      document.title = title + ' | ShipperShop';

      // Update OG meta
      var ogTitle = document.querySelector('meta[property="og:title"]');
      if (ogTitle) ogTitle.content = title;
      var ogDesc = document.querySelector('meta[property="og:description"]');
      if (ogDesc) ogDesc.content = (post.content || '').substring(0, 150);
    }).catch(function() {
      if (container) container.innerHTML = '<div class="empty-state"><div class="empty-text">Lỗi tải bài viết</div><a href="/" class="btn btn-primary mt-3">Về trang chủ</a></div>';
    });
  },

  loadComments: function() {
    var pid = SS.PostDetail._postId;
    var container = document.getElementById('pd-comments');
    if (!container || !pid) return;

    container.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/posts.php?action=comments&post_id=' + pid).then(function(d) {
      var comments = d.data || [];
      if (!comments.length) {
        container.innerHTML = '<div class="p-4 text-center text-muted text-sm">Chưa có ghi chú. Hãy là người đầu tiên!</div>';
        return;
      }

      // Build nested tree
      var map = {};
      var roots = [];
      for (var i = 0; i < comments.length; i++) {
        comments[i].children = [];
        map[comments[i].id] = comments[i];
      }
      for (var j = 0; j < comments.length; j++) {
        if (comments[j].parent_id && map[comments[j].parent_id]) {
          map[comments[j].parent_id].children.push(comments[j]);
        } else {
          roots.push(comments[j]);
        }
      }

      var html = '<div style="padding:12px 16px;font-weight:600;color:var(--text-secondary)">' + comments.length + ' ghi chú</div>';
      for (var k = 0; k < roots.length; k++) {
        html += SS.PostDetail._renderComment(roots[k], 0);
      }
      container.innerHTML = html;
    }).catch(function() {
      container.innerHTML = '<div class="p-4 text-center text-muted text-sm">Lỗi tải ghi chú</div>';
    });
  },

  _renderComment: function(c, depth) {
    var u = SS.utils;
    var indent = Math.min(depth, 3) * 20;
    var likeActive = c.user_liked ? 'color:var(--primary);font-weight:600' : '';

    var html = '<div style="padding:8px 16px 4px;margin-left:' + indent + 'px" id="cmt-' + c.id + '">'
      + '<div style="display:flex;gap:8px;align-items:flex-start">'
      + '<a href="/user.html?id=' + c.user_id + '"><img class="avatar avatar-sm" src="' + (c.user_avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy"></a>'
      + '<div style="flex:1">'
      + '<div style="background:var(--bg);padding:8px 12px;border-radius:12px">'
      + '<a href="/user.html?id=' + c.user_id + '" style="font-weight:600;font-size:13px;color:var(--text);text-decoration:none">' + u.esc(c.user_name || 'User') + '</a>'
      + '<div style="font-size:14px;line-height:1.5;white-space:pre-wrap;word-break:break-word">' + u.esc(c.content) + '</div>'
      + '</div>'
      + '<div style="display:flex;gap:12px;padding:4px 12px;font-size:11px;color:var(--text-muted)">'
      + '<span>' + u.ago(c.created_at) + '</span>'
      + '<span style="cursor:pointer;' + likeActive + '" onclick="SS.PostDetail.likeComment(' + c.id + ',this)">Thành công' + (parseInt(c.likes_count) > 0 ? ' ' + u.fN(c.likes_count) : '') + '</span>'
      + '<span style="cursor:pointer" onclick="SS.PostDetail.replyTo(' + c.id + ')">Trả lời</span>'
      + '</div></div></div></div>';

    if (c.children) {
      for (var i = 0; i < c.children.length; i++) {
        html += SS.PostDetail._renderComment(c.children[i], depth + 1);
      }
    }
    return html;
  },

  likeComment: function(commentId, el) {
    SS.api.post('/posts.php?action=vote_comment', {comment_id: commentId}).then(function(d) {
      var data = d.data || {};
      if (el) {
        el.style.color = data.liked ? 'var(--primary)' : '';
        el.style.fontWeight = data.liked ? '600' : '';
        var txt = 'Thành công';
        if (data.likes_count > 0) txt += ' ' + SS.utils.fN(data.likes_count);
        el.textContent = txt;
      }
    });
  },

  replyTo: function(commentId) {
    // Open comment sheet with reply mode
    if (SS.CommentSheet) {
      SS.CommentSheet.open(SS.PostDetail._postId);
      setTimeout(function() {
        var cmt = document.getElementById('cmt-' + commentId);
        var name = cmt ? cmt.querySelector('a[style*="font-weight"]') : null;
        if (name) SS.CommentSheet.reply(commentId, name.textContent);
      }, 500);
    }
  },

  submitComment: function(content, parentId) {
    if (!content || !SS.PostDetail._postId) return;
    SS.api.post('/posts.php?action=comment', {
      post_id: SS.PostDetail._postId,
      content: content,
      parent_id: parentId || null
    }).then(function() {
      SS.ui.toast('Đã ghi chú!', 'success');
      SS.PostDetail.loadComments();
    });
  }
};
