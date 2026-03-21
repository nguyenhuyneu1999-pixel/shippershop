/**
 * ShipperShop Component — Image Compressor
 * Client-side image compression using Canvas API
 * Reduces file size before upload, configurable quality + max dimensions
 */
window.SS = window.SS || {};

SS.ImageCompress = {

  // Compress a File object, returns Promise<Blob>
  compress: function(file, opts) {
    opts = opts || {};
    var maxWidth = opts.maxWidth || 1200;
    var maxHeight = opts.maxHeight || 1200;
    var quality = opts.quality || 0.8;
    var type = opts.type || 'image/jpeg';

    return new Promise(function(resolve, reject) {
      if (!file || !file.type.startsWith('image/')) {
        resolve(file); return;
      }
      // Skip if already small enough
      if (file.size < (opts.skipBelow || 102400)) {
        resolve(file); return;
      }

      var reader = new FileReader();
      reader.onload = function(e) {
        var img = new Image();
        img.onload = function() {
          var w = img.width;
          var h = img.height;

          // Scale down if needed
          if (w > maxWidth || h > maxHeight) {
            var ratio = Math.min(maxWidth / w, maxHeight / h);
            w = Math.round(w * ratio);
            h = Math.round(h * ratio);
          }

          var canvas = document.createElement('canvas');
          canvas.width = w;
          canvas.height = h;
          var ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, w, h);

          canvas.toBlob(function(blob) {
            if (blob) {
              // Only use compressed version if actually smaller
              if (blob.size < file.size) {
                var compressed = new File([blob], file.name.replace(/\.\w+$/, '.jpg'), {type: type});
                resolve(compressed);
              } else {
                resolve(file);
              }
            } else {
              resolve(file);
            }
          }, type, quality);
        };
        img.onerror = function() { resolve(file); };
        img.src = e.target.result;
      };
      reader.onerror = function() { resolve(file); };
      reader.readAsDataURL(file);
    });
  },

  // Compress multiple files
  compressAll: function(files, opts) {
    var promises = [];
    for (var i = 0; i < files.length; i++) {
      promises.push(SS.ImageCompress.compress(files[i], opts));
    }
    return Promise.all(promises);
  },

  // Compress + create FormData ready for upload
  prepareUpload: function(files, fieldName, opts) {
    fieldName = fieldName || 'file';
    return SS.ImageCompress.compressAll(files, opts).then(function(compressed) {
      var fd = new FormData();
      for (var i = 0; i < compressed.length; i++) {
        fd.append(fieldName, compressed[i]);
      }
      return fd;
    });
  },

  // Get estimated savings text
  formatSavings: function(originalSize, compressedSize) {
    var saved = originalSize - compressedSize;
    var pct = Math.round(saved / originalSize * 100);
    return SS.utils.formatFileSize(compressedSize) + ' (' + (pct > 0 ? '-' + pct + '%' : 'no change') + ')';
  }
};
