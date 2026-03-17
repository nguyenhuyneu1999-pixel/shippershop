
/* ============================================================
   SHIPPERSHOP MOBILE.JS - Mobile UX Enhancement
   ============================================================ */

(function() {
'use strict';

// Only run on mobile/tablet
const isMobile = window.innerWidth <= 1024 || /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);

// ========================
// BOTTOM NAVIGATION BAR
// ========================
function injectBottomNav() {
  if (document.getElementById('mobileBottomNav')) return;

  const page = location.pathname.split('/').pop() || 'index.html';
  const isActive = (p) => {
    if (p === 'index.html' && (page === '' || page === 'index.html')) return 'active';
    if (p !== 'index.html' && page.startsWith(p.replace('.html',''))) return 'active';
    return '';
  };

  // Get cart count
  let cartCount = 0;
  try { const cart = JSON.parse(localStorage.getItem('cart') || '[]'); cartCount = cart.reduce((s,i) => s+(i.quantity||1), 0); } catch(e) {}
  const cartBadge = cartCount > 0 ? `<span class="mnav-badge">${cartCount > 99 ? '99+' : cartCount}</span>` : '';

  const user = JSON.parse(localStorage.getItem('user') || 'null');
  const profilePage = user ? 'profile.html' : 'login.html';
  const profileActive = (page === 'profile.html' || page === 'login.html') ? 'active' : '';

  // Determine if current page needs shop fab or post fab
  const isCommunity = page.includes('community');

  const nav = document.createElement('div');
  nav.id = 'mobileBottomNav';
  nav.innerHTML = `
    <a href="index.html" class="mnav-item ${isActive('index.html')}">
      <i class="fas fa-home"></i>
      <span>Trang chủ</span>
    </a>
    <a href="shop.html" class="mnav-item ${isActive('shop.html')}">
      <i class="fas fa-store"></i>
      <span>Mua sắm</span>
    </a>
    <a href="${isCommunity ? '#' : 'shop.html'}" class="mnav-item" id="navFab" onclick="handleFab(event)">
      <div class="mnav-fab">
        <i class="fas ${isCommunity ? 'fa-pen' : 'fa-search'}"></i>
      </div>
    </a>
    <a href="community.html" class="mnav-item ${isActive('community.html')}">
      <i class="fas fa-users"></i>
      <span>Cộng đồng</span>
    </a>
    <a href="${profilePage}" class="mnav-item ${profileActive}">
      ${user && user.avatar
        ? `<img src="${user.avatar}" style="width:24px;height:24px;border-radius:50%;object-fit:cover">`
        : '<i class="fas fa-user"></i>'}
      <span>${user ? user.fullname.split(' ').pop() : 'Tài khoản'}</span>
    </a>`;

  document.body.appendChild(nav);

  // FAB handler
  window.handleFab = function(e) {
    e.preventDefault();
    const p = location.pathname.split('/').pop() || 'index.html';
    if (p.includes('community')) {
      // Open create post modal
      if (typeof openModal === 'function') openModal('post');
      else if (typeof openCreatePostModal === 'function') openCreatePostModal();
    } else {
      // Focus search
      const si = document.getElementById('searchInput') || document.querySelector('.search-bar input');
      if (si) { si.focus(); si.scrollIntoView({behavior:'smooth', block:'center'}); }
    }
  };
}


// ========================
// UPDATE CART BADGE IN NAV
// ========================
function updateNavCartBadge() {
  try {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const count = cart.reduce((s, i) => s + (i.quantity || 1), 0);
    const existing = document.querySelector('.mnav-badge');
    const cartItem = document.querySelector('.mnav-item[href="cart.html"]');
    if (cartItem) {
      let badge = cartItem.querySelector('.mnav-badge');
      if (count > 0) {
        if (!badge) { badge = document.createElement('span'); badge.className = 'mnav-badge'; cartItem.appendChild(badge); }
        badge.textContent = count > 99 ? '99+' : count;
      } else if (badge) badge.remove();
    }
    // Update header cart badge too
    const hBadge = document.getElementById('cartCount');
    if (hBadge) hBadge.textContent = count;
  } catch(e) {}
}

// ========================
// PULL TO REFRESH
// ========================
function setupPullToRefresh() {
  let startY = 0, pulling = false;
  const indicator = document.createElement('div');
  indicator.id = 'pullIndicator';
  indicator.style.cssText = 'position:fixed;top:0;left:0;right:0;height:0;background:linear-gradient(var(--primary),rgba(238,77,45,0));z-index:600;transition:height .2s;overflow:hidden;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:600;';
  indicator.innerHTML = '<i class="fas fa-arrow-down" style="margin-right:6px"></i> Thả để tải lại';
  document.body.prepend(indicator);

  document.addEventListener('touchstart', e => {
    if (window.scrollY === 0) { startY = e.touches[0].clientY; pulling = true; }
  }, { passive: true });

  document.addEventListener('touchmove', e => {
    if (!pulling) return;
    const dy = e.touches[0].clientY - startY;
    if (dy > 0 && dy < 80) indicator.style.height = (dy * 0.8) + 'px';
  }, { passive: true });

  document.addEventListener('touchend', e => {
    if (!pulling) return;
    const dy = e.changedTouches[0].clientY - startY;
    if (dy > 60) { indicator.style.height = '50px'; setTimeout(() => { indicator.style.height = '0'; location.reload(); }, 300); }
    else indicator.style.height = '0';
    pulling = false;
  }, { passive: true });
}

// ========================
// TOUCH-FRIENDLY PRODUCT CARDS
// ========================
function enhanceProductCards() {
  // Add ripple effect on tap
  document.querySelectorAll('.product-card, .btn, .header-btn').forEach(el => {
    if (el.dataset.ripple) return;
    el.dataset.ripple = '1';
    el.style.position = 'relative';
    el.style.overflow = 'hidden';
    el.addEventListener('touchstart', function(e) {
      const r = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const x = e.touches[0].clientX - rect.left;
      const y = e.touches[0].clientY - rect.top;
      r.style.cssText = `position:absolute;width:100px;height:100px;background:rgba(0,0,0,.08);border-radius:50%;left:${x-50}px;top:${y-50}px;transform:scale(0);animation:ripple .4s ease-out;pointer-events:none;z-index:999`;
      this.appendChild(r);
      setTimeout(() => r.remove(), 500);
    }, { passive: true });
  });
}

// ========================
// HORIZONTAL SCROLL HINTS
// ========================
function addScrollHints() {
  document.querySelectorAll('.products-grid, .flash-sale-grid').forEach(grid => {
    if (window.innerWidth <= 768 && !grid.dataset.hint) {
      grid.dataset.hint = '1';
    }
  });
}

// ========================
// IMAGE LAZY LOADING
// ========================
function lazyLoadImages() {
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          if (img.dataset.src) { img.src = img.dataset.src; delete img.dataset.src; }
          observer.unobserve(img);
        }
      });
    }, { rootMargin: '200px' });

    document.querySelectorAll('img[data-src]').forEach(img => observer.observe(img));
  }
}

// ========================
// SEARCH BAR BEHAVIOR
// ========================
function enhanceSearch() {
  const searchInput = document.getElementById('searchInput') || document.querySelector('.search-bar input');
  if (!searchInput) return;

  // Auto-expand on focus (mobile)
  searchInput.addEventListener('focus', function() {
    if (window.innerWidth <= 768) {
      document.querySelector('.header-actions') && (document.querySelector('.header-actions').style.display = 'none');
      document.querySelector('.logo') && (document.querySelector('.logo').style.display = 'none');
    }
  });

  searchInput.addEventListener('blur', function() {
    setTimeout(() => {
      if (document.querySelector('.header-actions')) document.querySelector('.header-actions').style.display = '';
      if (document.querySelector('.logo')) document.querySelector('.logo').style.display = '';
    }, 150);
  });
}

// ========================
// SWIPEABLE MODALS
// ========================
function setupSwipeableModals() {
  document.querySelectorAll('.modal, .modal-content').forEach(modal => {
    let startY = 0;
    modal.addEventListener('touchstart', e => { startY = e.touches[0].clientY; }, { passive: true });
    modal.addEventListener('touchmove', e => {
      const dy = e.touches[0].clientY - startY;
      if (dy > 0 && e.target === modal) modal.style.transform = `translateY(${Math.min(dy, 200)}px)`;
    }, { passive: true });
    modal.addEventListener('touchend', e => {
      const dy = e.changedTouches[0].clientY - startY;
      if (dy > 100) {
        const overlay = modal.closest('.modal-overlay');
        if (overlay) overlay.classList.remove('open');
        modal.style.transform = '';
      } else modal.style.transform = '';
    }, { passive: true });
  });
}

// ========================
// RIPPLE ANIMATION CSS
// ========================
function injectRippleCSS() {
  if (document.getElementById('mobileRippleStyle')) return;
  const s = document.createElement('style');
  s.id = 'mobileRippleStyle';
  s.textContent = '@keyframes ripple{to{transform:scale(4);opacity:0}}';
  document.head.appendChild(s);
}

// ========================
// FLASH SALE HORIZONTAL SCROLL ON MOBILE
// ========================
function makeFlashSaleScrollable() {
  if (window.innerWidth > 768) return;
  const grids = document.querySelectorAll('#flash-sale .products-grid');
  grids.forEach(grid => {
    grid.style.cssText = `display:flex!important;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;gap:10px;padding:10px 12px`;
    grid.querySelectorAll('.product-card').forEach(card => {
      card.style.minWidth = '150px';
      card.style.flex = '0 0 auto';
    });
  });
}

// ========================
// INIT
// ========================
function init() {
  injectRippleCSS();
  injectBottomNav();
  
  setupPullToRefresh();
  lazyLoadImages();
  enhanceSearch();

  if (isMobile) {
    enhanceProductCards();
    makeFlashSaleScrollable();
  }

  // Update nav cart badge whenever cart changes
  window.addEventListener('storage', updateNavCartBadge);
  updateNavCartBadge();

  // Re-run on dynamic content
  const observer = new MutationObserver(() => {
    enhanceProductCards();
    lazyLoadImages();
    setupSwipeableModals();
    updateNavCartBadge();
  });
  observer.observe(document.body, { childList: true, subtree: true });

  // Add bottom nav CSS transition
  const nav = document.getElementById('mobileBottomNav');
  if (nav) nav.style.transition = 'transform .2s';

  // Show mobile-only elements on small screens
  if (window.innerWidth <= 768) {
    document.querySelectorAll('.mobile-only').forEach(el => {
      el.style.display = el.style.display === 'none' ? 'block' : el.style.display;
    });
  }
}

// Run on DOM ready
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();

})();
