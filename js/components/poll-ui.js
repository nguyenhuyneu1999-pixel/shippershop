/**
 * ShipperShop Component — Poll UI
 * Render polls inside posts, vote, show results with animated bars
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.PollUI = {

  // Render poll inside a post card
  render: function(postId, containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;

    SS.api.get('/post-polls.php?post_id=' + postId).then(function(d) {
      var poll = d.data;
      if (!poll) { el.innerHTML = ''; return; }

      var opts = poll.options || [];
      var total = parseInt(poll.total_votes) || 0;
      var myVote = poll.my_vote;
      var expired = poll.expired;
      var showResults = myVote || expired;

      var html = '<div class="card" style="margin:8px 12px;border:1px solid var(--border)">'
        + '<div class="card-body" style="padding:12px">'
        + '<div class="font-bold text-sm mb-2">' + SS.utils.esc(poll.question) + '</div>';

      for (var i = 0; i < opts.length; i++) {
        var o = opts[i];
        var pct = total > 0 ? Math.round(parseInt(o.vote_count) / total * 100) : 0;
        var isMyVote = myVote === parseInt(o.id);
        var barColor = isMyVote ? 'var(--primary)' : 'var(--border)';

        if (showResults) {
          html += '<div style="margin-bottom:6px">'
            + '<div class="flex justify-between text-xs mb-1">'
            + '<span' + (isMyVote ? ' style="font-weight:700;color:var(--primary)"' : '') + '>' + (isMyVote ? '✓ ' : '') + SS.utils.esc(o.text) + '</span>'
            + '<span class="font-bold">' + pct + '%</span></div>'
            + '<div style="background:var(--bg);border-radius:6px;height:8px;overflow:hidden">'
            + '<div style="width:' + pct + '%;height:100%;background:' + barColor + ';border-radius:6px;transition:width .5s"></div>'
            + '</div></div>';
        } else {
          html += '<button class="btn btn-outline btn-sm" style="width:100%;margin-bottom:6px;text-align:left" onclick="SS.PollUI.vote(' + poll.id + ',' + o.id + ',' + postId + ')">'
            + SS.utils.esc(o.text) + '</button>';
        }
      }

      var footer = '<div class="flex justify-between text-xs text-muted mt-2">'
        + '<span>' + SS.utils.fN(total) + ' phiếu bầu</span>';
      if (expired) footer += '<span style="color:var(--danger)">Đã kết thúc</span>';
      else if (poll.expires_at) footer += '<span>Kết thúc ' + SS.utils.ago(poll.expires_at) + '</span>';
      footer += '</div>';

      html += footer + '</div></div>';
      el.innerHTML = html;
    }).catch(function() {});
  },

  vote: function(pollId, optionId, postId) {
    if (!SS.store || !SS.store.isLoggedIn()) {
      SS.ui.toast('Đăng nhập để bình chọn', 'warning');
      return;
    }
    SS.api.post('/post-polls.php?action=vote', {poll_id: pollId, option_id: optionId}).then(function(d) {
      SS.ui.toast(d.message || 'Đã bình chọn!', 'success', 1500);
      SS.PollUI.render(postId, 'poll-' + postId);
    }).catch(function(e) {
      SS.ui.toast(e.message || 'Lỗi', 'error');
    });
  },

  // Create poll dialog
  openCreate: function(postId, onCreated) {
    var html = '<div class="form-group"><label class="form-label">Câu hỏi</label>'
      + '<input id="poll-q" class="form-input" placeholder="Hỏi gì đó..." maxlength="500"></div>'
      + '<div id="poll-opts">'
      + '<div class="form-group"><label class="form-label">Lựa chọn</label>'
      + '<input class="form-input mb-2 poll-opt-input" placeholder="Lựa chọn 1" maxlength="200">'
      + '<input class="form-input mb-2 poll-opt-input" placeholder="Lựa chọn 2" maxlength="200">'
      + '</div></div>'
      + '<button class="btn btn-ghost btn-sm mb-3" onclick="SS.PollUI._addOption()"><i class="fa-solid fa-plus"></i> Thêm lựa chọn</button>'
      + '<div class="form-group"><label class="form-label">Thời hạn</label>'
      + '<select id="poll-hours" class="form-select"><option value="0">Không giới hạn</option><option value="1">1 giờ</option><option value="6">6 giờ</option><option value="24" selected>24 giờ</option><option value="72">3 ngày</option><option value="168">7 ngày</option></select></div>';

    SS.ui.modal({
      title: 'Tạo bình chọn',
      html: html,
      confirmText: 'Tạo',
      onConfirm: function() {
        var q = document.getElementById('poll-q').value.trim();
        var inputs = document.querySelectorAll('.poll-opt-input');
        var opts = [];
        for (var i = 0; i < inputs.length; i++) {
          var v = inputs[i].value.trim();
          if (v) opts.push(v);
        }
        var hours = parseInt(document.getElementById('poll-hours').value) || 0;
        if (!q) { SS.ui.toast('Nhập câu hỏi', 'warning'); return; }
        if (opts.length < 2) { SS.ui.toast('Cần ít nhất 2 lựa chọn', 'warning'); return; }

        SS.api.post('/post-polls.php?action=create', {post_id: postId, question: q, options: opts, hours: hours}).then(function(d) {
          SS.ui.toast('Đã tạo bình chọn!', 'success');
          if (onCreated) onCreated(d.data);
        }).catch(function(e) { SS.ui.toast(e.message || 'Lỗi', 'error'); });
      }
    });
  },

  _addOption: function() {
    var container = document.getElementById('poll-opts');
    if (!container) return;
    var count = container.querySelectorAll('.poll-opt-input').length;
    if (count >= 6) { SS.ui.toast('Tối đa 6 lựa chọn', 'info'); return; }
    var input = document.createElement('input');
    input.className = 'form-input mb-2 poll-opt-input';
    input.placeholder = 'Lựa chọn ' + (count + 1);
    input.maxLength = 200;
    container.querySelector('.form-group').appendChild(input);
  }
};
