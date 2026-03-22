/**
 * ShipperShop Component — Floating scroll-to-top button
 */
window.SS = window.SS || {};

SS.ScrollTop = {
  init: function() {
    var btn = document.createElement('div');
    btn.id = 'ss-scroll-top';
    btn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';
    btn.style.cssText = 'position:fixed;bottom:80px;right:16px;width:40px;height:40px;border-radius:50%;background:var(--primary);color:#fff;display:none;align-items:center;justify-content:center;cursor:pointer;z-index:999;box-shadow:0 2px 8px rgba(0,0,0,.2)';
    btn.onclick = function() { window.scrollTo({top: 0, behavior: 'smooth'}); };
    document.body.appendChild(btn);
    window.addEventListener('scroll', function() { btn.style.display = window.scrollY > 300 ? 'flex' : 'none'; });
  }
};
