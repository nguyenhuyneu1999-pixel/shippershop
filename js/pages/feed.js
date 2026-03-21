/**
 * ShipperShop Page — Feed (index.html)
 * Infinite scroll, pull-to-refresh, skeleton loading
 * Uses: SS.api, SS.PostCard, SS.CommentSheet, SS.VideoPlayer, SS.ui
 */
window.SS = window.SS || {};

SS.Feed = {
  _page: 1,
  _loading: false,
  _hasMore: true,
  _sort: 'hot',
  _type: '',
  _company: '',
  _province: '',
  _district: '',
  _ward: '',
  _search: '',
  _container: null,

  init: function(containerId) {
    SS.Feed._container = document.getElementById(containerId || 'feed');
    if (!SS.Feed._container) return;

    // Show skeleton while loading
    SS.Feed._container.innerHTML = SS.PostCard.skeleton(3);

    // Load first page
    SS.Feed.load(false);

    // Infinite scroll
    window.addEventListener('scroll', SS.utils.throttle(function() {
      if (SS.Feed._loading || !SS.Feed._hasMore) return;
      var scrollBottom = window.innerHeight + window.scrollY;
      var docHeight = document.documentElement.scrollHeight;
      if (scrollBottom >= docHeight - 600) {
        SS.Feed.load(true);
      }
    }, 200));

    // Pull to refresh (mobile)
    SS.Feed._setupPullToRefresh();
  },

  load: function(append) {
    if (SS.Feed._loading) return;
    SS.Feed._loading = true;

    if (!append) {
      SS.Feed._page = 1;
      SS.Feed._hasMore = true;
    }

    var params = {
      sort: SS.Feed._sort,
      page: SS.Feed._page,
      limit: 15
    };
    if (SS.Feed._type) params.type = SS.Feed._type;
    if (SS.Feed._company) params.company = SS.Feed._company;
    if (SS.Feed._province) params.province = SS.Feed._province;
    if (SS.Feed._district) params.district = SS.Feed._district;
    if (SS.Feed._ward) params.ward = SS.Feed._ward;
    if (SS.Feed._search) params.search = SS.Feed._search;

    SS.api.get('/posts.php', params).then(function(d) {
      var posts = d.data ? d.data.posts : (d.data || []);
      var meta = d.data ? d.data.meta : {};

      if (!append) {
        SS.Feed._container.innerHTML = '';
      }

      if (posts && posts.length) {
        // Remove loading indicator if exists
        var loader = SS.Feed._container.querySelector('.feed-loader');
        if (loader) loader.remove();

        SS.Feed._container.insertAdjacentHTML('beforeend', SS.PostCard.renderFeed(posts));
        SS.Feed._page++;

        // Check if more pages
        if (meta && meta.total_pages && SS.Feed._page > meta.total_pages) {
          SS.Feed._hasMore = false;
        }

        // Init video autoplay for new posts
        if (SS.VideoPlayer) SS.VideoPlayer.refresh();
      } else if (!append) {
        SS.Feed._container.innerHTML = '<div class="empty-state"><img src="/assets/img/defaults/no-posts.svg" style="width:120px;opacity:.5" loading="lazy"><div class="empty-text mt-3">Chưa có bài viết nào</div></div>';
      }

      if (SS.Feed._hasMore && posts && posts.length) {
        // Add loading indicator at bottom
        var existing = SS.Feed._container.querySelector('.feed-loader');
        if (!existing) {
          SS.Feed._container.insertAdjacentHTML('beforeend', '<div class="feed-loader" style="text-align:center;padding:20px"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>');
        }
      } else {
        var existing2 = SS.Feed._container.querySelector('.feed-loader');
        if (existing2) existing2.remove();
      }

      SS.Feed._loading = false;
    }).catch(function() {
      SS.Feed._loading = false;
      if (!append) {
        SS.Feed._container.innerHTML = '<div class="empty-state"><div class="empty-text">Lỗi tải bài viết. Vuốt xuống để thử lại.</div></div>';
      }
    });
  },

  setSort: function(sort) {
    SS.Feed._sort = sort;
    SS.Feed._container.innerHTML = SS.PostCard.skeleton(3);
    SS.Feed.load(false);
    // Update active tab UI
    var tabs = document.querySelectorAll('.feed-sort-tab');
    for (var i = 0; i < tabs.length; i++) {
      tabs[i].classList.toggle('tab-active', tabs[i].getAttribute('data-sort') === sort);
    }
  },

  setType: function(type) {
    SS.Feed._type = type;
    SS.Feed.load(false);
  },

  setCompany: function(company) {
    SS.Feed._company = company;
    SS.Feed._search = '';
    SS.Feed.load(false);
  },

  setProvince: function(province) {
    SS.Feed._province = province;
    SS.Feed._district = '';
    SS.Feed._ward = '';
    SS.Feed.load(false);
  },

  setDistrict: function(district) {
    SS.Feed._district = district;
    SS.Feed._ward = '';
    SS.Feed.load(false);
  },

  setWard: function(ward) {
    SS.Feed._ward = ward;
    SS.Feed.load(false);
  },

  search: function(query) {
    SS.Feed._search = query;
    SS.Feed._company = '';
    SS.Feed.load(false);
  },

  clearFilters: function() {
    SS.Feed._type = '';
    SS.Feed._company = '';
    SS.Feed._province = '';
    SS.Feed._district = '';
    SS.Feed._ward = '';
    SS.Feed._search = '';
    SS.Feed.load(false);
  },

  refresh: function() {
    SS.Feed._container.innerHTML = SS.PostCard.skeleton(3);
    SS.Feed.load(false);
  },

  _setupPullToRefresh: function() {
    var startY = 0;
    var pulling = false;
    var indicator = null;

    document.addEventListener('touchstart', function(e) {
      if (window.scrollY === 0 && e.touches.length === 1) {
        startY = e.touches[0].clientY;
        pulling = true;
      }
    }, {passive: true});

    document.addEventListener('touchmove', function(e) {
      if (!pulling) return;
      var dy = e.touches[0].clientY - startY;
      if (dy > 60 && !indicator) {
        indicator = document.createElement('div');
        indicator.id = 'ptr-indicator';
        indicator.style.cssText = 'position:fixed;top:0;left:0;right:0;height:50px;display:flex;align-items:center;justify-content:center;z-index:100;background:var(--primary);color:#fff;font-size:13px;font-weight:500;transform:translateY(-50px);transition:transform .2s';
        indicator.textContent = 'Kéo xuống để làm mới...';
        document.body.appendChild(indicator);
        setTimeout(function() { indicator.style.transform = 'translateY(0)'; }, 10);
      }
    }, {passive: true});

    document.addEventListener('touchend', function() {
      if (indicator) {
        indicator.textContent = 'Đang tải...';
        SS.Feed.refresh();
        setTimeout(function() {
          if (indicator && indicator.parentNode) {
            indicator.style.transform = 'translateY(-50px)';
            setTimeout(function() { if (indicator.parentNode) indicator.parentNode.removeChild(indicator); }, 200);
          }
          indicator = null;
        }, 1000);
      }
      pulling = false;
      startY = 0;
    });
  }
};
