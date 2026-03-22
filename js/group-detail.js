// ShipperShop Group Detail Page
/* === ShipperShop Group Page - Clean Rewrite === */ var CU=JSON.parse(localStorage.getItem("user")||"null");
var P=new URLSearchParams(location.search);
var groupSlug=P.get("slug")||"";
var groupId=parseInt(P.get("id"))||0;
var GROUP=null,curSort="new",grpPage=1,grpTotalPg=1;

function toast(m){var t=document.getElementById("toast");if(!t)return;t.textContent=m;t.className="toast show";setTimeout(function(){t.className="toast";},2500);}
function esc(s){if(!s)return"";return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");}
function fN(n){n=parseInt(n)||0;if(n>=1000000)return(n/1000000).toFixed(1)+"tr";if(n>=1000)return(n/1000).toFixed(1)+"k";return n;}
function ago(dt){if(!dt)return"";var s=Math.floor((new Date()-new Date(dt.replace(" ","T")))/1000);if(s<0)s=0;if(s<60)return s+" gi\u00e2y";if(s<3600)return Math.floor(s/60)+" ph\u00fat";if(s<86400)return Math.floor(s/3600)+" gi\u1edd";if(s<604800)return Math.floor(s/86400)+" ng\u00e0y";return new Date(dt).toLocaleDateString("vi-VN");}

function apiFetch(url,opts){
  opts=opts||{};opts.credentials="include";
  if(!opts.headers)opts.headers={};
  var tk=localStorage.getItem("token");
  if(tk&&!opts.headers["Authorization"])opts.headers["Authorization"]="Bearer "+tk;
  return fetch(url,opts).then(function(r){return r.json();});
}

/* ========== LOAD GROUP ========== */
function loadGroup(){
  var q=groupSlug?"slug="+encodeURIComponent(groupSlug):"id="+groupId;
  apiFetch("/api/groups.php?action=detail&"+q).then(function(d){
    if(!d.success||!d.data){document.getElementById("gBanner").innerHTML="<p style='text-align:center;padding:40px;color:#999'>Kh\u00f4ng t\u00ecm th\u1ea5y nh\u00f3m</p>";return;}
    GROUP=d.data;
    // Dynamic SEO
    document.title=GROUP.name+' - ShipperShop';
    var _gm=function(p,v){var m=document.querySelector('meta[property="'+p+'"]');if(m)m.content=v;else{m=document.createElement('meta');m.setAttribute('property',p);m.content=v;document.head.appendChild(m);}};
    _gm('og:title',GROUP.name+' - ShipperShop');
    _gm('og:description',(GROUP.description||'').substring(0,160));
    if(GROUP.icon_image)_gm('og:image','https://shippershop.vn'+GROUP.icon_image);

    renderHeader();
    loadPosts();
    renderAbout();
    loadLeaderboard();
  }).catch(function(e){console.error("loadGroup error:",e);});
}

/* ========== RENDER HEADER ========== */
function renderHeader(){
  var g=GROUP;
  document.title=g.name+" - ShipperShop";
  document.getElementById("ghName").textContent=g.name;
  var jb=document.getElementById("ghJoin");
  if(g.is_member){jb.className="gh-join joined";jb.textContent="\u0110\u00e3 tham gia";}
  else{jb.className="gh-join join";jb.textContent="Tham gia";}
  var icon=g.icon_image?"<img src='"+esc(g.icon_image)+"' style='width:100%;height:100%;object-fit:cover;border-radius:16px'>":"<span>"+esc(g.name[0])+"</span>";
  var isAdmin=g.member_role==="admin";
  var bannerBg=g.banner_image?"background:url("+esc(g.banner_image)+") center/cover no-repeat":"background:"+(g.banner_color||"var(--primary)");
  var editBtns="";
  if(isAdmin){editBtns="<div style='position:absolute;bottom:8px;right:8px;display:flex;gap:6px'><label style='width:32px;height:32px;border-radius:50%;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;cursor:pointer'><i class='fas fa-camera' style='color:#fff;font-size:12px'></i><input type='file' accept='image/*' style='display:none' onchange='uploadGroupImg(\"banner\",this)'></label></div>";}
  var iconEdit="";
  if(isAdmin){iconEdit="<label style='position:absolute;bottom:-2px;right:-2px;width:20px;height:20px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid #fff'><i class='fas fa-camera' style='color:#fff;font-size:8px'></i><input type='file' accept='image/*' style='display:none' onchange='uploadGroupImg(\"icon\",this)'></label>";}
  document.getElementById("gBanner").innerHTML=
    "<div style='"+bannerBg+";height:140px;position:relative'>"+editBtns+"</div>"+
    "<div class='g-banner-info' style='margin-top:-32px;padding:0 16px 16px;align-items:flex-end'>"+
    "<div class='g-banner-icon' style='background:"+(g.banner_color||"var(--primary)")+";position:relative;border:3px solid #fff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.15)'>"+icon+iconEdit+"</div>"+
    "<div class='g-banner-text' style='padding-bottom:4px'><h1>"+esc(g.name)+"</h1><div class='gm'>"+fN(g.member_count)+" th\u00e0nh vi\u00ean \u00b7 "+fN(g.post_count||0)+" b\u00e0i vi\u1ebft"+(g.cat_name?" \u00b7 "+esc(g.cat_name):"")+"</div></div></div>";
}

/* ========== TABS ========== */
function switchTab(tab,el){
  document.querySelectorAll(".g-tab").forEach(function(t){t.classList.remove("active");});
  if(el)el.classList.add("active");
  var tp=document.getElementById("tabPosts");
  var ta=document.getElementById("tabAbout");
  var tl=document.getElementById("tabLeaderboard");
  if(tp)tp.style.display=tab==="posts"?"block":"none";
  if(ta)ta.style.display=tab==="about"?"block":"none";
  if(tl)tl.style.display=tab==="leaderboard"?"block":"none";
}

/* ========== JOIN/LEAVE ========== */
function toggleJoin(){
  if(!CU){location.href="login.html";return;}
  if(!GROUP)return;
  apiFetch("/api/groups.php?action=join",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({group_id:GROUP.id})})
  .then(function(d){
    if(d.success){GROUP.is_member=d.data.joined;if(d.data.joined){GROUP.member_count++;toast("\u0110\u00e3 tham gia nh\u00f3m!");}else{GROUP.member_count--;toast("\u0110\u00e3 r\u1eddi nh\u00f3m");}renderHeader();loadPosts();}
    else{toast(d.message||"L\u1ed7i");}
  });
}
function shareGroup(){var url=location.origin+"/group.html?slug="+(GROUP?GROUP.slug:"");if(navigator.share)navigator.share({url:url,title:GROUP?GROUP.name:"Nh\u00f3m"});else{navigator.clipboard.writeText(url);toast("\u0110\u00e3 copy link!");}}

/* ========== LOAD POSTS ========== */
function loadPosts(){
  if(!GROUP)return;
  var tp=document.getElementById("tabPosts");
  if(!tp)return;
  var h="";
  if(GROUP.is_member&&CU){
    var av=CU.avatar?"<img src='"+CU.avatar+"' style='width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0' loading=\"lazy\">":"<div style='width:36px;height:36px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0'>"+esc((CU.fullname||"?")[0])+"</div>";
    h+="<div class='create-area'>"+av+"<input class='create-input' type='text' placeholder='B\u1ea1n \u0111ang ngh\u0129 g\u00ec? Click \u0111\u1ec3 \u0111\u0103ng b\u00e0i...' readonly onclick='openPM()'><button class='icon-btn' title='\u0110\u0103ng \u1ea3nh/video' onclick='openPM()'><i class='fas fa-image'></i></button><button class='icon-btn' title='Confession' onclick='openPM()'><i class='fas fa-mask'></i></button><button class='icon-btn' title='H\u1ecfi \u0111\u00e1p' onclick='openPM()'><i class='fas fa-question'></i></button></div>";
  }
  h+="<div class='sort-bar'><div class='sort-btn"+(curSort==="hot"?" active":"")+"' onclick='changeSort(\"hot\",this)'>\ud83d\udd25 N\u1ed5i b\u1eadt</div><div class='sort-btn"+(curSort==="new"?" active":"")+"' onclick='changeSort(\"new\",this)'>\ud83c\udd95 M\u1edbi nh\u1ea5t</div><div class='sort-btn"+(curSort==="top"?" active":"")+"' onclick='changeSort(\"top\",this)'>\u2b06 Nhi\u1ec1u th\u00edch</div></div>";
  h+="<div id='postsArea'></div>";
  h+="<button id='grpLoadMore' onclick='grpPage++;fetchPosts(true)' style='display:none;width:100%;padding:12px;background:#e4e6eb;border:none;font-size:14px;font-weight:700;cursor:pointer;margin-top:4px'>T\u1ea3i th\u00eam b\u00e0i vi\u1ebft</button>";
  tp.innerHTML=h;
  fetchPosts();
}

function changeSort(s,el){curSort=s;document.querySelectorAll(".sort-btn").forEach(function(b){b.classList.remove("active");});if(el)el.classList.add("active");fetchPosts();}

function fetchPosts(append){
  if(!append)grpPage=1;
  apiFetch("/api/groups.php?action=posts&group_id="+GROUP.id+"&sort="+curSort+"&page="+grpPage).then(function(d){
    var area=document.getElementById("postsArea");
    if(!area)return;
    if(!d||!d.success||!d.data||!d.data.posts||!d.data.posts.length){
      if(!append)area.innerHTML="<p style='text-align:center;padding:40px;color:#999'>Ch\u01b0a c\u00f3 b\u00e0i vi\u1ebft n\u00e0o</p>";
      return;
    }
    grpTotalPg=d.data.total_pages||1;
    var html=d.data.posts.map(function(p){return mkPost(p);}).join("");
    if(append)area.insertAdjacentHTML("beforeend",html);
    else area.innerHTML=html;
    var lb=document.getElementById("grpLoadMore");
    if(lb)lb.style.display=grpPage<grpTotalPg?"block":"none";
  }).catch(function(e){console.error("fetchPosts:",e);});
}

/* ========== POST CARD (same visual as index.html) ========== */
function mkPost(p){
  var uid=p.user_id||0;
  var uName=p.user_name||"Shipper";
  var likes=parseInt(p.likes_count)||0;
  var cmts=parseInt(p.comments_count)||0;
  var shares=parseInt(p.shares_count)||0;
  var liked=p.user_liked;
  var ch=esc(uName).charAt(0).toUpperCase();
  var av=p.user_avatar?"<a href='user.html?id="+uid+"'><img class='avatar-sm' src='"+esc(p.user_avatar)+"' onerror=\"this.outerHTML='<span class=avatar-sm loading=\"lazy\">"+ch+"</span>'\"></a>":"<a href='user.html?id="+uid+"'><span class='avatar-sm'>"+ch+"</span></a>";
  var shipH="";
  if(p.shipping_company){var sc=p.shipping_company;var colors={"GHTK":"#00b14f","J&T":"#d32f2f","GHN":"#ff6600","Viettel Post":"#e21a1a","SPX":"#EE4D2D","Grab":"#00b14f","Be":"#5bc500","Gojek":"#00aa13","Ninja Van":"#c41230"};shipH="<span style='font-size:11px;font-weight:700;color:"+(colors[sc]||"#666")+"'>"+esc(sc)+"</span><span>\u00b7</span>";}
  var body=esc(p.content||"");
  var isLong=body.length>400;
  var cid="gpc"+p.id;
  var contentH="<div class='post-content"+(isLong?"":" full")+"' id='"+cid+"'>"+body+"</div>";
  if(isLong)contentH+="<span class='show-more' onclick=\"var e=document.getElementById('"+cid+"');e.classList.toggle('full');this.textContent=e.classList.contains('full')?'Thu g\u1ecdn \u25b2':'Xem th\u00eam \u25bc'\">Xem th\u00eam \u25bc</span>";
  var imgH="";
  if(p.images){try{var arr=JSON.parse(p.images);if(arr&&arr.length){imgH="<div class='post-images"+(arr.length>1?" multi-img":"")+"'>";for(var i=0;i<Math.min(arr.length,4);i++)imgH+="<img class='post-img' src='"+arr[i]+"' loading='lazy' onerror=\"this.style.display='none'\">";imgH+="</div>";}}catch(x){}}
  var canDel=CU&&parseInt(uid)===parseInt(CU.id);
  var menuH="<div class='post-menu' id='gpm"+p.id+"' style='display:none'>"+(canDel?"<div onclick='editGrpPost("+p.id+")'><i class='fas fa-pen'></i> S\u1eeda b\u00e0i</div><div onclick='gDelP("+p.id+")'><i class='far fa-trash-can'></i> X\u00f3a b\u00e0i</div>":"")+"<div onclick='gTogMenu("+p.id+")'><i class='fas fa-times'></i> \u0110\u00f3ng</div></div>";
  return "<div class='post-card' id='GP"+p.id+"'><div class='post-body'><div class='post-meta'>"+av+"<div style='flex:1;min-width:0'><div style='display:flex;align-items:center;justify-content:space-between'><a href='user.html?id="+uid+"' class='post-author' style='text-decoration:none;color:#1a1a1a'>"+esc(uName)+"</a><button class='post-dots' onclick='event.stopPropagation();gTogMenu("+p.id+")'><i class='fas fa-ellipsis'></i></button></div><div style='font-size:12px;color:#999;display:flex;align-items:center;gap:4px'>"+shipH+"<span>"+ago(p.created_at)+"</span></div></div></div>"+menuH+contentH+imgH+"</div><div class='pa3-stats'><span>"+(likes>0?fN(likes)+" \u0111\u01a1n giao th\u00e0nh c\u00f4ng":"")+"</span><span>"+(cmts>0?fN(cmts)+" ghi ch\u00fa":"")+"</span><span>"+(shares>0?fN(shares)+" \u0111\u01a1n chuy\u1ec3n ti\u1ebfp":"")+"</span></div><div class='post-actions-3'><button class='pa3-btn"+(liked?" pa3-active":"")+"' onclick='gLike("+p.id+",this)'>Th\u00e0nh c\u00f4ng</button><button class='pa3-btn' onclick='openGhiChu("+p.id+")'>Ghi ch\u00fa</button><button class='pa3-btn' onclick='gShare("+p.id+")'>Chuy\u1ec3n ti\u1ebfp</button></div></div>";
}

/* ========== POST ACTIONS ========== */
function gLike(pid,btn){
  if(!CU){toast("\u0110\u0103ng nh\u1eadp!");return;}
  apiFetch("/api/groups.php?action=like_post",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({post_id:pid})}).then(function(d){
    if(d.success){btn.className="pa3-btn"+(d.data.liked?" pa3-active":"");var card=document.getElementById("GP"+pid);if(card){var ss=card.querySelectorAll(".pa3-stats span");if(ss[0])ss[0].textContent=(d.data.likes_count||0)>0?d.data.likes_count+" \u0111\u01a1n giao th\u00e0nh c\u00f4ng":"";}}
  });
}
function gShare(pid){var u="https://shippershop.vn/group.html?"+(GROUP?"slug="+GROUP.slug:"id="+groupId)+"#post-"+pid;if(navigator.share)navigator.share({url:u});else{navigator.clipboard.writeText(u);toast("\u0110\u00e3 copy link!");}}
function gTogMenu(pid){var m=document.getElementById("gpm"+pid);if(!m)return;var show=m.style.display==="none";document.querySelectorAll(".post-menu").forEach(function(el){el.style.display="none";});if(show)m.style.display="block";}
function gDelP(pid){if(!confirm("X\u00f3a b\u00e0i vi\u1ebft n\u00e0y?"))return;gTogMenu(pid);apiFetch("/api/groups.php?action=delete_post",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({post_id:pid})}).then(function(d){if(d.success){var el=document.getElementById("GP"+pid);if(el)el.remove();}});}
function reportP(pid){gTogMenu(pid);toast("\u0110\u00e3 g\u1eedi b\u00e1o c\u00e1o!");}

/* ========== COMMENT SHEET (Ghi chú) ========== */
var _gcPid=null,_gcRpl=null;

function openGhiChu(pid){
  var old=document.getElementById("gcOv");if(old)old.remove();
  _gcPid=pid;_gcRpl=null;
  var ov=document.createElement("div");ov.id="gcOv";
  ov.style.cssText="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:flex-end;justify-content:center;backdrop-filter:blur(2px)";
  ov.onclick=function(e){if(e.target===ov)gcClose();};
  var sh=document.createElement("div");
  sh.style.cssText="background:#fff;width:100%;max-width:600px;max-height:85vh;border-radius:16px 16px 0 0;display:flex;flex-direction:column;overflow:hidden";
  sh.innerHTML="<div style='width:36px;height:4px;background:#ddd;border-radius:2px;margin:10px auto 0'></div>"
    +"<div style='padding:12px 16px 8px;border-bottom:1px solid #e4e6eb;display:flex;align-items:center'><h3 style='flex:1;font-size:16px;font-weight:700;margin:0'>Ghi ch\u00fa</h3><button onclick='gcClose()' style='background:#e4e6eb;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:14px'>&times;</button></div>"
    +"<div id='gcBody' style='flex:1;overflow-y:auto;padding:8px 0'><div style='text-align:center;padding:30px;color:#999'><i class='fas fa-spinner fa-spin'></i></div></div>"
    +"<div id='gcRplBar' style='display:none;padding:6px 16px;background:#f0f2f5;font-size:12px;color:#65676B'>Tr\u1ea3 l\u1eddi <b id='gcRplName'></b> <a onclick='gcCancelRpl()' style='color:#7C3AED;font-weight:700;cursor:pointer;margin-left:8px'>H\u1ee7y</a></div>"
    +"<div style='display:flex;gap:8px;padding:10px 12px;border-top:1px solid #e4e6eb'><input id='gcInp' style='flex:1;padding:8px 14px;border-radius:20px;border:1px solid #e4e6eb;font-size:14px;outline:none;font-family:inherit' placeholder='Vi\u1ebft ghi ch\u00fa...' onkeydown='if(event.key===\"Enter\")gcSend()'><button onclick='gcSend()' style='background:#7C3AED;color:#fff;border:none;border-radius:50%;width:36px;height:36px;font-size:14px;cursor:pointer;flex-shrink:0'><i class='fas fa-paper-plane'></i></button></div>";
  ov.appendChild(sh);document.body.appendChild(ov);
  gcLoadCmts(pid);
}
function gcClose(){var ov=document.getElementById("gcOv");if(ov)ov.remove();_gcPid=null;_gcRpl=null;}

function gcLoadCmts(pid){
  apiFetch("/api/groups.php?action=comments&post_id="+pid+"&_t="+Date.now()).then(function(d){
    var wrap=document.getElementById("gcBody");if(!wrap)return;
    if(!d.success||!d.data||!d.data.length){wrap.innerHTML="<div style='text-align:center;padding:30px;color:#999'>Ch\u01b0a c\u00f3 ghi ch\u00fa</div>";return;}
    var flat=d.data,map={},list=[];
    flat.forEach(function(c){map[c.id]=c;});
    function build(cc,depth,pn){list.push({c:cc,dp:Math.min(depth,1),rpl:depth>0?pn:null});flat.filter(function(x){return parseInt(x.parent_id)===parseInt(cc.id);}).forEach(function(r){build(r,depth+1,cc.user_name);});}
    flat.filter(function(c){return !c.parent_id||parseInt(c.parent_id)<=0||!map[c.parent_id];}).forEach(function(c){build(c,0,null);});
    wrap.innerHTML=list.map(function(item){return gcCmt(item.c,item.dp,item.rpl);}).join("");
  }).catch(function(e){console.error("gcLoadCmts:",e);});
}

function gcCmt(c,dp,rplTo){
  var sz=dp>0?22:28;
  var indent=dp>0?"padding-left:20px;margin-left:14px;border-left:2px solid #e4e6eb":"";
  var av=c.user_avatar?"<img src='"+esc(c.user_avatar)+"' style='width:"+sz+"px;height:"+sz+"px;border-radius:50%;object-fit:cover;flex-shrink:0' loading=\"lazy\">":"<div style='width:"+sz+"px;height:"+sz+"px;border-radius:50%;background:#e4e6eb;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0'>"+esc((c.user_name||"U")[0])+"</div>";
  var lk=c.user_vote==="up"||c.user_liked;
  var n=parseInt(c.likes_count)||0;
  var sn=esc(c.user_name||"");
  var rplTag=rplTo?"<span style='color:#7C3AED;font-size:11px'>\u21a9 "+esc(rplTo)+"</span> ":"";
  return "<div style='display:flex;gap:6px;padding:4px 16px;"+indent+"'>"+av+"<div style='flex:1;min-width:0'><div style='background:#f0f2f5;border-radius:18px;padding:6px 12px;display:inline-block;max-width:100%'><div style='font-weight:700;font-size:13px'>"+sn+"</div><div style='font-size:14px;line-height:1.4;margin-top:1px'>"+rplTag+esc(c.content)+"</div></div><div style='display:flex;gap:12px;padding:2px 12px;font-size:12px;color:#65676B'><span>"+ago(c.created_at)+"</span><a style='color:"+(lk?"#7C3AED":"#65676B")+";text-decoration:none;font-weight:600;cursor:pointer' onclick='gcLkCmt("+c.id+",this)'>Th\u00e0nh c\u00f4ng"+(n>0?" \u00b7 "+n:"")+"</a><a style='color:#65676B;text-decoration:none;font-weight:600;cursor:pointer' onclick='gcSetRpl("+c.id+",\""+sn.replace(/"/g,"&quot;")+"\")'>Ghi ch\u00fa</a></div></div></div>";
}

function gcLkCmt(cid,el){
  if(!CU){toast("\u0110\u0103ng nh\u1eadp!");return;}
  apiFetch("/api/groups.php?action=like_comment",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({comment_id:cid})}).then(function(d){
    if(d.success){var n=parseInt(d.data.likes_count)||0;el.style.color=d.data.liked?"#7C3AED":"#65676B";el.textContent="Th\u00e0nh c\u00f4ng"+(n>0?" \u00b7 "+n:"");}
  });
}
function gcSetRpl(cid,name){_gcRpl=cid;document.getElementById("gcRplBar").style.display="block";document.getElementById("gcRplName").textContent=name;var inp=document.getElementById("gcInp");inp.placeholder="Tr\u1ea3 l\u1eddi "+name+"...";inp.focus();}
function gcCancelRpl(){_gcRpl=null;document.getElementById("gcRplBar").style.display="none";document.getElementById("gcInp").placeholder="Vi\u1ebft ghi ch\u00fa...";}

function gcSend(){
  if(!CU){toast("\u0110\u0103ng nh\u1eadp!");return;}
  var inp=document.getElementById("gcInp");var ct=inp.value.trim();if(!ct||!_gcPid)return;
  inp.value="";
  var body={post_id:_gcPid,content:ct};if(_gcRpl)body.parent_id=_gcRpl;
  gcCancelRpl();
  apiFetch("/api/groups.php?action=comment",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(body)}).then(function(d){
    if(d.success){toast("\u0110\u00e3 ghi ch\u00fa!");gcLoadCmts(_gcPid);var card=document.getElementById("GP"+_gcPid);if(card){var s=card.querySelectorAll(".pa3-stats span");if(s[1]){var cur=parseInt(s[1].textContent)||0;s[1].textContent=(cur+1)+" ghi ch\u00fa";}}}
    else{toast(d.message||"L\u1ed7i");inp.value=ct;}
  }).catch(function(){toast("L\u1ed7i k\u1ebft n\u1ed1i");inp.value=ct;});
}

/* ========== ABOUT TAB ========== */
function renderAbout(){
  if(!GROUP)return;
  var g=GROUP;
  var h="<div class='about-section'><h3>M\u00f4 t\u1ea3</h3><p style='font-size:14px;line-height:1.6'>"+esc(g.description||"Ch\u01b0a c\u00f3 m\u00f4 t\u1ea3")+"</p></div>";
  if(g.rules&&g.rules.length){h+="<div class='about-section'><h3>Quy t\u1eafc</h3>";g.rules.forEach(function(r){h+="<div class='rule-item'><div class='rule-num'>"+r.rule_order+"</div><div><div class='rule-title'>"+esc(r.title)+"</div>"+(r.description?"<div class='rule-desc'>"+esc(r.description)+"</div>":"")+"</div></div>";});h+="</div>";}
  h+="<div class='about-section'><h3>Th\u00f4ng tin</h3><div style='font-size:14px;color:#65676B'><p><i class='fas fa-calendar' style='width:20px'></i> T\u1ea1o ng\u00e0y "+new Date(g.created_at).toLocaleDateString("vi-VN")+"</p><p><i class='fas fa-user' style='width:20px'></i> "+esc(g.creator_name||"Admin")+"</p><p><i class='fas "+(g.privacy==="private"?"fa-lock":"fa-globe")+"' style='width:20px'></i> "+(g.privacy==="private"?"Ri\u00eang t\u01b0":"C\u00f4ng khai")+"</p></div></div>";
  var ta=document.getElementById("tabAbout");if(ta)ta.innerHTML=h;
}

/* ========== LEADERBOARD TAB ========== */
var lbType="posts";
function loadLeaderboard(){fetchLeaderboard();}
function changeLbType(t,el){lbType=t;document.querySelectorAll(".lb-tab").forEach(function(b){b.classList.remove("active");});if(el)el.classList.add("active");fetchLeaderboard();}
function fetchLeaderboard(){
  if(!GROUP)return;
  var tl=document.getElementById("tabLeaderboard");if(!tl)return;
  apiFetch("/api/groups.php?action=leaderboard&group_id="+GROUP.id+"&type="+lbType).then(function(d){
    var h="<div style='display:flex;gap:8px;padding:12px 16px'><button class='lb-tab"+(lbType==="posts"?" active":"")+"' onclick='changeLbType(\"posts\",this)'>B\u00e0i vi\u1ebft</button><button class='lb-tab"+(lbType==="comments"?" active":"")+"' onclick='changeLbType(\"comments\",this)'>Ghi ch\u00fa</button></div>";
    if(!d.success||!d.data||!d.data.length){h+="<p style='text-align:center;padding:30px;color:#999'>Ch\u01b0a c\u00f3 d\u1eef li\u1ec7u</p>";tl.innerHTML=h;return;}
    d.data.forEach(function(u,i){
      var av=u.avatar?"<img src='"+esc(u.avatar)+"' style='width:36px;height:36px;border-radius:50%;object-fit:cover' loading=\"lazy\">":"<div style='width:36px;height:36px;border-radius:50%;background:#e4e6eb;display:flex;align-items:center;justify-content:center;font-weight:700'>"+esc((u.fullname||"U")[0])+"</div>";
      var medal=i===0?"\ud83e\udd47":i===1?"\ud83e\udd48":i===2?"\ud83e\udd49":"#"+(i+1);
      var stat=lbType==="posts"?(u.post_count||0)+" b\u00e0i \u00b7 "+(u.total_likes||0)+" \u0111\u01a1n GTC":(u.comment_count||0)+" ghi ch\u00fa \u00b7 "+(u.total_likes||0)+" \u0111\u01a1n GTC";
      h+="<div style='display:flex;gap:10px;padding:10px 16px;align-items:center'><span style='width:28px;text-align:center;font-weight:700;font-size:14px'>"+medal+"</span>"+av+"<div style='flex:1'><div style='font-weight:700;font-size:14px'>"+esc(u.fullname)+"</div><div style='font-size:12px;color:#65676B'>"+stat+"</div></div></div>";
    });
    tl.innerHTML=h;
  });
}

/* ========== CREATE POST ========== */
function openPM(){
  if(!CU){location.href="login.html";return;}
  if(typeof openSPM==="function"){window._spmGroupId=GROUP?GROUP.id:null;window._spmOnSuccess=function(){fetchPosts();};openSPM();}
  else{document.getElementById("pmOverlay").classList.add("open");}
}
function closePM(){document.getElementById("pmOverlay").classList.remove("open");}
function submitPost(){
  var ta=document.getElementById("pmText");var ct=ta.value.trim();if(!ct)return;
  var btn=document.getElementById("pmSend");btn.disabled=true;btn.textContent="\u0110ang \u0111\u0103ng...";
  apiFetch("/api/groups.php?action=post",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({group_id:GROUP.id,content:ct})}).then(function(d){
    if(d.success){closePM();ta.value="";toast("\u0110\u00e3 \u0111\u0103ng b\u00e0i!");fetchPosts();}else{toast(d.message||"L\u1ed7i");}
    btn.disabled=false;btn.textContent="\u0110\u0103ng b\u00e0i";
  }).catch(function(){btn.disabled=false;btn.textContent="\u0110\u0103ng b\u00e0i";toast("L\u1ed7i k\u1ebft n\u1ed1i");});
}

/* ========== IMAGE UPLOAD ========== */
function uploadGroupImg(type,input){
  if(!input.files||!input.files[0])return;
  var fd=new FormData();fd.append(type,input.files[0]);fd.append("action","update_group");fd.append("group_id",GROUP.id);
  var tk=localStorage.getItem("token");var h={};if(tk)h["Authorization"]="Bearer "+tk;
  fetch("/api/groups.php?action=update_group",{method:"POST",headers:h,credentials:"include",body:fd}).then(function(r){return r.json();}).then(function(d){
    if(d.success){toast("\u0110\u00e3 c\u1eadp nh\u1eadt!");loadGroup();}else toast(d.message||"L\u1ed7i");
  });
}

/* ========== INIT ========== */
// loadPosts is already global
document.addEventListener("click",function(e){if(!e.target.closest(".post-dots")&&!e.target.closest(".post-menu")){document.querySelectorAll(".post-menu").forEach(function(m){m.style.display="none";});}});
if(groupSlug||groupId)loadGroup();


function editGrpPost(pid){
  var el=document.querySelector('[data-gpid="'+pid+'"] .gp-content, #gp'+pid+' .gp-content');
  if(!el)el=document.querySelector('[data-gpid="'+pid+'"] .post-content, #gp'+pid+' .post-content');
  if(!el)return;
  var oldText=el.textContent||'';
  var ta=document.createElement('textarea');
  ta.value=oldText;
  ta.style.cssText='width:100%;min-height:60px;padding:8px;border:1px solid #7C3AED;border-radius:8px;font-size:14px;resize:vertical;box-sizing:border-box;margin:4px 0';
  var btns=document.createElement('div');
  btns.style.cssText='display:flex;gap:8px;justify-content:flex-end;padding:4px 0';
  btns.innerHTML='<button onclick="this.parentNode.previousSibling.remove();this.parentNode.previousSibling.style.display=\'\';this.parentNode.remove()" style="padding:6px 14px;border:1px solid #ddd;border-radius:6px;background:#fff;cursor:pointer;font-size:13px">Huy</button><button onclick="saveGrpEdit('+pid+',this.parentNode.previousSibling)" style="padding:6px 14px;border:none;border-radius:6px;background:#7C3AED;color:#fff;cursor:pointer;font-weight:600;font-size:13px">Luu</button>';
  el.style.display='none';
  el.parentNode.insertBefore(ta,el.nextSibling);
  el.parentNode.insertBefore(btns,ta.nextSibling);
  ta.focus();
}
function saveGrpEdit(pid,ta){
  if(!ta||!ta.value.trim())return;
  var token=localStorage.getItem('token');
  fetch('/api/groups.php?action=edit_post',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({post_id:pid,content:ta.value.trim()})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){location.reload();}else{alert(d.message||'Loi');}
  });
}
function delGrpPost(pid){
  if(!confirm('Xoa bai viet nay?'))return;
  var token=localStorage.getItem('token');
  fetch('/api/groups.php?action=delete_post',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({post_id:pid})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){var el=document.getElementById('gp'+pid)||document.querySelector('[data-gpid="'+pid+'"]');if(el)el.style.display='none';toast('Da xoa');}
    else{toast(d.message||'Loi','error');}
  });
}
