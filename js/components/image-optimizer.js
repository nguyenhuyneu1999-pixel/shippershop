/**
 * ShipperShop Component — Image Optimizer
 * Client-side image resize + compress before upload
 * Reduces bandwidth and storage, maintains quality
 */
window.SS = window.SS || {};

SS.ImageOptimizer = {
  defaults: {
    maxWidth: 1200,
    maxHeight: 1200,
    quality: 0.82,
    format: 'image/jpeg'
  },

  // Optimize a File object, returns Promise<Blob>
  optimize: function(file, opts) {
    opts = opts || {};
    var maxW = opts.maxWidth || SS.ImageOptimizer.defaults.maxWidth;
    var maxH = opts.maxHeight || SS.ImageOptimizer.defaults.maxHeight;
    var quality = opts.quality || SS.ImageOptimizer.defaults.quality;
    var format = opts.format || SS.ImageOptimizer.defaults.format;

    // Skip non-images and GIFs (preserve animation)
    if (!file.type.startsWith('image/') || file.type === 'image/gif') {
      return Promise.resolve(file);
    }
    // Skip small files (< 200KB)
    if (file.size < 200 * 1024) {
      return Promise.resolve(file);
    }

    return new Promise(function(resolve) {
      var img = new Image();
      img.onload = function() {
        var w = img.width;
        var h = img.height;

        // Calculate new dimensions
        if (w > maxW || h > maxH) {
          var ratio = Math.min(maxW / w, maxH / h);
          w = Math.round(w * ratio);
          h = Math.round(h * ratio);
        }

        // Draw to canvas
        var canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        var ctx = canvas.getContext('2d');

        // Use better quality scaling
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(img, 0, 0, w, h);

        // Convert to blob
        canvas.toBlob(function(blob) {
          if (blob && blob.size < file.size) {
            resolve(blob);
          } else {
            resolve(file); // Original was smaller
          }
        }, format, quality);
      };
      img.onerror = function() { resolve(file); };
      img.src = URL.createObjectURL(file);
    });
  },

  // Optimize multiple files
  optimizeAll: function(files, opts) {
    var promises = [];
    for (var i = 0; i < files.length; i++) {
      promises.push(SS.ImageOptimizer.optimize(files[i], opts));
    }
    return Promise.all(promises);
  },

  // Get file size display
  formatSize: function(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  }
};
