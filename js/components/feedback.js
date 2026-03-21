/**
 * ShipperShop Component — Feedback
 * Submit feedback, bug reports, feature requests
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.Feedback = {

  open: function() {
    var html = '<div class="form-group"><label class="form-label">Loai gop y</label>'
      + '<div class="flex gap-2" id="fb-type">'
      + '<div class="chip chip-active" onclick="SS.Feedback._setType(\'feedback\',this)">💬 Gop y</div>'
      + '<div class="chip" onclick="SS.Feedback._setType(\'bug\',this)">🐛 Loi</div>'
      + '<div class="chip" onclick="SS.Feedback._setType(\'feature\',this)">💡 Tinh nang</div>'
      + '</div></div>'
      + '<div class="form-group"><label class="form-label">Noi dung</label>'
      + '<textarea id="fb-msg" class="form-textarea" rows="4" placeholder="Mo ta chi tiet..."></textarea></div>'
      + '<div class="form-group"><label class="form-label">Danh gia (tuy chon)</label>'
      + '<div class="flex gap-1" id="fb-stars">';
    for (var i = 1; i <= 5; i++) {
      html += '<span style="font-size:24px;cursor:pointer;opacity:0.3" data-star="' + i + '" onclick="SS.Feedback._setRating(' + i + ')">⭐</span>';
    }
    html += '</div></div>';

    SS.ui.modal({
      title: 'Gop y & Phan hoi',
      html: html,
      confirmText: 'Gui gop y',
      onConfirm: function() {
        var msg = document.getElementById('fb-msg').value.trim();
        if (!msg || msg.length < 5) { SS.ui.toast('Noi dung toi thieu 5 ky tu', 'warning'); return; }
        SS.api.post('/feedback.php', {
          type: SS.Feedback._type || 'feedback',
          message: msg,
          rating: SS.Feedback._rating || 0
        }).then(function(d) {
          SS.ui.toast(d.message || 'Cam on!', 'success');
        });
      }
    });
  },

  _type: 'feedback',
  _rating: 0,

  _setType: function(type, el) {
    SS.Feedback._type = type;
    var chips = document.querySelectorAll('#fb-type .chip');
    for (var i = 0; i < chips.length; i++) chips[i].classList.remove('chip-active');
    el.classList.add('chip-active');
  },

  _setRating: function(n) {
    SS.Feedback._rating = n;
    var stars = document.querySelectorAll('#fb-stars span');
    for (var i = 0; i < stars.length; i++) {
      stars[i].style.opacity = (i < n) ? '1' : '0.3';
    }
  }
};
