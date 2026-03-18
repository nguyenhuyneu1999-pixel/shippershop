/* ============================================================
   SHIPPERSHOP MOBILE.JS v3 - Zero Layout Shift
   Không inject DOM → không giật khi load
   Chỉ xử lý behavior: scroll, ripple, cart badge, search
   ============================================================ */
(function () {
  'use strict';

  /* ---------- utils ---------- */
  function tryJSON(s) { try { return s ? JSON.parse(s) : null; } catch { return null; } }
  function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

  /* ---------- cart badge ---------- */
  function refreshCartBadge() {
    try {
      const cart  = tryJSON(localStorage.getItem('cart')) || [];
      const count = cart.reduce((s, i) => s + (i.quantity || 1), 0);
      // header badge
      const hb = document.getElementById('cartCount');
      if (hb) hb.textContent = count || 0;
      // bottom nav badge
      const nb = document.getElementById('navCartBadge');
      if (nb) { nb.textContent = count > 99 ? '99+' : count; nb.style.display = count > 0 ? 'flex' : 'none'; }
    } catch (e) {}
  }

  /* ---------- active nav item ---------- */
  function setActiveNav() {
    const page = location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.mnav-item[data-page]').forEach(el => {
      el.classList.toggle('active', page === el.dataset.page ||
        (el.dataset.page !== 'index.html' && page.startsWith(el.dataset.page.replace('.html', ''))));
    });
    // Profile: if not logged in, no active needed
  }

  /* ---------- scroll: hide/show nav + back-to-top ---------- */
  function setupScroll() {
    const getNav = () => document.getElementById('mobileBottomNav');
    const getTop = () => document.getElementById('backToTop');
    let lastST = 0, ticking = false;

    window.addEventListener('scroll', () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        const st  = window.scrollY;
        const nav = getNav();
        const btn = getTop();
        if (nav) {
          if (st > lastST + 80 && st > 150)      nav.classList.add('nav-hidden');
          else if (lastST > st + 15 || st < 80)  nav.classList.remove('nav-hidden');
        }
        if (btn) btn.classList.toggle('visible', st > 300);
        lastST  = st;
        ticking = false;
      });
    }, { passive: true });
  }

  /* ---------- back to top button ---------- */
  window.scrollToTop = function () { window.scrollTo({ top: 0, behavior: 'smooth' }); };

  /* ---------- fab action ---------- */
  window.handleFab = function () {
    var page = location.pathname.split('/').pop() || 'index.html';
    if (page.indexOf('index') > -1 || page === '' || page === '/') {
      if (typeof openModal === 'function') openModal('post');
      else if (typeof openSPM === 'function') openSPM();
    } else {
      if (typeof openSPM === 'function') openSPM();
    }
  };

  /* ---------- ripple (event delegation - 1 listener only) ---------- */
  function setupRipples() {
    if (!document.getElementById('ssRippleStyle')) {
      const s = document.createElement('style');
      s.id = 'ssRippleStyle';
      s.textContent = '@keyframes ssRipple{0%{transform:scale(0);opacity:.3}100%{transform:scale(2.5);opacity:0}}';
      document.head.appendChild(s);
    }
    const SEL = '.product-card,.btn,.header-btn,.mnav-item,.mfb-btn,.act-btn,.sort-btn';
    document.addEventListener('touchstart', e => {
      const el = e.target.closest(SEL);
      if (!el) return;
      const cs = getComputedStyle(el);
      if (cs.position === 'static') el.style.position = 'relative';
      el.style.overflow = 'hidden';
      const rect = el.getBoundingClientRect();
      const span = document.createElement('span');
      const d    = Math.max(el.offsetWidth, el.offsetHeight) * 2;
      span.style.cssText = `position:absolute;width:${d}px;height:${d}px;border-radius:50%;`
        + `left:${e.touches[0].clientX - rect.left - d/2}px;`
        + `top:${e.touches[0].clientY - rect.top - d/2}px;`
        + `background:rgba(0,0,0,.06);transform:scale(0);`
        + `animation:ssRipple .4s ease-out;pointer-events:none;`;
      el.appendChild(span);
      span.addEventListener('animationend', () => span.remove(), { once: true });
    }, { passive: true });
  }

  /* ---------- pull to refresh ---------- */
  function setupPullRefresh() {
    const ind = document.getElementById('pullIndicator');
    if (!ind) return; // HTML element required
    let sy = 0, dist = 0, pulling = false, triggered = false;
    const THRESH = 70;
    document.addEventListener('touchstart', e => {
      if (window.scrollY === 0 && !triggered) { sy = e.touches[0].clientY; pulling = true; }
    }, { passive: true });
    document.addEventListener('touchmove', e => {
      if (!pulling || triggered) return;
      dist = Math.max(0, e.touches[0].clientY - sy);
      const p = Math.min(dist * 0.5, THRESH);
      ind.style.transform = `translateY(${p - 56}px)`;
      ind.querySelector('span').textContent = p >= THRESH - 4 ? 'Thả để tải lại' : 'Kéo xuống để làm mới';
    }, { passive: true });
    document.addEventListener('touchend', () => {
      if (!pulling) return;
      pulling = false;
      if (dist * 0.5 >= THRESH - 4 && !triggered) {
        triggered = true;
        ind.style.transform = 'translateY(0)';
        ind.querySelector('span').textContent = '🔄 Đang tải lại...';
        setTimeout(() => location.reload(), 500);
      } else {
        ind.style.transform = 'translateY(-56px)';
      }
      dist = 0;
    }, { passive: true });
  }

  /* ---------- search expand ---------- */
  function setupSearch() {
    if (window.innerWidth > 768) return;
    const inp     = document.getElementById('searchInput') || document.querySelector('.search-bar input');
    const logo    = document.querySelector('.logo');
    const actions = document.querySelector('.header-actions');
    if (!inp) return;
    inp.addEventListener('focus',  () => { if (logo) logo.style.visibility='hidden'; if (actions) actions.style.visibility='hidden'; }, { passive:true });
    inp.addEventListener('blur',   () => { setTimeout(() => { if (logo) logo.style.visibility=''; if (actions) actions.style.visibility=''; }, 180); }, { passive:true });
  }

  /* ---------- lazy images ---------- */
  function setupLazy() {
    if (!('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { const img = e.target; if (img.dataset.src) { img.src = img.dataset.src; delete img.dataset.src; io.unobserve(img); } } });
    }, { rootMargin: '300px' });
    document.querySelectorAll('img[data-src]').forEach(i => io.observe(i));
  }

  /* ---------- flash sale h-scroll ---------- */
  function flashSaleHScroll() {
    if (window.innerWidth > 768) return;
    document.querySelectorAll('#flash-sale .products-grid, #flashSaleGrid').forEach(g => {
      if (g.dataset.hs) return;
      g.dataset.hs = '1';
      g.style.cssText += ';display:flex!important;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;scroll-snap-type:x mandatory;gap:10px;padding:0 12px 12px';
      g.querySelectorAll('.product-card').forEach(c => { c.style.cssText += ';min-width:148px;flex:0 0 auto;scroll-snap-align:start'; });
    });
  }

  /* ---------- mutation observer (debounced) ---------- */
  function setupObserver() {
    const refresh = debounce(() => { refreshCartBadge(); flashSaleHScroll(); setupLazy(); }, 400);
    new MutationObserver(refresh).observe(document.body, { childList: true, subtree: true });
  }

  /* ---------- init ---------- */
  /* ---------- hide nav on auth pages ---------- */
  function handleAuthPage() {
    if (!document.querySelector('.auth-page')) return;
    const nav = document.getElementById('mobileBottomNav');
    const btn = document.getElementById('backToTop');
    const ind = document.getElementById('pullIndicator');
    if (nav) nav.style.display = 'none';
    if (btn) btn.style.display = 'none';
    if (ind) ind.style.display = 'none';
    document.body.style.paddingBottom = '0';
  }

  function init() {
    handleAuthPage();
    setActiveNav();
    refreshCartBadge();
    setupScroll();
    setupRipples();
    setupPullRefresh();
    setupSearch();
    setupLazy();
    flashSaleHScroll();
    setupObserver();
    window.addEventListener('storage', refreshCartBadge, { passive: true });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();


  /* ---------- smooth page transitions ---------- */
  // Fade-in on load
  document.body.classList.add('page-enter');
  document.body.addEventListener('animationend', function() {
    document.body.classList.remove('page-enter');
  }, { once: true });

  // Intercept nav clicks for smooth fade-out
  document.addEventListener('click', function(e) {
    var link = e.target.closest('#mobileBottomNav a[href], .mk-bnav a[href], .map-bnav a[href]');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!href || href === '#' || href.startsWith('javascript')) return;
    // Don't animate if same page
    var current = location.pathname.split('/').pop() || 'index.html';
    if (href === current) { e.preventDefault(); return; }
    e.preventDefault();
    document.body.classList.add('page-exit');
    setTimeout(function() { location.href = href; }, 120);
  }, true);

})();
