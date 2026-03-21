/**
 * ShipperShop Component — Profile Completeness
 * Progress ring with score + actionable suggestions
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.ProfileComplete = {

  render: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/profile-score.php').then(function(d) {
      var data = d.data || {};
      var score = data.score || 0;
      var suggestions = data.suggestions || [];

      if (score >= 100) {
        el.innerHTML = '<div class="card mb-3"><div class="card-body flex items-center gap-3"><div style="font-size:28px">🎉</div><div class="flex-1"><div class="font-bold text-success">Hồ sơ hoàn chỉnh!</div><div class="text-sm text-muted">Bạn đã điền đầy đủ thông tin</div></div></div></div>';
        return;
      }

      // SVG ring
      var r = 36;
      var c = 2 * Math.PI * r;
      var offset = c - (score / 100) * c;
      var color = score >= 80 ? 'var(--success)' : (score >= 50 ? 'var(--warning)' : 'var(--accent)');

      var ring = '<svg width="88" height="88" viewBox="0 0 88 88" style="flex-shrink:0">'
        + '<circle cx="44" cy="44" r="' + r + '" fill="none" stroke="var(--border-light)" stroke-width="6"/>'
        + '<circle cx="44" cy="44" r="' + r + '" fill="none" stroke="' + color + '" stroke-width="6" stroke-dasharray="' + c.toFixed(1) + '" stroke-dashoffset="' + offset.toFixed(1) + '" stroke-linecap="round" transform="rotate(-90 44 44)" style="transition:stroke-dashoffset .8s"/>'
        + '<text x="44" y="48" text-anchor="middle" style="font-size:18px;font-weight:800;fill:var(--text)">' + score + '%</text>'
        + '</svg>';

      var sugHtml = '';
      for (var i = 0; i < Math.min(suggestions.length, 3); i++) {
        var s = suggestions[i];
        var actions = {
          avatar: 'window.location.href="/profile.html"',
          bio: 'window.location.href="/profile.html"',
          shipping_company: 'window.location.href="/profile.html"',
          phone: 'window.location.href="/profile.html"',
          first_post: 'SS.PostCreate&&SS.PostCreate.open()',
          first_follow: 'window.location.href="/people.html"',
          verified: 'SS.VerifiedBadge&&SS.VerifiedBadge.requestVerification()'
        };
        sugHtml += '<div class="text-sm" style="display:flex;align-items:center;gap:8px;padding:4px 0;cursor:pointer" onclick="' + (actions[s.key] || '') + '">'
          + '<div style="width:6px;height:6px;border-radius:50%;background:' + color + ';flex-shrink:0"></div>'
          + '<span>' + SS.utils.esc(s.label) + '</span>'
          + '<span class="text-xs text-muted">+' + s.weight + '%</span></div>';
      }

      el.innerHTML = '<div class="card mb-3"><div class="card-body">'
        + '<div class="flex items-center gap-4">' + ring
        + '<div class="flex-1"><div class="font-bold mb-1">Hoàn thiện hồ sơ</div>' + sugHtml + '</div>'
        + '</div></div></div>';
    }).catch(function() {});
  }
};
