/**
 * ShipperShop Component — Upload
 * Multi-file upload with preview, compress, validate
 */
window.SS = window.SS || {};

SS.Upload = {
  _instances: {},

  init: function(inputId, previewId, opts) {
    opts = opts || {};
    var instance = {
      inputEl: document.getElementById(inputId),
      previewEl: document.getElementById(previewId),
      files: [],
      maxFiles: opts.maxFiles || 10,
      maxSizeMB: opts.maxSizeMB || 5,
      acceptTypes: opts.acceptTypes || ['image/jpeg','image/png','image/webp','image/gif'],
      resizeMax: opts.resizeMax || 1920,
      onChange: opts.onChange || null
    };
    if (!instance.inputEl) return;
    SS.Upload._instances[inputId] = instance;

    instance.inputEl.addEventListener('change', function() {
      SS.Upload.handle(inputId, this.files);
    });

    return instance;
  },

  handle: function(inputId, fileList) {
    var inst = SS.Upload._instances[inputId];
    if (!inst) return;

    for (var i = 0; i < fileList.length; i++) {
      if (inst.files.length >= inst.maxFiles) {
        SS.ui.toast('Tối đa ' + inst.maxFiles + ' ảnh', 'warning');
        break;
      }
      var f = fileList[i];
      // Validate type
      if (inst.acceptTypes.indexOf(f.type) === -1) {
        SS.ui.toast(f.name + ': loại file không hợp lệ', 'error');
        continue;
      }
      // Validate size
      if (f.size > inst.maxSizeMB * 1024 * 1024) {
        SS.ui.toast(f.name + ': quá ' + inst.maxSizeMB + 'MB', 'error');
        continue;
      }
      inst.files.push(f);
      SS.Upload._addPreview(inputId, f, inst.files.length - 1);
    }

    if (inst.onChange) inst.onChange(inst.files);
    // Reset input so same file can be re-selected
    inst.inputEl.value = '';
  },

  _addPreview: function(inputId, file, index) {
    var inst = SS.Upload._instances[inputId];
    if (!inst || !inst.previewEl) return;

    var wrap = document.createElement('div');
    wrap.className = 'upload-thumb';
    wrap.style.cssText = 'position:relative;display:inline-block;width:80px;height:80px;margin:4px;border-radius:8px;overflow:hidden;border:1px solid var(--border)';
    wrap.setAttribute('data-index', index);

    if (file.type.indexOf('image') === 0) {
      var img = document.createElement('img');
      img.style.cssText = 'width:100%;height:100%;object-fit:cover';
      var reader = new FileReader();
      reader.onload = function(e) { img.src = e.target.result; };
      reader.readAsDataURL(file);
      wrap.appendChild(img);
    } else {
      var icon = document.createElement('div');
      icon.style.cssText = 'width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg);font-size:24px;color:var(--text-muted)';
      icon.innerHTML = '<i class="fa-solid fa-file"></i>';
      wrap.appendChild(icon);
    }

    // Remove button
    var rmBtn = document.createElement('button');
    rmBtn.style.cssText = 'position:absolute;top:2px;right:2px;width:20px;height:20px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:50%;font-size:10px;cursor:pointer;display:flex;align-items:center;justify-content:center';
    rmBtn.innerHTML = '✕';
    rmBtn.onclick = function() {
      SS.Upload.remove(inputId, index);
    };
    wrap.appendChild(rmBtn);

    inst.previewEl.appendChild(wrap);
  },

  remove: function(inputId, index) {
    var inst = SS.Upload._instances[inputId];
    if (!inst) return;
    inst.files.splice(index, 1);
    SS.Upload._refreshPreviews(inputId);
    if (inst.onChange) inst.onChange(inst.files);
  },

  _refreshPreviews: function(inputId) {
    var inst = SS.Upload._instances[inputId];
    if (!inst || !inst.previewEl) return;
    inst.previewEl.innerHTML = '';
    for (var i = 0; i < inst.files.length; i++) {
      SS.Upload._addPreview(inputId, inst.files[i], i);
    }
  },

  getFiles: function(inputId) {
    var inst = SS.Upload._instances[inputId];
    return inst ? inst.files : [];
  },

  clear: function(inputId) {
    var inst = SS.Upload._instances[inputId];
    if (!inst) return;
    inst.files = [];
    if (inst.previewEl) inst.previewEl.innerHTML = '';
    if (inst.onChange) inst.onChange([]);
  },

  // Build FormData with files
  toFormData: function(inputId, fieldName) {
    fieldName = fieldName || 'images';
    var fd = new FormData();
    var files = SS.Upload.getFiles(inputId);
    for (var i = 0; i < files.length; i++) {
      fd.append(fieldName + '[]', files[i]);
    }
    return fd;
  }
};
