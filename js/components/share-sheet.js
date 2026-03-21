/**
 * ShipperShop Component — Share Sheet
 * Native Web Share API + fallback copy link + share to group
 */
window.SS = window.SS || {};

SS.ShareSheet = {

  share: function(opts) {
    // opts: {title, text, url, postId}
    opts = opts || {};
    var url = opts.url || window.location.href;
    var title = opts.title || 'ShipperShop';
    var text = opts.text || '';

    // Try native share first
    if (navigator.share) {
      navigator.share({title: title, text: text, url: url}).then(function() {
        // Track share
        if (opts.postId) {
          SS.api.post('/posts.php?action=share', {post_id: opts.postId}).catch(function() {});
        }
      }).catch(function() {
        // User cancelled or error — show fallback
        SS.ShareSheet._showSheet(opts);
      });
      return;
    }

    // Fallback: custom sheet
    SS.ShareSheet._showSheet(opts);
  },

  _showSheet: function(opts) {
    var url = opts.url || window.location.href;
    var title = opts.title || 'ShipperShop';
    var text = opts.text || '';
    var encoded = encodeURIComponent(url);
    var encodedText = encodeURIComponent(text + ' ' + url);

    var html = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;text-align:center;padding:8px 0">'
      + SS.ShareSheet._btn('📋', 'Sao chép', function() { SS.utils.copyText(url); SS.ui.closeSheet(); })
      + SS.ShareSheet._btn('💬', 'Zalo', function() { window.open('https://zalo.me/share?u=' + encoded); })
      + SS.ShareSheet._btn('📘', 'Facebook', function() { window.open('https://www.facebook.com/sharer/sharer.php?u=' + encoded); })
      + SS.ShareSheet._btn('🐦', 'Twitter', function() { window.open('https://twitter.com/intent/tweet?url=' + encoded + '&text=' + encodeURIComponent(text)); })
      + SS.ShareSheet._btn('📧', 'Email', function() { window.open('mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodedText); })
      + SS.ShareSheet._btn('💬', 'Telegram', function() { window.open('https://t.me/share/url?url=' + encoded + '&text=' + encodeURIComponent(text)); })
      + '</div>';

    // Copy link input
    html += '<div style="display:flex;gap:8px;margin-top:16px;padding-top:12px;border-top:1px solid var(--border)">'
      + '<input class="form-input" value="' + SS.utils.esc(url) + '" readonly style="flex:1;font-size:12px" onclick="this.select()">'
      + '<button class="btn btn-primary btn-sm" onclick="SS.utils.copyText(\'' + SS.utils.esc(url).replace(/'/g, '\\x27') + '\');SS.ui.closeSheet()">Sao chép</button>'
      + '</div>';

    SS.ui.sheet({title: 'Chia sẻ', html: html});

    // Track share
    if (opts.postId) {
      SS.api.post('/posts.php?action=share', {post_id: opts.postId}).catch(function() {});
    }
  },

  _btn: function(icon, label, onclick) {
    var id = 'sh_' + Math.random().toString(36).substr(2, 5);
    setTimeout(function() {
      var el = document.getElementById(id);
      if (el) el.onclick = onclick;
    }, 100);
    return '<div id="' + id + '" style="cursor:pointer;padding:8px">'
      + '<div style="font-size:28px;margin-bottom:4px">' + icon + '</div>'
      + '<div style="font-size:11px;color:var(--text-muted)">' + label + '</div></div>';
  }
};
