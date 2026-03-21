/**
 * ShipperShop Component — Saved Replies
 * Quick reply picker for messages and comments
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.SavedReplies = {

  // Show picker and insert into textarea
  pick: function(targetTextarea) {
    SS.api.get('/saved-replies.php').then(function(d) {
      var replies = d.data || [];
      var cats = {};
      for (var i = 0; i < replies.length; i++) {
        var cat = replies[i].category || 'general';
        if (!cats[cat]) cats[cat] = [];
        cats[cat].push(replies[i]);
      }

      var catNames = {general: 'Chung', delivery: 'Giao hàng', support: 'Hỗ trợ'};
      var html = '';
      var keys = Object.keys(cats);
      for (var k = 0; k < keys.length; k++) {
        var catKey = keys[k];
        html += '<div class="text-xs font-bold text-muted mb-1 mt-2">' + (catNames[catKey] || catKey) + '</div>';
        for (var j = 0; j < cats[catKey].length; j++) {
          var r = cats[catKey][j];
          html += '<div class="list-item" style="cursor:pointer;padding:8px 12px" onclick="SS.SavedReplies._insert(' + JSON.stringify(r.text).replace(/"/g, '&quot;') + ')">'
            + '<div class="flex-1"><div class="text-sm font-medium">' + SS.utils.esc(r.title) + '</div>'
            + '<div class="text-xs text-muted">' + SS.utils.esc(r.text.substring(0, 60)) + '</div></div></div>';
        }
      }

      html += '<div class="divider"></div>'
        + '<div class="list-item" style="cursor:pointer;color:var(--primary)" onclick="SS.SavedReplies.manage()">'
        + '<i class="fa-solid fa-gear" style="width:20px"></i><span class="text-sm">Quản lý mẫu trả lời</span></div>';

      SS.ui.sheet({title: 'Trả lời nhanh', html: html});
      SS.SavedReplies._target = targetTextarea;
    });
  },

  _target: null,

  _insert: function(text) {
    SS.ui.closeSheet();
    if (SS.SavedReplies._target) {
      var ta = SS.SavedReplies._target;
      if (typeof ta === 'string') ta = document.querySelector(ta);
      if (ta) { ta.value = text; ta.focus(); ta.dispatchEvent(new Event('input')); }
    }
  },

  manage: function() {
    SS.ui.closeSheet();
    SS.api.get('/saved-replies.php').then(function(d) {
      var replies = d.data || [];
      var html = '<button class="btn btn-primary btn-sm mb-3" onclick="SS.SavedReplies._add()"><i class="fa-solid fa-plus"></i> Thêm mẫu</button>';
      for (var i = 0; i < replies.length; i++) {
        var r = replies[i];
        html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)">'
          + '<div class="flex-1"><div class="text-sm font-medium">' + SS.utils.esc(r.title) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(r.text.substring(0, 50)) + '</div></div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.SavedReplies._del(' + r.id + ')"><i class="fa-solid fa-trash text-danger"></i></button></div>';
      }
      SS.ui.sheet({title: 'Quản lý mẫu (' + replies.length + ')', html: html});
    });
  },

  _add: function() {
    SS.ui.modal({
      title: 'Thêm mẫu trả lời',
      html: '<div class="form-group"><label class="form-label">Tiêu đề</label><input id="sr-title" class="form-input" placeholder="VD: Cảm ơn"></div>'
        + '<div class="form-group"><label class="form-label">Nội dung</label><textarea id="sr-text" class="form-textarea" rows="3" placeholder="Nội dung trả lời..."></textarea></div>',
      confirmText: 'Thêm',
      onConfirm: function() {
        SS.api.post('/saved-replies.php?action=add', {
          title: document.getElementById('sr-title').value,
          text: document.getElementById('sr-text').value
        }).then(function() { SS.ui.toast('Đã thêm!', 'success'); SS.SavedReplies.manage(); });
      }
    });
  },

  _del: function(id) {
    SS.api.post('/saved-replies.php?action=delete', {reply_id: id}).then(function() {
      SS.ui.toast('Đã xóa', 'success'); SS.SavedReplies.manage();
    });
  }
};
