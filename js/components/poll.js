/**
 * ShipperShop Component — Poll
 * Render poll in post card, vote, show results with progress bars
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.Poll = {

  // Render poll inside a post card
  render: function(containerId, postId) {
    var el = document.getElementById(containerId);
    if (!el) return;

    SS.api.get('/polls.php?post_id=' + postId).then(function(d) {
      var poll = d.data;
      if (!poll) { el.innerHTML = ''; return; }
      SS.Poll._renderPoll(el, poll, postId);
    }).catch(function() { el.innerHTML = ''; });
  },

  _renderPoll: function(el, poll, postId) {
    var hasVoted = poll.my_votes && poll.my_votes.length > 0;
    var showResults = hasVoted || poll.ended;
    var total = Math.max(1, poll.total_votes);

    var html = '<div class="p-3" style="border-top:1px solid var(--border)">';
    if (poll.question) {
      html += '<div class="font-bold text-sm mb-2">' + SS.utils.esc(poll.question) + '</div>';
    }

    for (var i = 0; i < poll.options.length; i++) {
      var opt = poll.options[i];
      var pct = poll.total_votes > 0 ? Math.round(opt.vote_count / total * 100) : 0;
      var isMyVote = poll.my_votes && poll.my_votes.indexOf(parseInt(opt.id)) !== -1;

      if (showResults) {
        html += '<div style="margin-bottom:8px;cursor:pointer" onclick="SS.Poll.vote(' + poll.id + ',' + opt.id + ',' + postId + ')">'
          + '<div class="flex items-center justify-between mb-1">'
          + '<span class="text-sm' + (isMyVote ? ' font-bold' : '') + '">' + (isMyVote ? '✓ ' : '') + SS.utils.esc(opt.text) + '</span>'
          + '<span class="text-xs text-muted">' + pct + '%</span>'
          + '</div>'
          + '<div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden">'
          + '<div style="height:100%;width:' + pct + '%;background:' + (isMyVote ? 'var(--primary)' : 'var(--text-muted)') + ';border-radius:3px;transition:width .5s"></div>'
          + '</div></div>';
      } else {
        html += '<button class="btn btn-outline btn-block mb-2 text-sm" style="text-align:left;justify-content:flex-start" onclick="SS.Poll.vote(' + poll.id + ',' + opt.id + ',' + postId + ')">' + SS.utils.esc(opt.text) + '</button>';
      }
    }

    html += '<div class="text-xs text-muted mt-2">' + SS.utils.fN(poll.total_votes) + ' phiếu';
    if (poll.ends_at) {
      html += poll.ended ? ' · Đã kết thúc' : ' · Kết thúc ' + SS.utils.ago(poll.ends_at);
    }
    if (poll.allow_multiple) html += ' · Chọn nhiều';
    html += '</div></div>';

    el.innerHTML = html;
  },

  vote: function(pollId, optionId, postId) {
    if (!SS.store || !SS.store.isLoggedIn()) {
      SS.ui.toast('Đăng nhập để bỏ phiếu', 'warning');
      return;
    }
    SS.api.post('/polls.php?action=vote', {poll_id: pollId, option_id: optionId}).then(function(d) {
      SS.ui.toast(d.message || 'OK', 'success', 1500);
      if (SS.NotifSound && d.data && d.data.action === 'voted') SS.NotifSound.play('success');
      // Re-render
      var container = document.getElementById('poll-' + postId);
      if (container) SS.Poll.render('poll-' + postId, postId);
    }).catch(function(e) {
      SS.ui.toast(e && e.message ? e.message : 'Lỗi', 'error');
    });
  },

  // Create poll dialog
  create: function(postId) {
    var html = '<div class="form-group"><label class="form-label">Câu hỏi (tùy chọn)</label><input id="poll-q" class="form-input" placeholder="Bạn nghĩ sao?"></div>'
      + '<div id="poll-opts">'
      + '<div class="form-group"><label class="form-label">Lựa chọn</label>'
      + '<input class="form-input mb-2 poll-opt-input" placeholder="Lựa chọn 1">'
      + '<input class="form-input mb-2 poll-opt-input" placeholder="Lựa chọn 2">'
      + '</div></div>'
      + '<button class="btn btn-ghost btn-sm mb-3" onclick="var d=document.createElement(\'input\');d.className=\'form-input mb-2 poll-opt-input\';d.placeholder=\'Lựa chọn \'+(document.querySelectorAll(\'.poll-opt-input\').length+1);document.getElementById(\'poll-opts\').querySelector(\'.form-group\').appendChild(d)"><i class="fa-solid fa-plus"></i> Thêm lựa chọn</button>'
      + '<div class="flex gap-3">'
      + '<label class="form-label flex items-center gap-2"><input type="checkbox" id="poll-multi"> Chọn nhiều</label>'
      + '<select id="poll-hours" class="form-select" style="width:auto"><option value="0">Không giới hạn</option><option value="1">1 giờ</option><option value="6">6 giờ</option><option value="24" selected>24 giờ</option><option value="72">3 ngày</option><option value="168">7 ngày</option></select>'
      + '</div>';

    SS.ui.modal({
      title: 'Tạo Poll',
      html: html,
      confirmText: 'Tạo',
      onConfirm: function() {
        var q = (document.getElementById('poll-q') || {}).value || '';
        var inputs = document.querySelectorAll('.poll-opt-input');
        var opts = [];
        for (var i = 0; i < inputs.length; i++) {
          var v = inputs[i].value.trim();
          if (v) opts.push(v);
        }
        if (opts.length < 2) { SS.ui.toast('Ít nhất 2 lựa chọn', 'warning'); return; }
        var multi = document.getElementById('poll-multi') && document.getElementById('poll-multi').checked ? 1 : 0;
        var hours = parseInt((document.getElementById('poll-hours') || {}).value || 24);

        SS.api.post('/polls.php?action=create', {
          post_id: postId, question: q, options: opts, allow_multiple: multi, hours: hours
        }).then(function(d) {
          SS.ui.toast(d.message || 'Đã tạo!', 'success');
          SS.ui.closeModal();
        }).catch(function(e) { SS.ui.toast(e && e.message ? e.message : 'Lỗi', 'error'); });
      }
    });
  }
};
