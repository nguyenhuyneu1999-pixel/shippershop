/**
 * ShipperShop Component — Contact Card
 */
window.SS = window.SS || {};

SS.ContactCard = {
  show: function(userId) {
    SS.api.get('/contact-card.php' + (userId ? '?user_id=' + userId : '')).then(function(d) {
      var card = d.data || {};
      var html = '<div class="card" style="padding:16px;background:linear-gradient(135deg,var(--primary),#6d28d9);color:#fff;border-radius:12px;margin-bottom:12px"><div class="font-bold text-lg">' + SS.utils.esc(card.name || '') + '</div>' + (card.company ? '<div class="text-sm" style="opacity:.8">' + SS.utils.esc(card.company) + '</div>' : '') + '</div>';
      var fields = [{icon:'📞',l:'SDT',v:card.phone},{icon:'📧',l:'Email',v:card.email},{icon:'📍',l:'Dia chi',v:card.address},{icon:'📝',l:'Ghi chu',v:card.note}];
      for (var i = 0; i < fields.length; i++) {
        if (fields[i].v) html += '<div class="flex items-center gap-2 p-2" style="border-bottom:1px solid var(--border-light)"><span>' + fields[i].icon + '</span><div><div class="text-xs text-muted">' + fields[i].l + '</div><div class="text-sm">' + SS.utils.esc(fields[i].v) + '</div></div></div>';
      }
      SS.ui.sheet({title: 'The lien lac', html: html});
    });
  }
};
