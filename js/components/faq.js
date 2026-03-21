/**
 * ShipperShop Component — FAQ
 * Searchable FAQ with categories, accordion UI
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.FAQ = {

  show: function(category) {
    var url = '/faq.php' + (category ? '?category=' + category : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var faqs = data.faqs || [];
      var cats = data.categories || [];

      // Category tabs
      var html = '<div class="flex gap-2 mb-3" style="overflow-x:auto">'
        + '<div class="chip ' + (!category ? 'chip-active' : '') + '" onclick="SS.FAQ.show()" style="cursor:pointer">Tat ca</div>';
      for (var c = 0; c < cats.length; c++) {
        var active = category === cats[c].id ? 'chip-active' : '';
        html += '<div class="chip ' + active + '" onclick="SS.FAQ.show(\'' + cats[c].id + '\')" style="cursor:pointer;white-space:nowrap">' + cats[c].icon + ' ' + SS.utils.esc(cats[c].name) + '</div>';
      }
      html += '</div>';

      // Search
      html += '<input class="form-input mb-3" placeholder="Tim kiem..." oninput="SS.FAQ._filter(this.value)" id="faq-search">';

      // FAQ items
      html += '<div id="faq-list">';
      for (var i = 0; i < faqs.length; i++) {
        var f = faqs[i];
        html += '<div class="faq-item" data-q="' + SS.utils.esc(f.q.toLowerCase()) + '" style="border-bottom:1px solid var(--border-light)">'
          + '<div class="flex items-center gap-2 p-3" style="cursor:pointer" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display===\'block\'?\'none\':\'block\';this.querySelector(\'.faq-arrow\').style.transform=this.nextElementSibling.style.display===\'block\'?\'rotate(180deg)\':\'rotate(0)\'">'
          + '<div class="flex-1 font-medium text-sm">' + SS.utils.esc(f.q) + '</div>'
          + '<i class="fa-solid fa-chevron-down faq-arrow text-muted" style="font-size:12px;transition:transform .2s"></i></div>'
          + '<div style="display:none;padding:0 12px 12px;color:var(--text-muted);font-size:13px;line-height:1.6">' + SS.utils.esc(f.a) + '</div></div>';
      }
      html += '</div>';
      if (!faqs.length) html += '<div class="text-center text-muted p-4">Khong tim thay</div>';

      SS.ui.sheet({title: 'Cau hoi thuong gap (' + faqs.length + ')', html: html});
    });
  },

  _filter: function(q) {
    q = q.toLowerCase();
    var items = document.querySelectorAll('.faq-item');
    for (var i = 0; i < items.length; i++) {
      items[i].style.display = items[i].getAttribute('data-q').indexOf(q) >= 0 ? '' : 'none';
    }
  }
};
