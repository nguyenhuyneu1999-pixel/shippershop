// ShipperShop Image Lightbox — zoom cover/avatar
// Usage: zoomImage(src) or onclick="zoomImage(this.src)"

function zoomImage(src){
  if(!src)return;
  var ov=document.createElement("div");
  ov.style.cssText="position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;animation:fadeIn .2s";
  
  var img=document.createElement("img");
  img.src=src;
  img.style.cssText="max-width:95vw;max-height:90vh;object-fit:contain;border-radius:8px;transform:scale(1);transition:transform .3s";
  
  var scale=1;
  var close=function(){ov.style.opacity="0";setTimeout(function(){ov.remove();},200);};
  
  // Pinch zoom (mobile)
  var startDist=0;
  img.addEventListener("touchstart",function(e){
    if(e.touches.length===2){
      startDist=Math.hypot(e.touches[0].clientX-e.touches[1].clientX,e.touches[0].clientY-e.touches[1].clientY);
    }
  });
  img.addEventListener("touchmove",function(e){
    if(e.touches.length===2){
      e.preventDefault();
      var dist=Math.hypot(e.touches[0].clientX-e.touches[1].clientX,e.touches[0].clientY-e.touches[1].clientY);
      scale=Math.max(0.5,Math.min(4,scale*(dist/startDist)));
      img.style.transform="scale("+scale+")";
      startDist=dist;
    }
  },{passive:false});
  
  // Double tap zoom
  var lastTap=0;
  img.addEventListener("touchend",function(e){
    if(e.touches.length>0)return;
    var now=Date.now();
    if(now-lastTap<300){
      scale=scale>1.5?1:2.5;
      img.style.transform="scale("+scale+")";
    }else if(scale<=1){
      // Single tap close only if not zoomed
      setTimeout(function(){if(Date.now()-lastTap>290)close();},300);
    }
    lastTap=now;
  });
  
  // Mouse wheel zoom (desktop)
  img.addEventListener("wheel",function(e){
    e.preventDefault();
    scale=Math.max(0.5,Math.min(4,scale+(e.deltaY>0?-0.3:0.3)));
    img.style.transform="scale("+scale+")";
  },{passive:false});
  
  // Click background to close
  ov.addEventListener("click",function(e){if(e.target===ov)close();});
  
  // Close button
  var btn=document.createElement("button");
  btn.innerHTML="&times;";
  btn.style.cssText="position:absolute;top:16px;right:16px;background:rgba(255,255,255,.2);border:none;color:#fff;font-size:28px;width:40px;height:40px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center";
  btn.onclick=close;
  
  ov.appendChild(img);
  ov.appendChild(btn);
  document.body.appendChild(ov);
}
