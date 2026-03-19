/* ShipperShop Image Viewer - Facebook style
   Usage: openLb(imgSrc, postId, allImgs)
   - Click image → slides up, dark bg, post info + actions below
   - X button or swipe down to close
*/
(function(){
var _viewer=null,_startY=0,_curY=0,_dragging=false,_pid=0,_imgs=[],_idx=0;

window.openLb=function(src,pid,imgs){
  _pid=pid||0;
  _imgs=imgs||[src];
  _idx=_imgs.indexOf(src);if(_idx<0)_idx=0;
  show();
};

function show(){
  if(_viewer)_viewer.remove();
  var ov=document.createElement("div");
  ov.id="ssImgViewer";
  ov.style.cssText="position:fixed;inset:0;z-index:9999;background:#000;display:flex;flex-direction:column;animation:ssIvIn .25s ease-out";

  // Top bar
  var top=document.createElement("div");
  top.style.cssText="position:absolute;top:0;left:0;right:0;display:flex;align-items:center;justify-content:space-between;padding:12px 16px;z-index:10";
  top.innerHTML="<span style='color:#fff;font-size:13px;opacity:.7'>"+(_imgs.length>1?(_idx+1)+"/"+_imgs.length:"")+"</span><button onclick='closeLb()' style='width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.15);border:none;color:#fff;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center'><i class='fas fa-times'></i></button>";

  // Image area (swipeable)
  var imgWrap=document.createElement("div");
  imgWrap.style.cssText="flex:1;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;touch-action:pan-y";
  imgWrap.id="ssIvImgWrap";
  var img=document.createElement("img");
  img.src=_imgs[_idx];
  img.style.cssText="max-width:100%;max-height:100%;object-fit:contain;transition:transform .2s;user-select:none;-webkit-user-drag:none";
  img.id="ssIvImg";
  imgWrap.appendChild(img);

  // Swipe left/right for multi images
  if(_imgs.length>1){
    var leftA=document.createElement("div");
    leftA.style.cssText="position:absolute;left:0;top:0;bottom:0;width:60px;z-index:5";
    leftA.onclick=function(){if(_idx>0){_idx--;document.getElementById("ssIvImg").src=_imgs[_idx];updCounter();}};
    var rightA=document.createElement("div");
    rightA.style.cssText="position:absolute;right:0;top:0;bottom:0;width:60px;z-index:5";
    rightA.onclick=function(){if(_idx<_imgs.length-1){_idx++;document.getElementById("ssIvImg").src=_imgs[_idx];updCounter();}};
    imgWrap.appendChild(leftA);
    imgWrap.appendChild(rightA);
  }

  // Bottom panel with post info
  var bot=document.createElement("div");
  bot.id="ssIvBot";
  bot.style.cssText="background:rgba(0,0,0,.85);padding:12px 16px calc(12px + env(safe-area-inset-bottom));color:#fff";
  if(_pid){
    bot.innerHTML="<div style='text-align:center;padding:8px'><i class='fas fa-spinner fa-spin' style='color:#999'></i></div>";
    loadPostInfo(_pid,bot);
  }

  ov.appendChild(top);
  ov.appendChild(imgWrap);
  ov.appendChild(bot);
  document.body.appendChild(ov);
  document.body.style.overflow="hidden";
  _viewer=ov;

  // Swipe down to close
  imgWrap.addEventListener("touchstart",onTS,{passive:true});
  imgWrap.addEventListener("touchmove",onTM,{passive:false});
  imgWrap.addEventListener("touchend",onTE,{passive:true});
}

function updCounter(){
  var ov=document.getElementById("ssImgViewer");
  if(!ov)return;
  var sp=ov.querySelector("span");
  if(sp&&_imgs.length>1)sp.textContent=(_idx+1)+"/"+_imgs.length;
}

function onTS(e){_startY=e.touches[0].clientY;_curY=_startY;_dragging=true;}
function onTM(e){
  if(!_dragging)return;
  _curY=e.touches[0].clientY;
  var dy=_curY-_startY;
  if(dy>0){
    e.preventDefault();
    var wrap=document.getElementById("ssIvImgWrap");
    if(wrap)wrap.style.transform="translateY("+dy+"px)";
    var ov=document.getElementById("ssImgViewer");
    if(ov)ov.style.background="rgba(0,0,0,"+Math.max(0.3,1-dy/400)+")";
  }
}
function onTE(){
  _dragging=false;
  var dy=_curY-_startY;
  if(dy>120){closeLb();return;}
  var wrap=document.getElementById("ssIvImgWrap");
  if(wrap)wrap.style.transform="";
  var ov=document.getElementById("ssImgViewer");
  if(ov)ov.style.background="#000";
}

window.closeLb=function(){
  var ov=document.getElementById("ssImgViewer");
  if(!ov)return;
  ov.style.animation="ssIvOut .2s ease-in forwards";
  setTimeout(function(){if(ov.parentNode)ov.remove();},200);
  document.body.style.overflow="";
  _viewer=null;
};

function loadPostInfo(pid,bot){
  var isGroup=window._ssIvGroup||false;
  var url=isGroup?"/api/groups.php?action=posts&group_id="+(window._ssIvGroupId||0):"/api/posts.php?id="+pid;
  var tk=localStorage.getItem("token");
  var hdrs={};if(tk)hdrs["Authorization"]="Bearer "+tk;
  fetch(url,{credentials:"include",headers:hdrs}).then(function(r){return r.json();}).then(function(d){
    var p=null;
    if(isGroup&&d.data&&d.data.posts){
      d.data.posts.forEach(function(pp){if(pp.id===pid)p=pp;});
    }else if(d.data){
      p=d.data.posts?null:d.data;
    }
    if(!p){bot.innerHTML="";return;}
    renderPostInfo(p,bot,isGroup);
  }).catch(function(){bot.innerHTML="";});
}

function renderPostInfo(p,bot,isGroup){
  var likes=parseInt(p.likes_count)||0;
  var cmts=parseInt(p.comments_count)||0;
  var shares=parseInt(p.shares_count)||0;
  var liked=p.user_liked||p.user_vote==="up";
  var uName=p.user_name||p.fullname||"Người dùng";
  var content=(p.content||"").substring(0,100);
  if((p.content||"").length>100)content+="...";

  var h="";
  // Post author + content preview
  h+="<div style='margin-bottom:10px'>";
  h+="<div style='font-weight:700;font-size:14px'>"+esc2(uName)+"</div>";
  if(content)h+="<div style='font-size:13px;color:rgba(255,255,255,.7);margin-top:2px'>"+esc2(content)+"</div>";
  h+="</div>";

  // Stats
  h+="<div style='display:flex;font-size:12px;color:rgba(255,255,255,.5);padding-bottom:8px;border-bottom:1px solid rgba(255,255,255,.15)'>";
  h+="<span style='flex:1'>"+(likes>0?fmtN(likes)+" đơn giao thành công":"")+"</span>";
  h+="<span>"+(cmts>0?fmtN(cmts)+" ghi chú":"")+"</span>";
  h+="</div>";

  // Action buttons
  var apiBase=isGroup?"groups":"posts";
  h+="<div style='display:flex;margin-top:8px'>";
  h+="<button id='ssIvLk' onclick='ssIvLike("+p.id+","+JSON.stringify(isGroup)+")' style='flex:1;padding:8px;text-align:center;border:none;background:none;color:"+(liked?"#A78BFA":"rgba(255,255,255,.8)")+";font-size:14px;font-weight:600;cursor:pointer'>Thành công</button>";
  h+="<button onclick='ssIvComment("+p.id+")' style='flex:1;padding:8px;text-align:center;border:none;background:none;color:rgba(255,255,255,.8);font-size:14px;font-weight:600;cursor:pointer'>Ghi chú</button>";
  h+="<button onclick='ssIvShare("+p.id+")' style='flex:1;padding:8px;text-align:center;border:none;background:none;color:rgba(255,255,255,.8);font-size:14px;font-weight:600;cursor:pointer'>Chuyển tiếp</button>";
  h+="</div>";

  bot.innerHTML=h;
}

// Actions
window.ssIvLike=function(pid,isGroup){
  var tk=localStorage.getItem("token");if(!tk)return;
  var hdrs={"Content-Type":"application/json","Authorization":"Bearer "+tk};
  var url=isGroup?"/api/groups.php?action=like_post":"/api/posts.php?action=vote";
  var body=isGroup?{post_id:pid}:{post_id:pid,vote_type:"up"};
  fetch(url,{method:"POST",headers:hdrs,credentials:"include",body:JSON.stringify(body)}).then(function(r){return r.json();}).then(function(d){
    if(d.success){
      var btn=document.getElementById("ssIvLk");
      if(btn){
        var liked=isGroup?d.data.liked:(d.data.user_vote==="up");
        btn.style.color=liked?"#A78BFA":"rgba(255,255,255,.8)";
      }
    }
  }).catch(function(){});
};
window.ssIvComment=function(pid){
  closeLb();
  // Trigger comment sheet on the page
  if(typeof openGhiChu==="function")openGhiChu(pid);
  else if(typeof openComment==="function")openComment(pid);
  else if(typeof openPostSheet==="function")openPostSheet(pid);
  else if(typeof openPdCmtModal==="function")openPdCmtModal();
};
window.ssIvShare=function(pid){
  var u=location.origin+"/share.php?type=post&id="+pid;
  if(navigator.share)navigator.share({url:u,title:"ShipperShop"});
  else{navigator.clipboard.writeText(u);if(typeof toast==="function")toast("Đã copy link!");}
};

function esc2(s){if(!s)return"";return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");}
function fmtN(n){if(n>=1000000)return(n/1000000).toFixed(1)+"M";if(n>=1000)return(n/1000).toFixed(1)+"K";return n;}

// CSS animation
var st=document.createElement("style");
st.textContent="@keyframes ssIvIn{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:none}}@keyframes ssIvOut{from{opacity:1}to{opacity:0;transform:translateY(60px)}}";
document.head.appendChild(st);
})();
