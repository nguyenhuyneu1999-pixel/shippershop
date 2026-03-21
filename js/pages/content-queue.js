/**
 * ShipperShop Page — Admin Content Queue
 * View, approve, reject, edit queued auto-content
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.ContentQueue = {
  _status: 'pending',
  _page: 1,

  init: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;

    // Stats first
    SS.api.get('/content-queue.php?action=stats').then(function(d) {
      var s = d.data || {};
      var statsHtml = '<div class="flex gap-3 mb-3">'
        + '<div class="card flex-1"><div class="card-body text-center"><div style="font-size:20px;font-weight:800;color:var(--warning)">' + (s.pending || 0) + '</div><div class="text-xs text-muted">Chờ duyệt</div></div></div>'
        + '<div class="card flex-1"><div class="card-body text-center"><div style="font-size:20px;font-weight:800;color:var(--success)">' + (s.today_published || 0) + '</div><div class="text-xs text-muted">Hôm nay</div></div></div>'
        + '<div class="card flex-1"><div class="card-body text-center"><div style="font-size:20px;font-weight:800;color:var(--primary)">' + (s.published || 0) + '</div><div class="text-xs text-muted">Đã đăng</div></div></div>'
        + '</div>';
      el.innerHTML = statsHtml
        + '<div class="flex gap-2 mb-3">'
        + '<button class="btn btn-sm btn-primary" onclick="SS.ContentQueue._loadStatus(\'pending\')">Chờ duyệt</button>'
        + '<button class="btn btn-sm btn-outline" onclick="SS.ContentQueue._loadStatus(\'published\')">Đã đăng</button>'
        + '<button class="btn btn-sm btn-outline" onclick="SS.ContentQueue._loadStatus(\'rejected\')">Từ chối</button>'
        + '</div>'
        + '<div id="cq-list"></div>';
      SS.ContentQueue._loadItems();
    });
  },

  _loadStatus: function(status) {
    SS.ContentQueue._status = status;
    SS.ContentQueue._page = 1;
    SS.ContentQueue._loadItems();
  },

  _loadItems: function() {
    var el = document.getElementById('cq-list');
    if (!el) return;
    el.innerHTML = '<div class="p-3 text-center"><div class="spin" style="width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/content-queue.php?status=' + SS.ContentQueue._status + '&page=' + SS.ContentQueue._page + '&limit=10').then(function(d) {
      var data = d.data || {};
      var items = data.items || [];
      if (!items.length) { el.innerHTML = '<div class="empty-state p-4"><div class="empty-text">Không có nội dung</div></div>'; return; }

      var html = '';
      var isPending = SS.ContentQueue._status === 'pending';

      for (var i = 0; i < items.length; i++) {
        var item = items[i];
        html += '<div class="card mb-2" id="cq-' + item.id + '"><div class="card-body">'
          + '<div class="text-sm" style="line-height:1.6;white-space:pre-wrap;margin-bottom:8px">' + SS.utils.esc((item.content || '').substring(0, 300)) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.ago(item.created_at) + (item.type ? ' · ' + item.type : '') + '</div>';

        if (isPending) {
          html += '<div class="flex gap-2 mt-2 pt-2" style="border-top:1px solid var(--border)">'
            + '<button class="btn btn-sm btn-success flex-1" onclick="SS.ContentQueue._action(\'approve\',' + item.id + ')"><i class="fa-solid fa-check"></i> Duyệt</button>'
            + '<button class="btn btn-sm btn-danger" onclick="SS.ContentQueue._action(\'reject\',' + item.id + ')"><i class="fa-solid fa-xmark"></i></button>'
            + '<button class="btn btn-sm btn-ghost" onclick="SS.ContentQueue._edit(' + item.id + ')"><i class="fa-solid fa-pen"></i></button>'
            + '</div>';
        }
        html += '</div></div>';
      }

      // Pagination
      if (data.total > 10) {
        html += '<div class="flex justify-center gap-2 mt-3">';
        if (SS.ContentQueue._page > 1) html += '<button class="btn btn-ghost btn-sm" onclick="SS.ContentQueue._page--;SS.ContentQueue._loadItems()">← Trước</button>';
        html += '<span class="text-sm text-muted" style="line-height:32px">' + SS.ContentQueue._page + '/' + Math.ceil(data.total / 10) + '</span>';
        if (SS.ContentQueue._page * 10 < data.total) html += '<button class="btn btn-ghost btn-sm" onclick="SS.ContentQueue._page++;SS.ContentQueue._loadItems()">Sau →</button>';
        html += '</div>';
      }

      el.innerHTML = html;
    });
  },

  _action: function(action, qid) {
    SS.api.post('/content-queue.php?action=' + action, {queue_id: qid}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success');
      var card = document.getElementById('cq-' + qid);
      if (card) { card.style.opacity = '0'; card.style.transition = 'opacity .3s'; setTimeout(function() { card.remove(); }, 300); }
    });
  },

  _edit: function(qid) {
    SS.api.get('/content-queue.php?status=pending&limit=50').then(function(d) {
      var items = (d.data && d.data.items) || [];
      var item = null;
      for (var i = 0; i < items.length; i++) { if (items[i].id == qid) { item = items[i]; break; } }
      if (!item) return;
      SS.ui.modal({
        title: 'Sửa nội dung',
        html: '<textarea id="cq-edit-ta" class="form-input" rows="6" style="font-size:14px">' + SS.utils.esc(item.content || '') + '</textarea>',
        confirmText: 'Lưu',
        onConfirm: function() {
          var content = document.getElementById('cq-edit-ta').value;
          SS.api.post('/content-queue.php?action=edit', {queue_id: qid, content: content}).then(function() {
            SS.ui.toast('Đã lưu', 'success');
            SS.ui.closeModal();
            SS.ContentQueue._loadItems();
          });
        }
      });
    });
  }
};
