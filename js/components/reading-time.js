/**
 * ShipperShop Component — Reading Time
 * Estimates reading time for long posts (Vietnamese ~200 words/min)
 */
window.SS = window.SS || {};

SS.ReadingTime = {

  // Calculate reading time in minutes
  calc: function(text) {
    if (!text) return 0;
    // Vietnamese words: split by spaces + count chars / avg word length
    var words = text.trim().split(/\s+/).length;
    return Math.max(1, Math.ceil(words / 200));
  },

  // Render reading time badge (only for long posts)
  render: function(text) {
    if (!text || text.length < 300) return '';
    var mins = SS.ReadingTime.calc(text);
    return '<span style="display:inline-flex;align-items:center;gap:3px;font-size:11px;color:var(--text-muted);margin-left:6px" title="Thời gian đọc"><i class="fa-regular fa-clock" style="font-size:10px"></i> ' + mins + ' phút đọc</span>';
  },

  // Character count with limit indicator
  charCount: function(text, maxChars) {
    if (!text) return '';
    var len = text.length;
    maxChars = maxChars || 2000;
    var pct = Math.min(100, Math.round(len / maxChars * 100));
    var color = pct > 90 ? 'var(--danger)' : (pct > 70 ? 'var(--warning)' : 'var(--text-muted)');
    return '<span style="font-size:11px;color:' + color + '">' + len + '/' + maxChars + '</span>';
  }
};
