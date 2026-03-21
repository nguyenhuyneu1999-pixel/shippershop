/**
 * ShipperShop Component — Search Filters
 * Advanced search panel: type, sort, date range, company, media filter
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.SearchFilters = {
  _filters: {},

  open: function(query, onResults) {
    var companies = ['GHTK','GHN','J&T','Viettel Post','SPX','Ninja Van','BEST','Ahamove','Grab','Be','Gojek'];
    var compOpts = '<option value="">Tất cả</option>';
    for (var i = 0; i < companies.length; i++) {
      compOpts += '<option value="' + companies[i] + '">' + companies[i] + '</option>';
    }

    var html = '<div class="form-group"><label class="form-label">Từ khóa</label><input id="sf-q" class="form-input" value="' + SS.utils.esc(query || '') + '"></div>'
      + '<div class="form-group"><label class="form-label">Loại</label><select id="sf-type" class="form-select"><option value="posts">Bài viết</option><option value="users">Người dùng</option><option value="groups">Nhóm</option></select></div>'
      + '<div class="form-group"><label class="form-label">Sắp xếp</label><select id="sf-sort" class="form-select"><option value="relevant">Liên quan nhất</option><option value="newest">Mới nhất</option><option value="popular">Phổ biến nhất</option></select></div>'
      + '<div class="form-group"><label class="form-label">Hãng vận chuyển</label><select id="sf-company" class="form-select">' + compOpts + '</select></div>'
      + '<div class="flex gap-2"><div class="form-group flex-1"><label class="form-label">Từ ngày</label><input id="sf-from" type="date" class="form-input"></div>'
      + '<div class="form-group flex-1"><label class="form-label">Đến ngày</label><input id="sf-to" type="date" class="form-input"></div></div>'
      + '<div class="flex gap-3 mb-3">'
      + '<label class="flex items-center gap-2 text-sm"><input type="checkbox" id="sf-img"> Có ảnh</label>'
      + '<label class="flex items-center gap-2 text-sm"><input type="checkbox" id="sf-vid"> Có video</label>'
      + '</div>';

    SS.SearchFilters._onResults = onResults;

    SS.ui.modal({
      title: 'Tìm kiếm nâng cao',
      html: html,
      confirmText: 'Tìm kiếm',
      onConfirm: function() {
        SS.SearchFilters._search();
      }
    });
  },

  _onResults: null,

  _search: function() {
    var params = [];
    var q = (document.getElementById('sf-q') || {}).value || '';
    var type = (document.getElementById('sf-type') || {}).value || 'posts';
    var sort = (document.getElementById('sf-sort') || {}).value || 'relevant';
    var company = (document.getElementById('sf-company') || {}).value || '';
    var from = (document.getElementById('sf-from') || {}).value || '';
    var to = (document.getElementById('sf-to') || {}).value || '';
    var hasImg = document.getElementById('sf-img') && document.getElementById('sf-img').checked;
    var hasVid = document.getElementById('sf-vid') && document.getElementById('sf-vid').checked;

    params.push('action=advanced');
    if (q) params.push('q=' + encodeURIComponent(q));
    params.push('type=' + type);
    params.push('sort=' + sort);
    if (company) params.push('company=' + encodeURIComponent(company));
    if (from) params.push('date_from=' + from);
    if (to) params.push('date_to=' + to);
    if (hasImg) params.push('has_image=1');
    if (hasVid) params.push('has_video=1');

    SS.ui.closeModal();
    SS.ui.loading(true);

    SS.api.get('/search.php?' + params.join('&')).then(function(d) {
      SS.ui.loading(false);
      if (SS.SearchFilters._onResults) {
        SS.SearchFilters._onResults(d.data, {q: q, type: type, sort: sort});
      }
    }).catch(function() {
      SS.ui.loading(false);
      SS.ui.toast('Lỗi tìm kiếm', 'error');
    });
  }
};
