/**
 * ShipperShop Component — Comment Sheet (Bottom Sheet)
 * Opens bottom sheet with post + nested comments
 */
window.SS = window.SS || {};

SS.CommentSheet = {

  _currentPostId: null,
  _replyTo: null,

  open: function(postId) {
    SS.CommentSheet._currentPostId = postId;
    SS.CommentSheet._replyTo = null;

    var bd = SS.ui.sheet({title: 'Ghi chú', maxHeight: '85vh'});
    bd.id = 'cs-body';
    bd.innerHTML = '<div id="cs-comments" style="min-height:100px"><div class="flex justify-center p-4"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%"></div></div></div>'
      + '<div id="cs-reply-bar" style="display:none;padding:8px 12px;background:var(--primary-50);font-size:12px;color:var(--primary)">'
      + 'Đang trả lời <b id="cs-reply-name"></b> <span onclick="SS.CommentSheet.cancelReply()" style="cursor:pointer;float:right">✕</span></div>'
      + '<div style="display:flex;gap:8px;padding:8px 0;align-items:flex-end;border-top:1px solid var(--border)">'
      + '<textarea id="cs-input" class="form-input" placeholder="Viết ghi chú..." rows="1" style="flex:1;resize:none;max-height:100px"></textarea>'
      + '<button class="btn btn-primary btn-sm" onclick="SS.CommentSheet.submit()">Gửi</button>'
      + '</div>';

    SS.CommentSheet.load(postId);

    // Auto resize textarea
    var inp = document.getElementById('cs-input');
    if (inp) {
      inp.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
      });
      inp.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          SS.CommentSheet.submit();
        }
      });
    }
  },

  close: function() {
    SS.ui.closeSheet();
    SS.CommentSheet._currentPostId = null;
    SS.CommentSheet._replyTo = null;
  },

  load: function(postId) {
    SS.api.get('/posts.php?action=comments&post_id=' + postId).then(function(d) {
      var comments = d.data || [];
      var container = document.getElementById('cs-comments');
      if (!container) return;

      if (!comments.length) {
        container.innerHTML = '<div class="empty-state p-4"><div class="text-muted text-sm">Chưa có ghi chú. Hãy là người đầu tiên!</div></div>';
        return;
      }

      // Build nested tree
      var map = {};
      var roots = [];
      for (var i = 0; i < comments.length; i++) {
        var c = comments[i];
        c.children = [];
        map[c.id] = c;
      }
      for (var j = 0; j < comments.length; j++) {
        var cm = comments[j];
        if (cm.parent_id && map[cm.parent_id]) {
          map[cm.parent_id].children.push(cm);
        } else {
          roots.push(cm);
        }
      }

      var html = '';
      for (var k = 0; k < roots.length; k++) {
        html += SS.CommentSheet._renderComment(roots[k], 0);
      }
      container.innerHTML = html;
    });
  },

  _renderComment: function(c, depth) {
    var u = SS.utils;
    var indent = Math.min(depth, 3) * 24;
    var likeActive = c.user_liked ? 'color:var(--primary);font-weight:600' : '';
    var likesText = (parseInt(c.likes_count) || 0) > 0 ? ' ' + u.fN(c.likes_count) : '';

    var html = '<div style="padding:8px 0 4px;margin-left:' + indent + 'px;border-bottom:1px solid var(--border-light)" id="cmt-' + c.id + '">'
      + '<div style="display:flex;gap:8px;align-items:flex-start">'
      + '<a href="/user.html?id=' + c.user_id + '"><img class="avatar avatar-sm" src="' + (c.user_avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy"></a>'
      + '<div style="flex:1;min-width:0">'
      + '<div style="background:var(--bg);padding:8px 12px;border-radius:12px">'
      + '<a href="/user.html?id=' + c.user_id + '" style="font-weight:600;font-size:13px;color:var(--text);text-decoration:none">' + u.esc(c.user_name || 'User') + '</a>'
      + '<div style="font-size:13px;line-height:1.5;white-space:pre-wrap;word-break:break-word">' + u.esc(c.content) + '</div>'
      + '</div>'
      + '<div style="display:flex;gap:12px;padding:4px 12px;font-size:11px;color:var(--text-muted)">'
      + '<span>' + u.ago(c.created_at) + '</span>'
      + '<span style="cursor:pointer;' + likeActive + '" onclick="SS.CommentSheet.like(' + c.id + ',this)">Thành công' + likesText + '</span>'
      + '<span style="cursor:pointer" onclick="SS.CommentSheet.reply(' + c.id + ',\'' + u.esc(c.user_name || 'User').replace(/'/g, '\\x27') + '\')">Trả lời</span>'
      + '</div></div></div></div>';

    if (c.children) {
      for (var i = 0; i < c.children.length; i++) {
        html += SS.CommentSheet._renderComment(c.children[i], depth + 1);
      }
    }
    return html;
  },

  submit: function() {
    var inp = document.getElementById('cs-input');
    if (!inp) return;
    var content = inp.value.trim();
    if (!content) return;
    var pid = SS.CommentSheet._currentPostId;
    if (!pid) return;

    var data = {post_id: pid, content: content};
    if (SS.CommentSheet._replyTo) data.parent_id = SS.CommentSheet._replyTo;

    inp.disabled = true;
    SS.api.post('/posts.php?action=comment', data).then(function() {
      inp.value = '';
      inp.disabled = false;
      inp.style.height = 'auto';
      SS.CommentSheet.cancelReply();
      SS.CommentSheet.load(pid);
      SS.ui.toast('Đã ghi chú!', 'success');
    }).catch(function() {
      inp.disabled = false;
    });
  },

  like: function(commentId, el) {
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

  reply: function(commentId, userName) {
    SS.CommentSheet._replyTo = commentId;
    var bar = document.getElementById('cs-reply-bar');
    var name = document.getElementById('cs-reply-name');
    if (bar) bar.style.display = 'block';
    if (name) name.textContent = userName;
    var inp = document.getElementById('cs-input');
    if (inp) inp.focus();
  },

  cancelReply: function() {
    SS.CommentSheet._replyTo = null;
    var bar = document.getElementById('cs-reply-bar');
    if (bar) bar.style.display = 'none';
  }
};
