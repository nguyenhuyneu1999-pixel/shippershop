/**
 * ShipperShop Core — UI Components
 * Toast, Modal, Bottom Sheet, Loading, Skeleton, Confirm
 */
window.SS = window.SS || {};

SS.ui = {

  // ========== TOAST ==========
  _toastContainer: null,

  toast: function(msg, type, duration) {
    type = type || 'info';
    duration = duration || 3000;
    if (!SS.ui._toastContainer) {
      SS.ui._toastContainer = document.createElement('div');
      SS.ui._toastContainer.className = 'toast-container';
      document.body.appendChild(SS.ui._toastContainer);
    }
    var el = document.createElement('div');
    el.className = 'toast toast-' + type;
    el.textContent = msg;
    SS.ui._toastContainer.appendChild(el);
    setTimeout(function() {
      el.style.opacity = '0';
      el.style.transition = 'opacity .3s';
      setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 300);
    }, duration);
  },

  // ========== MODAL ==========
  _modalEl: null,

  modal: function(opts) {
    // opts: {title, body, html, onConfirm, onCancel, confirmText, cancelText, danger}
    if (SS.ui._modalEl) SS.ui.closeModal();

    var ov = document.createElement('div');
    ov.className = 'modal-overlay active';
    ov.onclick = function(e) { if (e.target === ov) SS.ui.closeModal(); };

    var m = document.createElement('div');
    m.className = 'modal';

    // Header
    var hdr = document.createElement('div');
    hdr.className = 'modal-header';
    var h3 = document.createElement('h3');
    h3.textContent = opts.title || '';
    hdr.appendChild(h3);
    var closeBtn = document.createElement('button');
    closeBtn.className = 'btn btn-icon btn-ghost';
    closeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    closeBtn.onclick = SS.ui.closeModal;
    hdr.appendChild(closeBtn);
    m.appendChild(hdr);

    // Body
    var bd = document.createElement('div');
    bd.className = 'modal-body';
    if (opts.html) {
      bd.innerHTML = opts.html;
    } else if (opts.body) {
      bd.textContent = opts.body;
    }
    m.appendChild(bd);

    // Footer
    if (opts.onConfirm || opts.confirmText) {
      var ft = document.createElement('div');
      ft.className = 'modal-footer';
      var cancelBtn = document.createElement('button');
      cancelBtn.className = 'btn btn-ghost';
      cancelBtn.textContent = opts.cancelText || 'Hủy';
      cancelBtn.onclick = function() {
        if (opts.onCancel) opts.onCancel();
        SS.ui.closeModal();
      };
      ft.appendChild(cancelBtn);

      var okBtn = document.createElement('button');
      okBtn.className = 'btn ' + (opts.danger ? 'btn-danger' : 'btn-primary');
      okBtn.textContent = opts.confirmText || 'Xác nhận';
      okBtn.onclick = function() {
        if (opts.onConfirm) opts.onConfirm();
        SS.ui.closeModal();
      };
      ft.appendChild(okBtn);
      m.appendChild(ft);
    }

    ov.appendChild(m);
    document.body.appendChild(ov);
    SS.ui._modalEl = ov;
    document.body.style.overflow = 'hidden';
  },

  closeModal: function() {
    if (SS.ui._modalEl) {
      document.body.removeChild(SS.ui._modalEl);
      SS.ui._modalEl = null;
      document.body.style.overflow = '';
    }
  },

  // ========== CONFIRM ==========
  confirm: function(msg, onYes, opts) {
    opts = opts || {};
    SS.ui.modal({
      title: opts.title || 'Xác nhận',
      body: msg,
      confirmText: opts.confirmText || 'Đồng ý',
      cancelText: opts.cancelText || 'Hủy',
      danger: opts.danger || false,
      onConfirm: onYes
    });
  },

  // ========== BOTTOM SHEET ==========
  _sheetEl: null,
  _sheetOverlay: null,

  sheet: function(opts) {
    // opts: {title, html, onClose, maxHeight}
    if (SS.ui._sheetEl) SS.ui.closeSheet();

    var ov = document.createElement('div');
    ov.className = 'modal-overlay active';
    ov.style.alignItems = 'flex-end';
    ov.onclick = function(e) { if (e.target === ov) SS.ui.closeSheet(); };

    var sh = document.createElement('div');
    sh.className = 'bottom-sheet active';
    sh.style.maxHeight = opts.maxHeight || '80vh';
    sh.style.position = 'relative';

    // Handle
    var handle = document.createElement('div');
    handle.className = 'sheet-handle';
    sh.appendChild(handle);

    // Header
    if (opts.title) {
      var hdr = document.createElement('div');
      hdr.className = 'sheet-header';
      var h3 = document.createElement('h3');
      h3.textContent = opts.title;
      hdr.appendChild(h3);
      var closeBtn = document.createElement('button');
      closeBtn.className = 'btn btn-icon btn-ghost';
      closeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
      closeBtn.onclick = SS.ui.closeSheet;
      hdr.appendChild(closeBtn);
      sh.appendChild(hdr);
    }

    // Body
    var bd = document.createElement('div');
    bd.className = 'sheet-body';
    if (opts.html) bd.innerHTML = opts.html;
    sh.appendChild(bd);

    ov.appendChild(sh);
    document.body.appendChild(ov);
    SS.ui._sheetEl = sh;
    SS.ui._sheetOverlay = ov;
    document.body.style.overflow = 'hidden';

    return bd; // Return body element for dynamic content
  },

  closeSheet: function() {
    if (SS.ui._sheetOverlay) {
      if (SS.ui._sheetEl) SS.ui._sheetEl.style.transform = 'translateY(100%)';
      setTimeout(function() {
        if (SS.ui._sheetOverlay && SS.ui._sheetOverlay.parentNode) {
          document.body.removeChild(SS.ui._sheetOverlay);
        }
        SS.ui._sheetEl = null;
        SS.ui._sheetOverlay = null;
        document.body.style.overflow = '';
      }, 300);
    }
  },

  // ========== LOADING ==========
  _loadingEl: null,

  loading: function(show) {
    if (show) {
      if (SS.ui._loadingEl) return;
      var el = document.createElement('div');
      el.className = 'modal-overlay active';
      el.style.background = 'rgba(0,0,0,.3)';
      el.innerHTML = '<div style="width:40px;height:40px;border:3px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .8s linear infinite"></div>';
      document.body.appendChild(el);
      SS.ui._loadingEl = el;
    } else {
      if (SS.ui._loadingEl) {
        document.body.removeChild(SS.ui._loadingEl);
        SS.ui._loadingEl = null;
      }
    }
  },

  // ========== SKELETON ==========
  skeleton: function(container, type, count) {
    count = count || 3;
    var html = '';
    for (var i = 0; i < count; i++) {
      if (type === 'post') {
        html += '<div class="card mb-3 p-4"><div class="flex gap-3 mb-3"><div class="skeleton skeleton-avatar"></div><div class="flex-1"><div class="skeleton skeleton-text" style="width:40%"></div><div class="skeleton skeleton-text" style="width:25%"></div></div></div><div class="skeleton skeleton-text"></div><div class="skeleton skeleton-text" style="width:70%"></div><div class="skeleton skeleton-image mt-3" style="height:160px"></div></div>';
      } else if (type === 'chat') {
        html += '<div class="list-item"><div class="skeleton skeleton-avatar"></div><div class="flex-1"><div class="skeleton skeleton-text" style="width:50%"></div><div class="skeleton skeleton-text" style="width:30%"></div></div></div>';
      } else if (type === 'user') {
        html += '<div class="list-item"><div class="skeleton skeleton-avatar"></div><div class="flex-1"><div class="skeleton skeleton-text" style="width:40%"></div><div class="skeleton skeleton-text" style="width:60%"></div></div></div>';
      } else {
        html += '<div class="skeleton skeleton-card mb-3"><div class="skeleton skeleton-text"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>';
      }
    }
    if (typeof container === 'string') container = document.getElementById(container);
    if (container) container.innerHTML = html;
  },

  // ========== DROPDOWN ==========
  dropdown: function(triggerEl, items) {
    // items: [{icon, label, onClick, danger}]
    var existing = triggerEl.querySelector('.dropdown');
    if (existing) { existing.parentNode.removeChild(existing); return; }

    // Close others
    document.querySelectorAll('.dropdown.active').forEach(function(d) { d.parentNode.removeChild(d); });

    var dd = document.createElement('div');
    dd.className = 'dropdown active';
    dd.style.top = (triggerEl.offsetHeight + 4) + 'px';
    dd.style.right = '0';

    items.forEach(function(item) {
      if (item === 'divider') {
        var div = document.createElement('div');
        div.className = 'dropdown-divider';
        dd.appendChild(div);
        return;
      }
      var di = document.createElement('div');
      di.className = 'dropdown-item' + (item.danger ? ' danger' : '');
      di.innerHTML = (item.icon ? '<i class="fa-solid ' + item.icon + '"></i>' : '') + item.label;
      di.onclick = function(e) {
        e.stopPropagation();
        dd.parentNode.removeChild(dd);
        if (item.onClick) item.onClick();
      };
      dd.appendChild(di);
    });

    triggerEl.style.position = 'relative';
    triggerEl.appendChild(dd);

    // Close on outside click
    setTimeout(function() {
      var handler = function(e) {
        if (!dd.contains(e.target)) {
          if (dd.parentNode) dd.parentNode.removeChild(dd);
          document.removeEventListener('click', handler);
        }
      };
      document.addEventListener('click', handler);
    }, 0);
  }
};
