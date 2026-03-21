/**
 * ShipperShop Component — Image Viewer (Lightbox)
 * Fullscreen, swipe left/right, double-tap zoom, pinch zoom
 */
window.SS = window.SS || {};

SS.ImageViewer = {
  _el: null,
  _gallery: [],
  _index: 0,
  _scale: 1,
  _startX: 0,
  _diffX: 0,

  open: function(src, gallery) {
    SS.ImageViewer._gallery = gallery || [src];
    SS.ImageViewer._index = 0;
    SS.ImageViewer._scale = 1;
    for (var i = 0; i < SS.ImageViewer._gallery.length; i++) {
      if (SS.ImageViewer._gallery[i] === src) { SS.ImageViewer._index = i; break; }
    }

    var ov = document.createElement('div');
    ov.id = 'ss-lightbox';
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.95);z-index:2000;display:flex;align-items:center;justify-content:center;flex-direction:column';

    // Close button
    var closeBtn = document.createElement('button');
    closeBtn.style.cssText = 'position:absolute;top:12px;right:12px;background:none;border:none;color:#fff;font-size:24px;cursor:pointer;z-index:10;width:40px;height:40px';
    closeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    closeBtn.onclick = SS.ImageViewer.close;
    ov.appendChild(closeBtn);

    // Counter
    if (SS.ImageViewer._gallery.length > 1) {
      var counter = document.createElement('div');
      counter.id = 'iv-counter';
      counter.style.cssText = 'position:absolute;top:16px;left:50%;transform:translateX(-50%);color:#fff;font-size:14px;z-index:10';
      counter.textContent = (SS.ImageViewer._index + 1) + ' / ' + SS.ImageViewer._gallery.length;
      ov.appendChild(counter);
    }

    // Image container
    var imgWrap = document.createElement('div');
    imgWrap.style.cssText = 'flex:1;display:flex;align-items:center;justify-content:center;width:100%;overflow:hidden';
    var img = document.createElement('img');
    img.id = 'iv-img';
    img.src = SS.ImageViewer._gallery[SS.ImageViewer._index];
    img.style.cssText = 'max-width:100%;max-height:90vh;object-fit:contain;transition:transform .2s;touch-action:none';
    imgWrap.appendChild(img);
    ov.appendChild(imgWrap);

    // Navigation arrows (desktop)
    if (SS.ImageViewer._gallery.length > 1) {
      var prevBtn = document.createElement('button');
      prevBtn.style.cssText = 'position:absolute;left:12px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.2);border:none;color:#fff;width:40px;height:40px;border-radius:50%;cursor:pointer;font-size:18px';
      prevBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
      prevBtn.onclick = SS.ImageViewer.prev;
      ov.appendChild(prevBtn);

      var nextBtn = document.createElement('button');
      nextBtn.style.cssText = 'position:absolute;right:12px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.2);border:none;color:#fff;width:40px;height:40px;border-radius:50%;cursor:pointer;font-size:18px';
      nextBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
      nextBtn.onclick = SS.ImageViewer.next;
      ov.appendChild(nextBtn);
    }

    document.body.appendChild(ov);
    document.body.style.overflow = 'hidden';
    SS.ImageViewer._el = ov;

    // Touch events for swipe
    imgWrap.addEventListener('touchstart', SS.ImageViewer._onTouchStart, {passive: true});
    imgWrap.addEventListener('touchmove', SS.ImageViewer._onTouchMove, {passive: true});
    imgWrap.addEventListener('touchend', SS.ImageViewer._onTouchEnd);

    // Double tap zoom
    var lastTap = 0;
    imgWrap.addEventListener('click', function() {
      var now = Date.now();
      if (now - lastTap < 300) {
        SS.ImageViewer._scale = SS.ImageViewer._scale > 1 ? 1 : 2.5;
        img.style.transform = 'scale(' + SS.ImageViewer._scale + ')';
      }
      lastTap = now;
    });

    // Keyboard
    document.addEventListener('keydown', SS.ImageViewer._onKey);

    // Click overlay to close
    ov.addEventListener('click', function(e) {
      if (e.target === ov || e.target === imgWrap) SS.ImageViewer.close();
    });
  },

  close: function() {
    if (SS.ImageViewer._el) {
      document.body.removeChild(SS.ImageViewer._el);
      SS.ImageViewer._el = null;
      document.body.style.overflow = '';
      document.removeEventListener('keydown', SS.ImageViewer._onKey);
    }
  },

  next: function() {
    if (SS.ImageViewer._gallery.length <= 1) return;
    SS.ImageViewer._index = (SS.ImageViewer._index + 1) % SS.ImageViewer._gallery.length;
    SS.ImageViewer._updateImg();
  },

  prev: function() {
    if (SS.ImageViewer._gallery.length <= 1) return;
    SS.ImageViewer._index = (SS.ImageViewer._index - 1 + SS.ImageViewer._gallery.length) % SS.ImageViewer._gallery.length;
    SS.ImageViewer._updateImg();
  },

  _updateImg: function() {
    var img = document.getElementById('iv-img');
    var counter = document.getElementById('iv-counter');
    if (img) { img.src = SS.ImageViewer._gallery[SS.ImageViewer._index]; img.style.transform = 'scale(1)'; }
    if (counter) counter.textContent = (SS.ImageViewer._index + 1) + ' / ' + SS.ImageViewer._gallery.length;
    SS.ImageViewer._scale = 1;
  },

  _onTouchStart: function(e) {
    if (e.touches.length === 1) SS.ImageViewer._startX = e.touches[0].clientX;
  },

  _onTouchMove: function(e) {
    if (e.touches.length === 1 && SS.ImageViewer._scale <= 1) {
      SS.ImageViewer._diffX = e.touches[0].clientX - SS.ImageViewer._startX;
    }
  },

  _onTouchEnd: function() {
    if (Math.abs(SS.ImageViewer._diffX) > 60 && SS.ImageViewer._scale <= 1) {
      if (SS.ImageViewer._diffX > 0) SS.ImageViewer.prev();
      else SS.ImageViewer.next();
    }
    SS.ImageViewer._diffX = 0;
  },

  _onKey: function(e) {
    if (e.key === 'Escape') SS.ImageViewer.close();
    if (e.key === 'ArrowLeft') SS.ImageViewer.prev();
    if (e.key === 'ArrowRight') SS.ImageViewer.next();
  }
};
