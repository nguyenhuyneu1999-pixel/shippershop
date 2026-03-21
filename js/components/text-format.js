/**
 * ShipperShop Component — Text Formatter
 * Auto-linkify URLs, phone numbers, hashtags, @mentions in post content
 * Also handles "Read more" truncation for long posts
 */
window.SS = window.SS || {};

SS.TextFormat = {

  // Format post content with auto-linking
  format: function(text, opts) {
    if (!text) return '';
    opts = opts || {};
    var html = SS.utils.esc(text);

    // URLs → clickable links
    html = html.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:var(--primary);text-decoration:none">$1</a>');

    // Phone numbers (Vietnamese format)
    html = html.replace(/(0\d{9,10})/g, '<a href="tel:$1" style="color:var(--primary);text-decoration:none">$1</a>');

    // Hashtags
    html = html.replace(/#([a-zA-Z0-9_\u00C0-\u024F]+)/g, '<a href="/index.html?search=%23$1" style="color:var(--primary);text-decoration:none;font-weight:500">#$1</a>');

    // @Mentions
    html = html.replace(/@([a-zA-Z0-9_\u00C0-\u024F]+)/g, '<a href="/people.html?search=$1" style="color:var(--primary);text-decoration:none;font-weight:500">@$1</a>');

    // Line breaks
    html = html.replace(/\n/g, '<br>');

    return html;
  },

  // Truncate with "Xem thêm" button
  truncate: function(text, maxLen, postId) {
    maxLen = maxLen || 300;
    if (!text || text.length <= maxLen) return SS.TextFormat.format(text);

    var truncated = text.substring(0, maxLen);
    // Don't cut in middle of a word
    var lastSpace = truncated.lastIndexOf(' ');
    if (lastSpace > maxLen * 0.7) truncated = truncated.substring(0, lastSpace);

    var id = 'tf-' + (postId || Math.random().toString(36).substr(2, 6));
    return '<span id="' + id + '-short">'
      + SS.TextFormat.format(truncated)
      + '... <a onclick="document.getElementById(\'' + id + '-short\').style.display=\'none\';document.getElementById(\'' + id + '-full\').style.display=\'inline\'" style="color:var(--primary);cursor:pointer;font-weight:600">Xem thêm</a></span>'
      + '<span id="' + id + '-full" style="display:none">'
      + SS.TextFormat.format(text)
      + ' <a onclick="document.getElementById(\'' + id + '-full\').style.display=\'none\';document.getElementById(\'' + id + '-short\').style.display=\'inline\'" style="color:var(--primary);cursor:pointer;font-weight:600">Thu gọn</a></span>';
  },

  // Strip all formatting (plain text)
  plain: function(html) {
    var tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
  }
};
