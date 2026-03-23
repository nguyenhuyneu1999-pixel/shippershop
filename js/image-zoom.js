// ShipperShop — Image Zoom Viewer (cover + avatar)
// Usage: zoomImage(src) or onclick="zoomImage('url')"

function zoomImage(src){
  if(!src)return;
  var ov=document.createElement("div");
  ov.style.cssText="position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:5000;display:flex;align-items:center;justify-content:center;cursor:zoom-out";
  
  var img=document.createElement("img");
  img.src=src;
  img.style.cssText="max-width:95vw;max-height:90vh;object-fit:contain;border-radius:4px;transition:transform .2s";
  
  var scale=1;
  var lastTap=0;
  
  // Double-tap/click to zoom
  img.addEventListener("click",function(e){
    e.stopPropagation();
    var now=Date.now();
    if(now-lastTap<300){
      scale=scale>1?1:2.5;
      img.style.transform="scale("+scale+")";
    }
    lastTap=now;
  });
  
  // Pinch zoom
  var startDist=0;
  img.addEventListener("touchstart",function(e){
    if(e.touches.length===2){
      startDist=Math.hypot(e.touches[0].clientX-e.touches[1].clientX,e.touches[0].clientY-e.touches[1].clientY);
    }
  },{passive:true});
  
  img.addEventListener("touchmove",function(e){
    if(e.touches.length===2){
      var dist=Math.hypot(e.touches[0].clientX-e.touches[1].clientX,e.touches[0].clientY-e.touches[1].clientY);
      scale=Math.max(0.5,Math.min(4,scale*(dist/startDist)));
      img.style.transform="scale("+scale+")";
      startDist=dist;
    }
  },{passive:true});
  
  // Close button
  var close=document.createElement("button");
  close.innerHTML="&times;";
  close.style.cssText="position:absolute;top:16px;right:16px;background:rgba(255,255,255,.2);border:none;color:#fff;font-size:28px;width:44px;height:44px;border-radius:50%;cursor:pointer;z-index:1";
  close.onclick=function(e){e.stopPropagation();ov.remove();};
  
  ov.appendChild(close);
  ov.appendChild(img);
  ov.onclick=function(){ov.remove();};
  document.body.appendChild(ov);
}
