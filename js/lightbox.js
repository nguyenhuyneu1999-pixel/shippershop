// ShipperShop Universal Image Lightbox
// Swipe left/right for multi-image, pinch zoom, tap to close
(function(){
var _lbIdx=0, _lbImgs=[], _lbOv=null, _lbStartX=0, _lbStartY=0;

window.openLb = function(src, postId, imgs) {
    _lbImgs = imgs && imgs.length ? imgs : [src];
    _lbIdx = _lbImgs.indexOf(src);
    if (_lbIdx < 0) _lbIdx = 0;
    showLb();
};

function showLb() {
    if (_lbOv) { updateLb(); return; }
    _lbOv = document.createElement('div');
    _lbOv.id = 'ssLightbox';
    _lbOv.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.95);z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;touch-action:pan-y';
    
    _lbOv.innerHTML = '<div style="position:absolute;top:0;left:0;right:0;display:flex;justify-content:space-between;align-items:center;padding:12px 16px;z-index:2">'
        + '<span id="lbCounter" style="color:#fff;font-size:13px;opacity:.8"></span>'
        + '<button onclick="closeLb()" style="background:none;border:none;color:#fff;font-size:24px;cursor:pointer;padding:8px"><i class="fas fa-times"></i></button>'
        + '</div>'
        + '<img id="lbImg" style="max-width:100%;max-height:85vh;object-fit:contain;user-select:none;transition:opacity .2s" onclick="event.stopPropagation()">'
        + '<div style="position:absolute;bottom:0;left:0;right:0;display:flex;justify-content:center;gap:6px;padding:16px" id="lbDots"></div>';
    
    _lbOv.addEventListener('click', function(e) { if (e.target === _lbOv) closeLb(); });
    
    // Swipe support
    _lbOv.addEventListener('touchstart', function(e) {
        _lbStartX = e.touches[0].clientX;
        _lbStartY = e.touches[0].clientY;
    }, {passive: true});
    
    _lbOv.addEventListener('touchend', function(e) {
        var dx = e.changedTouches[0].clientX - _lbStartX;
        var dy = e.changedTouches[0].clientY - _lbStartY;
        if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) {
            if (dx < 0 && _lbIdx < _lbImgs.length - 1) { _lbIdx++; updateLb(); }
            else if (dx > 0 && _lbIdx > 0) { _lbIdx--; updateLb(); }
        } else if (Math.abs(dy) > 80) { closeLb(); }
    }, {passive: true});
    
    // Keyboard
    document.addEventListener('keydown', lbKey);
    
    document.body.appendChild(_lbOv);
    document.body.style.overflow = 'hidden';
    updateLb();
}

function updateLb() {
    var img = document.getElementById('lbImg');
    if (!img) return;
    img.style.opacity = '0';
    setTimeout(function() {
        img.src = _lbImgs[_lbIdx];
        img.style.opacity = '1';
    }, 100);
    
    var counter = document.getElementById('lbCounter');
    if (counter) counter.textContent = _lbImgs.length > 1 ? (_lbIdx + 1) + ' / ' + _lbImgs.length : '';
    
    var dots = document.getElementById('lbDots');
    if (dots && _lbImgs.length > 1) {
        dots.innerHTML = _lbImgs.map(function(_, i) {
            return '<div style="width:6px;height:6px;border-radius:50%;background:' + (i === _lbIdx ? '#fff' : 'rgba(255,255,255,.4)') + '"></div>';
        }).join('');
    } else if (dots) { dots.innerHTML = ''; }
}

window.closeLb = function() {
    if (_lbOv) { _lbOv.remove(); _lbOv = null; }
    document.body.style.overflow = '';
    document.removeEventListener('keydown', lbKey);
};

function lbKey(e) {
    if (e.key === 'Escape') closeLb();
    else if (e.key === 'ArrowRight' && _lbIdx < _lbImgs.length - 1) { _lbIdx++; updateLb(); }
    else if (e.key === 'ArrowLeft' && _lbIdx > 0) { _lbIdx--; updateLb(); }
}
})();
