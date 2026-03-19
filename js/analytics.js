// ShipperShop Simple Analytics
(function(){
  try {
    var page = location.pathname.replace(/\//g,'').replace('.html','') || 'home';
    var ref = document.referrer || 'direct';
    var src = new URLSearchParams(location.search).get('utm_source') || '';
    var data = {page: page, ref: ref, src: src, t: Date.now()};
    
    // Send to our analytics endpoint (non-blocking)
    var img = new Image();
    img.src = '/api/track.php?p=' + encodeURIComponent(page) + 
              '&r=' + encodeURIComponent(ref.substring(0, 100)) + 
              '&s=' + encodeURIComponent(src) +
              '&_=' + Date.now();
    
    // Track time on page
    var startTime = Date.now();
    window.addEventListener('beforeunload', function() {
      var duration = Math.round((Date.now() - startTime) / 1000);
      navigator.sendBeacon('/api/track.php?p=' + page + '&d=' + duration + '&action=leave');
    });
  } catch(e) {}
})();
