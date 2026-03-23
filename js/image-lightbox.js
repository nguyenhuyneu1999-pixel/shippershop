// ShipperShop Image Lightbox — pinch zoom + pan
// Usage: openLightbox(imageUrl)

var _lbScale=1,_lbX=0,_lbY=0,_lbStartDist=0,_lbStartScale=1;

function openLightbox(url){
  if(!url)return;
  _lbScale=1;_lbX=0;_lbY=0;
  
  var ov=document.createElement('div');
  ov.id='imgLightbox';
  ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.95);z-index:5000;display:flex;align-items:center;justify-content:center;cursor:zoom-out;touch-action:none';
  
  var img=document.createElement('img');
  img.src=url;
  img.style.cssText='max-width:95vw;max-height:90vh;object-fit:contain;border-radius:4px;transition:transform .1s ease;user-select:none;-webkit-user-drag:none';
  img.draggable=false;
  
  // Close button
  var closeBtn=document.createElement('button');
  closeBtn.innerHTML='&times;';
  closeBtn.style.cssText='position:absolute;top:16px;right:16px;background:rgba(255,255,255,.2);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:24px;cursor:pointer;z-index:5001;backdrop-filter:blur(4px)';
  closeBtn.onclick=function(){ov.remove();};
  
  // Zoom controls
  var controls=document.createElement('div');
  controls.style.cssText='position:absolute;bottom:24px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:5001';
  controls.innerHTML='<button onclick="lbZoom(1.5)" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:18px;cursor:pointer;backdrop-filter:blur(4px)">+</button>'
    +'<button onclick="lbZoom(0.5)" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:18px;cursor:pointer;backdrop-filter:blur(4px)">&minus;</button>'
    +'<button onclick="lbReset()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:14px;cursor:pointer;backdrop-filter:blur(4px)">1:1</button>';
  
  ov.appendChild(img);
  ov.appendChild(closeBtn);
  ov.appendChild(controls);
  document.body.appendChild(ov);
  
  // Click overlay to close (not on img)
  ov.onclick=function(e){if(e.target===ov)ov.remove();};
  
  // Double tap to zoom
  var lastTap=0;
  img.addEventListener('click',function(e){
    var now=Date.now();
    if(now-lastTap<300){
      _lbScale=_lbScale>1?1:2.5;
      _lbX=0;_lbY=0;
      img.style.transform='scale('+_lbScale+') translate('+_lbX+'px,'+_lbY+'px)';
    }
    lastTap=now;
    e.stopPropagation();
  });
  
  // Pinch zoom (touch)
  img.addEventListener('touchstart',function(e){
    if(e.touches.length===2){
      _lbStartDist=Math.hypot(e.touches[0].pageX-e.touches[1].pageX,e.touches[0].pageY-e.touches[1].pageY);
      _lbStartScale=_lbScale;
      e.preventDefault();
    }
  },{passive:false});
  
  img.addEventListener('touchmove',function(e){
    if(e.touches.length===2){
      var dist=Math.hypot(e.touches[0].pageX-e.touches[1].pageX,e.touches[0].pageY-e.touches[1].pageY);
      _lbScale=Math.max(0.5,Math.min(5,_lbStartScale*(dist/_lbStartDist)));
      img.style.transform='scale('+_lbScale+') translate('+_lbX+'px,'+_lbY+'px)';
      e.preventDefault();
    }else if(e.touches.length===1&&_lbScale>1){
      _lbX+=(e.touches[0].pageX-(img._lastX||e.touches[0].pageX))*0.5;
      _lbY+=(e.touches[0].pageY-(img._lastY||e.touches[0].pageY))*0.5;
      img._lastX=e.touches[0].pageX;img._lastY=e.touches[0].pageY;
      img.style.transform='scale('+_lbScale+') translate('+_lbX+'px,'+_lbY+'px)';
      e.preventDefault();
    }
  },{passive:false});
  
  img.addEventListener('touchend',function(){img._lastX=null;img._lastY=null;});
  
  // Mouse wheel zoom
  ov.addEventListener('wheel',function(e){
    e.preventDefault();
    _lbScale=Math.max(0.5,Math.min(5,_lbScale+(e.deltaY>0?-0.3:0.3)));
    img.style.transform='scale('+_lbScale+') translate('+_lbX+'px,'+_lbY+'px)';
  },{passive:false});
  
  // ESC to close
  var escHandler=function(e){if(e.key==='Escape'){ov.remove();document.removeEventListener('keydown',escHandler);}};
  document.addEventListener('keydown',escHandler);
}

function lbZoom(delta){
  _lbScale=Math.max(0.5,Math.min(5,_lbScale+delta));
  var img=document.querySelector('#imgLightbox img');
  if(img)img.style.transform='scale('+_lbScale+') translate('+_lbX+'px,'+_lbY+'px)';
}

function lbReset(){
  _lbScale=1;_lbX=0;_lbY=0;
  var img=document.querySelector('#imgLightbox img');
  if(img)img.style.transform='scale(1)';
}
