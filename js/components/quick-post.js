/**
 * ShipperShop Component — Quick Post
 * Lightweight inline posting from feed — no full modal needed
 * Uses: SS.api, SS.ui, SS.DraftSave
 */
window.SS = window.SS || {};

SS.QuickPost = {

  render: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !SS.store || !SS.store.isLoggedIn()) return;

    var user = SS.store.getUser();
    var avatar = (user && user.avatar) ? user.avatar : '/assets/img/defaults/avatar.svg';

    el.innerHTML = '<div class="card mb-3"><div class="card-body" style="padding:12px 16px">'
      + '<div class="flex items-center gap-3">'
      + '<img src="' + avatar + '" class="avatar avatar-sm" loading="lazy">'
      + '<div class="flex-1" onclick="SS.QuickPost._expand(\'' + containerId + '\')" style="background:var(--bg);border-radius:20px;padding:10px 16px;cursor:pointer;font-size:14px;color:var(--text-muted)">Bạn đang nghĩ gì?</div>'
      + '</div>'
      + '<div id="' + containerId + '-expand" style="display:none;margin-top:12px">'
      + '<textarea id="' + containerId + '-ta" class="form-input" rows="3" placeholder="Chia sẻ với cộng đồng shipper..." style="border:none;background:var(--bg);border-radius:12px;resize:none;font-size:14px"></textarea>'
      + '<div class="flex items-center justify-between mt-2">'
      + '<div class="flex gap-2">'
      + '<button class="btn btn-ghost btn-xs" onclick="SS.PostTemplates&&SS.PostTemplates.open(function(c){document.getElementById(\'' + containerId + '-ta\').value=c})" title="Mẫu"><i class="fa-solid fa-file-lines"></i></button>'
      + '<button class="btn btn-ghost btn-xs" onclick="SS.Poll&&SS.Poll.create()" title="Poll"><i class="fa-solid fa-square-poll-vertical"></i></button>'
      + '</div>'
      + '<div class="flex gap-2">'
      + '<button class="btn btn-ghost btn-sm" onclick="SS.QuickPost._collapse(\'' + containerId + '\')">Hủy</button>'
      + '<button class="btn btn-primary btn-sm" onclick="SS.QuickPost._submit(\'' + containerId + '\')"><i class="fa-solid fa-paper-plane"></i> Đăng</button>'
      + '</div></div></div>'
      + '</div></div>';

    // Attach draft auto-save
    if (SS.DraftSave) {
      setTimeout(function() { SS.DraftSave.attach(containerId + '-ta', 'draft_quick_post'); }, 100);
    }
  },

  _expand: function(id) {
    var expand = document.getElementById(id + '-expand');
    if (expand) {
      expand.style.display = 'block';
      var ta = document.getElementById(id + '-ta');
      if (ta) ta.focus();
    }
  },

  _collapse: function(id) {
    var expand = document.getElementById(id + '-expand');
    if (expand) expand.style.display = 'none';
  },

  _submit: function(id) {
    var ta = document.getElementById(id + '-ta');
    if (!ta) return;
    var content = ta.value.trim();
    if (!content) { SS.ui.toast('Nhập nội dung', 'warning'); return; }

    SS.api.post('/posts.php', {content: content}).then(function(d) {
      SS.ui.toast('Đã đăng bài!', 'success');
      ta.value = '';
      SS.QuickPost._collapse(id);
      if (SS.DraftSave) SS.DraftSave.clear('draft_quick_post');
      if (SS.NotifSound) SS.NotifSound.play('success');
      if (SS.Celebrate) SS.Celebrate.confetti({count: 20, duration: 1500});
      // Reload feed
      if (window.loadFeed) window.loadFeed();
      else setTimeout(function() { location.reload(); }, 500);
    }).catch(function(e) {
      SS.ui.toast(e && e.message ? e.message : 'Lỗi đăng bài', 'error');
    });
  }
};
