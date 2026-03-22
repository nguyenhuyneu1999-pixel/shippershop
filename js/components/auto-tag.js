/**
 * ShipperShop Component — Auto Tag
 * Suggest tags/hashtags based on post content
 */
window.SS = window.SS || {};

SS.AutoTag = {
  suggest: function(text, containerId) {
    if (!text || text.length < 5) return;
    SS.api.post('/auto-tag.php', {text: text}).then(function(d) {
      var tags = (d.data || {}).suggested_tags || [];
      var el = document.getElementById(containerId);
      if (!el || !tags.length) return;
      var html = '<div class="flex gap-1 flex-wrap">';
      for (var i = 0; i < tags.length; i++) {
        html += '<span class="chip chip-sm" style="cursor:pointer;font-size:11px" onclick="SS.AutoTag._insert(\'#' + tags[i] + '\')">#' + tags[i] + '</span>';
      }
      html += '</div>';
      el.innerHTML = html;
    }).catch(function() {});
  },

  _insert: function(tag) {
    var ta = document.querySelector('textarea[name="content"],#post-content,.post-textarea');
    if (ta) { ta.value = ta.value.trim() + ' ' + tag; ta.focus(); }
  },

  // Debounced version for real-time suggestions
  _timer: null,
  onInput: function(text, containerId) {
    clearTimeout(SS.AutoTag._timer);
    SS.AutoTag._timer = setTimeout(function() {
      SS.AutoTag.suggest(text, containerId);
    }, 800);
  }
};
