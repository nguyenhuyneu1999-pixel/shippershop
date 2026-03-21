/**
 * ShipperShop Component — Feed Filter Bar
 * Horizontal scrollable filter chips: All, GHTK, GHN, J&T, SPX, etc.
 * Uses: SS.utils
 */
window.SS = window.SS || {};

SS.FeedFilter = {
  _active: '',

  render: function(containerId, onFilter) {
    var el = document.getElementById(containerId);
    if (!el) return;

    var companies = [
      {id:'',label:'Tất cả',icon:'🏠'},
      {id:'GHTK',label:'GHTK',color:'#00b14f'},
      {id:'GHN',label:'GHN',color:'#ff6600'},
      {id:'J&T',label:'J&T',color:'#d32f2f'},
      {id:'SPX',label:'SPX',color:'#EE4D2D'},
      {id:'Viettel Post',label:'Viettel',color:'#e21a1a'},
      {id:'Ninja Van',label:'Ninja',color:'#c41230'},
      {id:'BEST',label:'BEST',color:'#ffc107'},
      {id:'Ahamove',label:'Ahamove',color:'#f5a623'},
      {id:'Grab',label:'Grab',color:'#00b14f'},
      {id:'Be',label:'Be',color:'#5bc500'},
    ];

    var html = '<div style="display:flex;gap:8px;overflow-x:auto;padding:8px 0;scrollbar-width:none;-webkit-overflow-scrolling:touch">';
    for (var i = 0; i < companies.length; i++) {
      var c = companies[i];
      var isActive = SS.FeedFilter._active === c.id;
      var bg = isActive ? (c.color || 'var(--primary)') : 'var(--card)';
      var color = isActive ? '#fff' : (c.color || 'var(--text)');
      var border = isActive ? 'transparent' : 'var(--border)';

      html += '<button data-filter="' + c.id + '" onclick="SS.FeedFilter._select(\'' + c.id + '\',this.parentNode)" style="flex-shrink:0;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid ' + border + ';background:' + bg + ';color:' + color + ';cursor:pointer;transition:all .2s;white-space:nowrap">'
        + (c.icon ? c.icon + ' ' : '') + c.label + '</button>';
    }
    html += '</div>';

    el.innerHTML = html;
    SS.FeedFilter._onFilter = onFilter;
  },

  _onFilter: null,

  _select: function(id, container) {
    SS.FeedFilter._active = id;

    // Update button styles
    var btns = container.querySelectorAll('button');
    for (var i = 0; i < btns.length; i++) {
      var btn = btns[i];
      var filterId = btn.getAttribute('data-filter');
      var isActive = filterId === id;
      // Reset all then highlight active
      if (isActive) {
        btn.style.background = btn.style.color !== '#fff' ? btn.style.color : 'var(--primary)';
        btn.style.color = '#fff';
        btn.style.borderColor = 'transparent';
      } else {
        btn.style.background = 'var(--card)';
        btn.style.borderColor = 'var(--border)';
        // Restore original color
      }
    }

    // Call filter callback
    if (SS.FeedFilter._onFilter) SS.FeedFilter._onFilter(id);
  },

  getActive: function() {
    return SS.FeedFilter._active;
  }
};
