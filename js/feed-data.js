// ShipperShop Feed Data — Provinces + Location Filter
// Extracted from index.html for browser caching (25KB saved per page view)
const PROVINCES=["An Giang","Bà Rịa - Vũng Tàu","Bắc Giang","Bắc Kạn","Bạc Liêu","Bắc Ninh","Bến Tre","Bình Định","Bình Dương","Bình Phước","Bình Thuận","Cà Mau","Cần Thơ","Cao Bằng","Đà Nẵng","Đắk Lắk","Đắk Nông","Điện Biên","Đồng Nai","Đồng Tháp","Gia Lai","Hà Giang","Hà Nam","Hà Nội","Hà Tĩnh","Hải Dương","Hải Phòng","Hậu Giang","Hòa Bình","Hưng Yên","Khánh Hòa","Kiên Giang","Kon Tum","Lai Châu","Lâm Đồng","Lạng Sơn","Lào Cai","Long An","Nam Định","Nghệ An","Ninh Bình","Ninh Thuận","Phú Thọ","Phú Yên","Quảng Bình","Quảng Nam","Quảng Ngãi","Quảng Ninh","Quảng Trị","Sóc Trăng","Sơn La","Tây Ninh","Thái Bình","Thái Nguyên","Thanh Hóa","Thừa Thiên Huế","Tiền Giang","TP. Hồ Chí Minh","Trà Vinh","Tuyên Quang","Vĩnh Long","Vĩnh Phúc","Yên Bái"];
let CU=null,sort='hot',type='all',prov=null,company='',page=1,totalPg=1,imgs=[],ptype='post';

document.addEventListener('DOMContentLoaded',()=>{
  CU=JSON.parse(localStorage.getItem('user')||'null');
  renderNav(); renderProvinces(); loadPosts(); loadTrend(); loadHashtags(); loadSuggestions(); loadAnnouncement(); loadFriendsLatest(); timeGreeting();
  // mProv populated by async province API fetch below
  document.getElementById('stM').textContent=Math.floor(Math.random()*3000+1000).toLocaleString();
  document.getElementById('stO').textContent=Math.floor(Math.random()*500+100);
});

function renderNav(){
  const a=document.getElementById('navArea');
  if(CU){
    const av=CU.avatar?`<img src="${CU.avatar}" style="width:32px;height:32px;border-radius:50%;object-fit:cover" loading=lazy>`:`<div class="avatar-sm" style="width:32px;height:32px;font-size:14px">${CU.fullname[0].toUpperCase()}</div>`;
    a.innerHTML=`<a href="messages.html" style="width:40px;height:40px;border-radius:50%;background:#f0f2f5;display:flex;align-items:center;justify-content:center;color:#333;text-decoration:none"><i class="fas fa-comment-dots" style="font-size:18px"></i></a>`;
    const ca=document.getElementById('cAvatar');if(!ca)return;
    if(CU.avatar) ca.outerHTML=`<img src="${CU.avatar}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0" loading=lazy>`;
    else ca.textContent=CU.fullname[0].toUpperCase();
  } else {
    a.innerHTML=`<button class="nav-btn" onclick="location='login.html'">Đăng nhập</button><button class="nav-btn filled" onclick="location='register.html'">Đăng ký</button>`;
  }
}

function renderProvinces(f=''){
  const L=document.getElementById('pList');
  L.innerHTML=`<div class="province-item${!prov?' active':''}" onclick="setProv(null,this)">🇻🇳 Toàn quốc</div>`;
  PROVINCES.filter(p=>!f||p.toLowerCase().includes(f.toLowerCase())).forEach(p=>{
    const d=document.createElement('div');
    d.className='province-item'+(prov===p?' active':'');
    d.textContent=p; d.onclick=()=>setProv(p,d); L.appendChild(d);
  });
}
function setProv(p,el){ prov=p; page=1; document.querySelectorAll('.province-item').forEach(i=>i.classList.remove('active')); el.classList.add('active'); loadPosts(); }

async function loadPosts(append=false){
  if(!append){ document.getElementById('feed').innerHTML='<div class="empty"><i class="fas fa-spinner spin"></i><p style="margin-top:8px">Đang tải...</p></div>'; page=1; }
  var fp=document.getElementById('fProvince')?document.getElementById('fProvince').value:'';var fd=document.getElementById('fDistrict')?document.getElementById('fDistrict').value:'';let url='/api/posts.php?sort='+sort+'&page='+page+'&limit=20'+(fp?'&province='+encodeURIComponent(fp):'')+(fd?'&district='+encodeURIComponent(fd):'');
  if(type&&type!=='all') url+=`&type=${type}`;
  if(prov) url+=`&province=${encodeURIComponent(prov)}`;
  const q=document.getElementById('q').value.trim();
  if(q) url+=`&search=${encodeURIComponent(q)}`;
  if(company) url+='&company='+encodeURIComponent(company);
  try{
    const r=await fetch(url,{credentials:'include'});
    const d=await r.json();
    if(d.success){
      totalPg=d.data.total_pages||1;
      document.getElementById('stP').textContent=d.data.total||0;
      document.getElementById('stT').textContent=Math.floor((d.data.total||0)*0.15+Math.random()*20);
      document.getElementById('loadMoreBtn').style.display=page<totalPg?'block':'none'; if(d.data&&d.data.next_cursor)_feedCursor=d.data.next_cursor; _feedLoading=false; if(!_infiniteObs)setupInfiniteScroll();
    setupImpressionTracking();
    // Check for polls
    document.querySelectorAll('.poll-container').forEach(function(el){
      var pid=el.id.replace('poll','');
      if(pid&&!el.dataset.loaded){el.dataset.loaded='1';loadPoll(parseInt(pid),el);}
    }); var sk=document.getElementById('feedSkeleton'); if(sk)sk.style.display='none';
      if(append) d.data.posts.forEach(p=>document.getElementById('feed').insertAdjacentHTML('beforeend',mkPost(p)));
      else{
        if(!d.data.posts.length) document.getElementById('feed').innerHTML='<div class="empty"><i class="fas fa-ghost"></i><p>Chưa có bài viết nào!</p><p style="font-size:13px;margin-top:6px">Hãy là người đầu tiên đăng bài 🎉</p></div>';
        else document.getElementById('feed').innerHTML=d.data.posts.map(mkPost).join('');
      }
    }
  }catch(e){ document.getElementById('feed').innerHTML='<div class="empty"><i class="fas fa-wifi"></i><p>Lỗi kết nối API</p></div>'; }
}
var _feedLoading=false;
var _feedCursor=0;
function loadMore(){ if(_feedLoading)return; _feedLoading=true; page++; loadPosts(true); }
var _loadingMore=false;
window.addEventListener('scroll',function(){
  if(_loadingMore||page>=totalPg)return;
  if((window.innerHeight+window.scrollY)>=document.body.offsetHeight-800){
    _loadingMore=true;
    page++;
    loadPosts(true).then(function(){_loadingMore=false;}).catch(function(){_loadingMore=false;});
  }
});
function search(){ page=1; loadPosts(); }
function fType(t){ type=t; page=1; document.querySelectorAll('[id^="t-"]').forEach(i=>i.classList.remove('active')); const el=document.getElementById('t-'+t); if(el)el.classList.add('active'); loadPosts(); }
// === LOCATION FILTER ===
var fProvData=[],fDistData=[];
(async function(){try{var r=await fetch("https://provinces.open-api.vn/api/?depth=1");fProvData=await r.json();var sel=document.getElementById("fProvince");var mSel=document.getElementById("mProv");fProvData.forEach(function(p){var o=document.createElement("option");o.value=p.name;o.textContent=p.name;sel.appendChild(o);var o2=document.createElement("option");o2.value=p.name;o2.textContent=p.name;mSel.appendChild(o2);});}catch(e){}})();

function onProvChange(){
  var name=document.getElementById("fProvince").value;
  var dSel=document.getElementById("fDistrict");
  dSel.innerHTML='<option value="">Qu\u1eadn/Huy\u1ec7n</option>';
  if(!name){dSel.style.display="none";document.getElementById("locClear").style.display="none";loadPosts();return;}
  dSel.style.display="";document.getElementById("locClear").style.display="";
  var prov=fProvData.find(function(p){return p.name===name;});
  if(prov){fetch("https://provinces.open-api.vn/api/p/"+prov.code+"?depth=2").then(function(r){return r.json();}).then(function(d){fDistData=d.districts||[];fDistData.forEach(function(dt){var o=document.createElement("option");o.value=dt.name;o.textContent=dt.name;dSel.appendChild(o);});});}
  loadPosts();
}
function onDistChange(){
  var dName=document.getElementById('fDistrict').value;
  var wSel=document.getElementById('fWard');
  wSel.innerHTML='<option value="">X\u00e3/Ph\u01b0\u1eddng</option>';
  if(!dName){wSel.style.display='none';loadPosts();return;}
  wSel.style.display='';
  var dist=fDistData.find(function(d){return d.name===dName;});
  if(dist){fetch('https://provinces.open-api.vn/api/d/'+dist.code+'?depth=2').then(function(r){return r.json();}).then(function(d){(d.wards||[]).forEach(function(w){var o=document.createElement('option');o.value=w.name;o.textContent=w.name;wSel.appendChild(o);});});}
  loadPosts();
}

// Modal province/district/ward
var mDistData=[],mWardData=[];
function onMProvChange(){
  var name=document.getElementById('mProv').value;
  var dR=document.getElementById('mDistRow');
  var wR=document.getElementById('mWardRow');
  var dSel=document.getElementById('mDist');
  var wSel=document.getElementById('mWard');
  dSel.innerHTML='<option value="">-- Ch\u1ECDn --</option>';
  wSel.innerHTML='<option value="">-- Ch\u1ECDn --</option>';
  if(!name){dR.style.display='none';wR.style.display='none';return;}
  dR.style.display='block';
  wR.style.display='none';
  var code=null;
  if(typeof fProvData!=='undefined'){for(var i=0;i<fProvData.length;i++){if(fProvData[i].name===name){code=fProvData[i].code;break;}}}
  if(!code){
    fetch('https://provinces.open-api.vn/api/?depth=1').then(function(r){return r.json();}).then(function(all){
      for(var i=0;i<all.length;i++){if(all[i].name===name){loadMDist(all[i].code);break;}}
    });
    return;
  }
  loadMDist(code);
}
function loadMDist(code){
  fetch('https://provinces.open-api.vn/api/p/'+code+'?depth=2').then(function(r){return r.json();}).then(function(d){
    mDistData=d.districts||[];
    var dSel=document.getElementById('mDist');
    mDistData.forEach(function(dt){var o=document.createElement('option');o.value=dt.name;o.textContent=dt.name;dSel.appendChild(o);});
  });
}
function onMDistChange(){
  var name=document.getElementById('mDist').value;
  var wR=document.getElementById('mWardRow');
  var wSel=document.getElementById('mWard');
  wSel.innerHTML='<option value="">-- Ch\u1ECDn --</option>';
  if(!name){wR.style.display='none';return;}
  wR.style.display='block';
  var dist=null;
  if(typeof mDistData!=='undefined'){dist=mDistData.find(function(d){return d.name===name;});}
  if(dist){fetch('https://provinces.open-api.vn/api/d/'+dist.code+'?depth=2').then(function(r){return r.json();}).then(function(d){
    (d.wards||[]).forEach(function(w){var o=document.createElement('option');o.value=w.name;o.textContent=w.name;wSel.appendChild(o);});
  });}
}
function clearLocFilter(){document.getElementById('fProvince').value='';document.getElementById('fDistrict').value='';document.getElementById('fWard').value='';document.getElementById('fDistrict').style.display='none';document.getElementById('fWard').style.display='none';document.getElementById('locClear').style.display='none';loadPosts();}


// Infinite scroll: auto-load when reaching bottom

async function _refreshPersonalized(){
  // Silently fetch PHP API to get user_liked/user_saved status
  var token=localStorage.getItem('token');
  if(!token)return;
  try{
    var r=await fetch('/api/posts.php?limit=40&sort='+sort,{headers:{'Authorization':'Bearer '+token}});
    var d=await r.json();
    if(d.success&&d.data&&d.data.posts){
      d.data.posts.forEach(function(p){
        var card=document.getElementById('P'+p.id);
        if(!card)return;
        // Update like button state
        if(p.user_liked){
          var btn=card.querySelector('.pa3-btn');
          if(btn&&!btn.classList.contains('pa3-active')){btn.classList.add('pa3-active','liked');}
        }
      });
    }
  }catch(e){}
}

var _infiniteObs = null;
function setupInfiniteScroll() {
    var btn = document.getElementById('loadMoreBtn');
    if (!btn || !('IntersectionObserver' in window)) return;
    if (_infiniteObs) _infiniteObs.disconnect();
    _infiniteObs = new IntersectionObserver(function(entries) {
        if (entries[0].isIntersecting && !_feedLoading && page < totalPg) {
            page++;
            loadPosts(true);
        }
    }, { rootMargin: '300px' });
    _infiniteObs.observe(btn);
}
// Setup after first load
var _origLoadPosts = loadPosts;

function setSort(s,btn){ sort=s; page=1; document.querySelectorAll('.sort-btn').forEach(b=>b.classList.remove('active')); btn.classList.add('active'); loadPosts(); }

function tryParse(s){try{return JSON.parse(s);}catch{return null;}}
function ytEmbed(u){const m=u.match(/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);return m?`https://www.youtube.com/embed/${m[1]}`:u;}

// vote function moved to reddit-mkpost.js
// saveP function moved to reddit-mkpost.js as savePost
function togShare(e,pid){
  e.stopPropagation();
  document.querySelectorAll('.share-drop').forEach(d=>d.remove());
  const url=location.origin+'/post-detail.html?id='+pid;
  const el=e.currentTarget;
  el.style.position='relative';
  const drop=document.createElement('div');
  drop.className='share-drop';
  drop.innerHTML=`
    <div class="share-opt" onclick="cpLink('${url}',${pid})"><i class="fas fa-link" style="color:#666"></i>Sao chép liên kết</div>
    <div class="share-opt" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}','_blank');shr(${pid})"><i class="fab fa-facebook" style="color:#1877F2"></i>Facebook</div>
    <div class="share-opt" onclick="window.open('https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}','_blank');shr(${pid})"><i class="fab fa-x-twitter"></i>Twitter/X</div>
    <div class="share-opt" onclick="navigator.share&&navigator.share({url:'${url}',title:'ShipperShop'});shr(${pid})"><i class="fas fa-ellipsis" style="color:#666"></i>Khác...</div>`;
  el.appendChild(drop);
  setTimeout(()=>document.addEventListener('click',()=>drop.remove(),{once:true}),10);
}
function cpLink(u,pid){navigator.clipboard.writeText(u).then(()=>toast('Đã sao chép!','success'));shr(pid);}
async function shr(pid){try{await fetch('/api/posts.php?action=share',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({post_id:pid})});}catch{}}

function togCmt(pid){
  const b=document.getElementById('C'+pid);
  if(b.classList.contains('open')){b.classList.remove('open');return;}
  b.classList.add('open'); loadCmts(pid);
}
async function loadCmts(pid){
  const b=document.getElementById('C'+pid);
  b.innerHTML='<div style="padding:12px;text-align:center;color:var(--muted)"><i class="fas fa-spinner spin"></i></div>';
  try{
    const r=await fetch(`/api/posts.php?action=comments&post_id=${pid}`,{credentials:'include'});
    const d=await r.json();
    if(d.success){
      let h=`<div class="cmt-form"><span class="avatar-sm" style="width:28px;height:28px;font-size:12px;flex-shrink:0">${CU?CU.fullname[0].toUpperCase():'?'}</span><textarea id="ct${pid}" placeholder="${CU?'Viết ghi chú...':'Đăng nhập để bình luận'}" ${!CU?'disabled':''}></textarea><button class="cmt-send" onclick="sendCmt(${pid})">Gửi</button></div>`;
      const flat=d.data||[];
      const map={},list=[];
      flat.forEach(c=>{map[c.id]=c;});
      function addFlat(cc,depth,parentName){list.push({c:cc,depth:Math.min(depth,1),replyTo:depth>0?parentName:null});var ch=flat.filter(x=>x.parent_id===cc.id);ch.forEach(r=>addFlat(r,depth+1,cc.user_name));}
      flat.filter(c=>!c.parent_id||c.parent_id<=0||!map[c.parent_id]).forEach(c=>addFlat(c,0,null));
      if(!list.length) h+='<p style="text-align:center;color:var(--muted);font-size:13px;padding:12px">Chưa có ghi chú nào</p>';
      else h+=list.map(item=>mkCmt(item.c,pid,item.depth,item.replyTo)).join('');
      b.innerHTML=h;
    }
  }catch{b.innerHTML='<p style="color:red;padding:8px;font-size:13px">Lỗi tải comments</p>';}
}
function mkCmt(c,pid,depth,replyTo){
  depth=depth||0;
  var sz=depth>0?22:32;
  var indent=depth>0?'padding:3px 0 3px 20px;border-left:2px solid #e4e6eb;margin-left:16px':'padding:4px 0';
  var av=c.user_avatar?'<img src="'+c.user_avatar+'" style="width:'+sz+'px;height:'+sz+'px;border-radius:50%;object-fit:cover;flex-shrink:0" onerror="this.style.display=\'none\'" loading=lazy>':'<div style="width:'+sz+'px;height:'+sz+'px;border-radius:50%;background:#e4e6eb;display:flex;align-items:center;justify-content:center;font-size:'+(depth>0?10:12)+'px;font-weight:700;flex-shrink:0">'+((c.user_name||'?')[0].toUpperCase())+'</div>';
  var liked=c.user_vote==='up'||c.user_liked;
  var lkCnt=c.likes_count||0;
  var rplTag=replyTo?'<span style="color:#7C3AED;font-size:11px">↩ '+esc(replyTo)+'</span> ':'';
  return '<div class="cmt-item" style="'+indent+'">'+
    '<a href="user.html?id='+c.user_id+'" style="flex-shrink:0;text-decoration:none">'+av+'</a>'+
    '<div class="cmt-body">'+
      '<div class="cmt-bubble">'+
        '<a href="user.html?id='+c.user_id+'" class="cmt-author" style="text-decoration:none;color:inherit">'+esc(c.user_name||'Ẩn danh')+'</a>'+
        '<div class="cmt-text">'+rplTag+esc(c.content)+'</div>'+
      '</div>'+
      '<div class="cmt-meta">'+
        '<span class="cmt-time">'+ago(c.created_at)+'</span>'+
        '<a class="'+(liked?'liked':'')+'" onclick="likeCmt('+c.id+',this);return false">Thành công'+(lkCnt>0?' · '+lkCnt:'')+'</a>'+
        '<a onclick="showRpl('+c.id+','+pid+');return false">Ghi chú</a>'+
      '</div>'+
      '<div id="rbox'+c.id+'"></div>'+
    '</div>'+
  '</div>';
}

async function likeCmt(cid,btn){
  if(!CU){toast('Đăng nhập!','warning');return;}
  try{
    const r=await fetch('/api/posts.php?action=vote_comment',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({comment_id:cid,vote_type:'up'})});
    const d=await r.json();
    if(d.success){
      const liked=d.data.user_vote==='up';
      btn.className=liked?'liked':'';
      btn.textContent='Thành công'+(d.data.score>0?' · '+d.data.score:'');
    }
  }catch(e){toast('Lỗi','error');}
}

function showRpl(cid,pid){
  if(!CU){toast('Đăng nhập để trả lời!','warning');return;}
  document.getElementById('rbox'+cid).innerHTML=`<div style="display:flex;gap:6px;margin-top:6px"><textarea id="rt${cid}" style="flex:1;padding:6px;border:1px solid var(--border);border-radius:4px;font-size:13px;min-height:48px;resize:vertical;font-family:inherit;outline:none" placeholder="Trả lời..."></textarea><button class="cmt-send" style="height:fit-content" onclick="sendRpl(${cid},${pid})">Gửi</button></div>`;
  setTimeout(()=>document.getElementById('rt'+cid).focus(),50);
}
async function sendRpl(cid,pid){
  const ta=document.getElementById('rt'+cid); const ct=ta.value.trim(); if(!ct) return;
  try{const r=await fetch('/api/posts.php?action=comment',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({post_id:pid,parent_id:cid,content:ct})});const d=await r.json();if(d.success){loadCmts(pid);}else toast(d.message,'error');}catch{toast('Lỗi','error');}
}
async function vCmt(cid,type,btn){
  if(!CU){toast('Đăng nhập để vote!','warning');return;}
  try{const r=await fetch('/api/posts.php?action=vote_comment',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({comment_id:cid,vote_type:'up'})});const d=await r.json();if(d.success){const liked=d.data.user_vote==='up';btn.className=liked?'liked':'';btn.textContent='Thành công'+(d.data.score>0?' · '+d.data.score:'');}}catch{}
}
async function sendCmt(pid){
  if(!CU){toast('Đăng nhập để bình luận!','warning');return;}
  const ta=document.getElementById('ct'+pid); const ct=ta.value.trim(); if(!ct){toast('Nhập nội dung!','warning');return;}
  try{const r=await fetch('/api/posts.php?action=comment',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({post_id:pid,content:ct})});const d=await r.json();if(d.success){ta.value='';const nc=document.getElementById('nc'+pid);if(nc)nc.textContent=parseInt(nc.textContent)+1;loadCmts(pid);}else toast(d.message,'error');}catch{toast('Lỗi kết nối','error');}
}
// delP → moved to reddit-mkpost JS

async function loadTrend(){
  try{const r=await fetch('/api/posts.php?sort=top&limit=5');const d=await r.json();if(d.success&&d.data.posts.length){document.getElementById('trendBox').innerHTML='<div class="sidebar-title" style="padding:10px 12px">🔥 Đang thịnh hành</div>'+d.data.posts.map(p=>`<div class="trend-item" onclick="scrollTo2(${p.id})"><div class="trend-title">${esc((p.title||p.content).substring(0,80))}${(p.title||p.content).length>80?'...':''}</div><div class="trend-meta">▲${fN(p.score||0)} · ${fN(p.comments_count||0)} bình luận</div></div>`).join('');}}catch{}
}

// Load trending hashtags

// Load follow suggestions
async function loadSuggestions(){
  var token=localStorage.getItem('token');
  if(!token)return;
  try{
    var r=await fetch('/api/friends.php?action=suggestions&limit=5',{headers:{'Authorization':'Bearer '+token}});
    var d=await r.json();
    if(d.success&&d.data&&d.data.length){
      var box=document.getElementById('suggestBox');
      if(!box)return;
      var html='<div class="sidebar-title" style="padding:10px 12px">👥 Gợi ý theo dõi</div>';
      d.data.forEach(function(u){
        var av=u.avatar?'<img src="'+u.avatar+'" style="width:36px;height:36px;border-radius:50%;object-fit:cover">':'<div style="width:36px;height:36px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px">'+(u.fullname||'U')[0]+'</div>';
        html+='<div style="display:flex;align-items:center;gap:10px;padding:8px 12px"><a href="user.html?id='+u.id+'" style="text-decoration:none">'+av+'</a><div style="flex:1;min-width:0"><a href="user.html?id='+u.id+'" style="font-weight:600;font-size:13px;color:#333;text-decoration:none">'+esc(u.fullname)+'</a><div style="font-size:11px;color:#999">'+(u.shipping_company||'Shipper')+'</div></div><button onclick="quickFollow('+u.id+',this)" style="padding:4px 12px;border:1px solid #7C3AED;border-radius:6px;background:#fff;color:#7C3AED;font-size:12px;font-weight:600;cursor:pointer">Theo dõi</button></div>';
      });
      box.innerHTML=html;
    }
  }catch(e){}
}
function quickFollow(uid,btn){
  var token=localStorage.getItem('token');
  fetch('/api/social.php?action=follow',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({user_id:uid})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){btn.textContent='Đã theo dõi';btn.style.background='#7C3AED';btn.style.color='#fff';btn.disabled=true;}
  });
}

async function loadHashtags(){
  try{
    var r=await fetch('/api/trending.php?action=hashtags');
    var d=await r.json();
    if(d.success&&d.data&&d.data.length){
      var box=document.getElementById('hashtagBox');
      if(!box)return;
      var html='<div class="sidebar-title" style="padding:10px 12px"># Hashtag phổ biến</div><div style="padding:4px 12px 12px;display:flex;flex-wrap:wrap;gap:6px">';
      d.data.forEach(function(h){
        html+='<span onclick="doSearch(\'#'+h.tag+'\')" style="padding:4px 10px;background:#f0f0f0;border-radius:16px;font-size:12px;cursor:pointer;color:#333">#'+h.tag+' <span style=\'color:#999;font-size:11px\'>'+h.count+'</span></span>';
      });
      html+='</div>';
      box.innerHTML=html;
    }
  }catch(e){}
}

async function scrollTo2(id){
  var el=document.getElementById('P'+id);
  if(el){el.scrollIntoView({behavior:'smooth',block:'center'});el.style.background='#FFF8E1';setTimeout(function(){el.style.background='';},3000);return;}
  try{
    var r=await fetch('/api/posts.php?id='+id,{credentials:'include'});
    var d=await r.json();
    if(d.success&&d.data){
      var feed=document.getElementById('feed');
      var tmp=document.createElement('div');
      tmp.innerHTML=mkPost(d.data);
      feed.insertBefore(tmp.firstElementChild,feed.firstChild);
      var el2=document.getElementById('P'+id);
      if(el2){el2.scrollIntoView({behavior:'smooth',block:'center'});el2.style.background='#FFF8E1';setTimeout(function(){el2.style.background='';},3000);}
    }
  }catch(e){console.log('scrollTo2 err',e);}
}

// MODAL
function openModal(type){
  if(!CU){toast('Đăng nhập để đăng bài!','warning');setTimeout(function(){location='login.html'},1000);return;}
  if(typeof openSPM==="function"){openSPM();return;}
}
function closeModal(){document.getElementById('modal').classList.remove('open');imgs=[];document.getElementById('mImgPrev').innerHTML='';document.getElementById('mContent').value='';document.getElementById('mTitle2').value='';document.getElementById('cnt').textContent=0;}
function setPType(t,btn){
  ptype=t;
  document.querySelectorAll('.type-tab').forEach(b=>b.classList.remove('active'));
  const map={post:'📝 Bài viết',review:'⭐ Review',question:'❓ Hỏi đáp',tip:'💡 Mẹo hay',discussion:'💬 Thảo luận',confession:'🎭 Confession ẩn danh'};
  document.getElementById('mTitle').innerHTML=`<i class="fas fa-pen"></i> ${map[t]||'Tạo bài viết'}`;
  document.getElementById('confNote').style.display=t==='confession'?'block':'none';
  document.getElementById('mAnon').checked=t==='confession';
  // find matching button
  const tabs=document.querySelectorAll('.type-tab');
  tabs.forEach(b=>{if(b.textContent.toLowerCase().includes(t.substring(0,4)))b.classList.add('active');});
}
function cCnt(){const v=document.getElementById('mContent').value.length;document.getElementById('cnt').textContent=v;document.getElementById('cnt').style.color=v>4800?'red':'';}
function prevImgs(inp){
  imgs=[];document.getElementById('mImgPrev').innerHTML='';
  Array.from(inp.files).slice(0,4).forEach(f=>{const rd=new FileReader();rd.onload=e=>{imgs.push(e.target.result);const im=document.createElement('img');im.src=e.target.result;im.className='img-prev';document.getElementById('mImgPrev').appendChild(im);};rd.readAsDataURL(f);});
}
async function uploadVideo(file){
  const fd=new FormData();
  fd.append('video',file);
  fd.append('token',localStorage.getItem('token'));
  const r=await fetch('/api/upload-video.php',{method:'POST',body:fd,credentials:'include'});
  const d=await r.json();
  if(d.success) return d.url;
  throw new Error(d.message||'Upload failed');
}
function renderVideo(url){
  if(!url)return '';
  if(url.includes('/uploads/videos/')){
    return '<video controls playsinline preload="metadata" class="post-video" style="width:100%;max-height:600px;border-radius:0;display:block"><source src="'+url+'" type="video/mp4">Video không hỗ trợ</video>';
  }
  var eid=url;
  if(url.includes('youtube.com/watch'))eid='https://www.youtube.com/embed/'+url.split('v=')[1].split('&')[0];
  else if(url.includes('youtu.be/'))eid='https://www.youtube.com/embed/'+url.split('youtu.be/')[1].split('?')[0];
  else if(url.includes('tiktok.com'))return '<a href="'+url+'" target="_blank" style="display:block;padding:12px;background:#f5f5f5;border-radius:8px;text-decoration:none;color:#333;margin:8px 0"><i class="fab fa-tiktok"></i> Xem video trên TikTok</a>';
  return '<iframe src="'+eid+'" style="width:100%;aspect-ratio:16/9;border:none;border-radius:8px;margin:8px 0" allowfullscreen></iframe>';
}
async function submitPost(){
  if(!CU){toast('Đăng nhập đi!','warning');return;}
  const ct=document.getElementById('mContent').value.trim();
  if(!ct){toast('Nhập nội dung bài viết!','warning');return;}
  const btn=document.getElementById('subBtn');btn.disabled=true;btn.textContent='Đang đăng...';
  try{
    var videoUrl=document.getElementById('mVideo').value.trim()||null;
    var vf=document.getElementById('mVideoFile');
    if(vf&&vf.files&&vf.files[0]){try{var fd=new FormData();fd.append('video',vf.files[0]);var vr=await fetch('/api/upload-video.php',{method:'POST',headers:{'Authorization':'Bearer '+localStorage.getItem('token')},body:fd});var vd=await vr.json();if(vd.success)videoUrl=vd.url;else toast('Upload lỗi: '+(vd.message||''),'warning');}catch(ve){toast('Lỗi upload video','error');}}
    const r=await fetch('/api/posts.php',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+localStorage.getItem('token')},credentials:'include',body:JSON.stringify({content:ct,type:ptype,title:document.getElementById('mTitle2').value.trim()||null,province:document.getElementById('mProv').value||null,district:document.getElementById('mDist')?document.getElementById('mDist').value||null:null,ward:document.getElementById('mWard')?document.getElementById('mWard').value||null:null,is_anonymous:document.getElementById('mAnon').checked?1:0,video_url:videoUrl,images:imgs.length?imgs:null})});
    const d=await r.json();
    if(d.success){toast('🎉 Đăng bài thành công!','success');closeModal();setTimeout(()=>loadPosts(),600);}
    else if(!handleApiError(d))toast(d.message||'Lỗi','error');
  }catch{toast('Lỗi kết nối','error');}
  finally{btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane"></i> Đăng bài';}
}

// Lightbox
// openLb/closeLb provided by image-viewer.js


// Helpers
function xpnd(id){const e=document.getElementById('pc-'+id);e.classList.add('full');e.nextElementSibling&&e.nextElementSibling.classList.contains('show-more')&&e.nextElementSibling.remove();}
function esc(t){if(!t)return'';return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function fN(n){n=parseInt(n)||0;if(n>=1000)return(n/1000).toFixed(1)+'K';return n.toString();}
function ago(dt){if(!dt)return'';const s=Math.floor((new Date()-new Date(dt.replace(' ','T')))/1000);if(s<60)return s+'s';if(s<3600)return Math.floor(s/60)+'m';if(s<86400)return Math.floor(s/3600)+'h';if(s<604800)return Math.floor(s/86400)+'d';return new Date(dt).toLocaleDateString('vi-VN');}
function toast(msg,type='success'){const t=document.getElementById('toast');t.textContent=msg;t.className=`toast ${type} show`;setTimeout(()=>t.classList.remove('show'),2800);}

const up=new URLSearchParams(location.search);
if(up.get('post'))setTimeout(function(){scrollTo2(parseInt(up.get('post')));},2000);
if(up.get('type'))fType(up.get('type'));
function handleFab(){if(!CU){toast('Đăng nhập để đăng bài!','warning');setTimeout(function(){location='login.html'},1000);return;}if(typeof openSPM==="function")openSPM();}

function activateChip(btn){
  document.querySelectorAll('.chip').forEach(function(c){c.classList.remove('active');});
  btn.classList.add('active');
}

// Scroll to top button
(function(){
  var btn=document.createElement('button');
  btn.id='scrollTopBtn';
  btn.innerHTML='<i class="fas fa-arrow-up"></i>';
  btn.style.cssText='position:fixed;bottom:80px;right:16px;width:40px;height:40px;border-radius:50%;background:#7C3AED;color:#fff;border:none;font-size:16px;cursor:pointer;z-index:999;display:none;box-shadow:0 2px 8px rgba(0,0,0,.2);transition:opacity .2s';
  btn.onclick=function(){window.scrollTo({top:0,behavior:'smooth'});};
  document.body.appendChild(btn);
  var _lastY=0;
  window.addEventListener('scroll',function(){
    var y=window.scrollY;
    btn.style.display=y>600?'flex':'none';
    if(btn.style.display==='flex'){btn.style.alignItems='center';btn.style.justifyContent='center';}
    _lastY=y;
  },{passive:true});
})();

// Load site announcement
function loadAnnouncement(){
  fetch('/api/admin-moderation.php?action=announcement')
    .then(function(r){return r.json()})
    .then(function(d){
      var el=document.getElementById('siteBanner');
      if(!el)return;
      if(d.success&&d.data&&d.data.text){
        var colors={info:'#7C3AED',warning:'#F59E0B',success:'#00b14f'};
        var bg={info:'#f5f3ff',warning:'#fffbeb',success:'#f0fdf4'};
        var type=d.data.type||'info';
        el.innerHTML='<div style="padding:10px 16px;background:'+(bg[type]||bg.info)+';border-left:4px solid '+(colors[type]||colors.info)+';font-size:13px;color:#333;display:flex;align-items:center;gap:8px"><i class="fas fa-bullhorn" style="color:'+(colors[type]||colors.info)+'"></i><span style="flex:1">'+d.data.text+'</span><button onclick="this.parentNode.remove()" style="background:none;border:none;font-size:16px;cursor:pointer;color:#999">&times;</button></div>';
      }else{el.innerHTML='';}
    }).catch(function(){});
}

// Pull-to-refresh indicator
(function(){
  var startY=0,pulling=false,indicator=null;
  function getPTR(){
    if(!indicator){
      indicator=document.createElement('div');
      indicator.id='ptrIndicator';
      indicator.style.cssText='position:fixed;top:0;left:50%;transform:translateX(-50%) translateY(-50px);z-index:1001;transition:transform .2s;pointer-events:none';
      indicator.innerHTML='<div style="width:36px;height:36px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.2)"><i class="fas fa-arrow-down" id="ptrIcon"></i></div>';
      document.body.appendChild(indicator);
    }
    return indicator;
  }
  document.addEventListener('touchstart',function(e){
    if(window.scrollY<5){startY=e.touches[0].clientY;pulling=true;}
  },{passive:true});
  document.addEventListener('touchmove',function(e){
    if(!pulling)return;
    var dy=e.touches[0].clientY-startY;
    if(dy>0&&dy<150&&window.scrollY<5){
      var ptr=getPTR();
      ptr.style.transform='translateX(-50%) translateY('+(Math.min(dy*0.5,60)-10)+'px)';
      var icon=document.getElementById('ptrIcon');
      if(icon){icon.className=dy>80?'fas fa-sync-alt':'fas fa-arrow-down';if(dy>80)icon.style.animation='spin .5s linear infinite';}
    }
  },{passive:true});
  document.addEventListener('touchend',function(){
    if(!pulling)return;
    pulling=false;
    var ptr=getPTR();
    var dy=parseInt(ptr.style.transform.match(/translateY\(([\d.]+)px/)||[0,0])[1]||0;
    ptr.style.transform='translateX(-50%) translateY(-50px)';
    var icon=document.getElementById('ptrIcon');
    if(icon)icon.style.animation='';
    if(dy>50){page=1;loadPosts();}
  },{passive:true});
})();

// Friends' latest posts
async function loadFriendsLatest(){
  var token=localStorage.getItem('token');
  if(!token)return;
  try{
    var r=await fetch('/api/posts.php?sort=following&limit=3',{headers:{'Authorization':'Bearer '+token}});
    var d=await r.json();
    if(d.success&&d.data&&d.data.posts&&d.data.posts.length){
      var box=document.getElementById('friendsLatest');
      if(!box)return;
      var html='<div class="sidebar-title" style="padding:10px 12px">📨 Bạn bè đăng gần đây</div>';
      d.data.posts.forEach(function(p){
        var av=p.user_avatar?'<img src="'+p.user_avatar+'" style="width:28px;height:28px;border-radius:50%;object-fit:cover">':'';
        html+='<a href="post-detail.html?id='+p.id+'" style="display:flex;gap:8px;padding:6px 12px;text-decoration:none;color:#333">'+av+'<div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:600">'+esc(p.user_name)+'</div><div style="font-size:11px;color:#65676B;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+esc((p.content||'').substring(0,60))+'</div></div></a>';
      });
      box.innerHTML=html;
    }
  }catch(e){}
}

// Time-based greeting
function timeGreeting(){
  var h=new Date().getHours();
  var user=JSON.parse(localStorage.getItem('user')||'{}');
  var name=user.fullname?user.fullname.split(' ').pop():'';
  var greet=h<12?'Chào buổi sáng':(h<18?'Chào buổi chiều':'Chào buổi tối');
  var el=document.getElementById('feedGreeting');
  if(el&&name)el.textContent=greet+', '+name+'! 👋';
}

// Keyboard shortcuts (desktop)
document.addEventListener('keydown',function(e){
  if(e.target.tagName==='INPUT'||e.target.tagName==='TEXTAREA')return;
  if(e.key==='n'||e.key==='N'){e.preventDefault();if(typeof openSPM==='function')openSPM();else if(typeof openModal==='function')openModal();}
  if(e.key==='/'||e.key==='s'){e.preventDefault();var si=document.getElementById('sInput');if(si){si.focus();var so=document.getElementById('searchOverlay');if(so)so.style.display='flex';}}
  if(e.key==='g'){location.href='groups.html';}
  if(e.key==='m'){location.href='messages.html';}
  if(e.key==='t'){window.scrollTo({top:0,behavior:'smooth'});}
});

function showEmptyFeed(){
  var feed=document.getElementById('feed');
  if(feed&&feed.children.length===0){
    feed.innerHTML='<div style="text-align:center;padding:60px 20px"><div style="font-size:48px;margin-bottom:12px">📭</div><div style="font-size:18px;font-weight:700;color:#333;margin-bottom:8px">Chưa có bài viết nào</div><div style="font-size:14px;color:#65676B;margin-bottom:16px">Hãy là người đầu tiên chia sẻ!</div><button onclick="if(typeof openSPM===\'function\')openSPM()" style="padding:10px 24px;background:#7C3AED;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer">Tạo bài viết</button></div>';
  }
}

// Track post impressions (lightweight, batched)
var _impressionQueue=[];
var _impressionTimer=null;
function trackImpression(postId){
  if(_impressionQueue.indexOf(postId)<0)_impressionQueue.push(postId);
  if(!_impressionTimer){
    _impressionTimer=setTimeout(function(){
      if(_impressionQueue.length){
        var ids=_impressionQueue.splice(0,20);
        navigator.sendBeacon('/api/posts.php?action=impressions',JSON.stringify({post_ids:ids}));
      }
      _impressionTimer=null;
    },5000);
  }
}
// Observe post cards for impressions
var _impObs=null;
function setupImpressionTracking(){
  if(!('IntersectionObserver' in window))return;
  _impObs=new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(e.isIntersecting&&e.target.id){
        var pid=parseInt(e.target.id.replace('P',''));
        if(pid)trackImpression(pid);
        _impObs.unobserve(e.target);
      }
    });
  },{threshold:0.5});
  document.querySelectorAll('.post-card[id]').forEach(function(el){_impObs.observe(el);});
}
