// ShipperShop activity-log.html

var CU=JSON.parse(localStorage.getItem('user')||'null');
if(!CU)location.href='login.html';
var curFilter='all',curFrom='',curTo='',curPage=1,curCategory='';
var filterStep='main';
var titles={'comments':'Bình luận và cảm xúc','posts':'Bài viết','saved':'Bộ sưu tập và mục đã lưu','likes':'Lượt thích'};
var filterMap={'comments':'comments','posts':'posts','saved':'saved','likes':'likes'};

// Auto-open on page load
var autoOpen=new URLSearchParams(location.search).get('cat');
if(autoOpen){setTimeout(function(){openCategory(autoOpen);},0);}

function openCategory(cat){
  curCategory=cat;
  if(cat==='comments')curFilter='all';
  else curFilter=cat;
  curPage=1;
  document.getElementById('mainMenu').style.display='none';
  document.getElementById('activityView').style.display='block';
  document.getElementById('actTitle').textContent=titles[cat]||'Hoạt động';
  loadActivities();
}

function backToMenu(){document.getElementById("activityView").style.display="none";document.getElementById("mainMenu").style.display="block";window.scrollTo(0,0);}

async function loadActivities(append){
  var list=document.getElementById('activityList');
  if(!append){list.innerHTML='<div class="empty"><i class="fas fa-spinner spin"></i></div>';curPage=1;}
  var url='/api/activity-log.php?filter='+curFilter+'&page='+curPage;
  if(curFrom)url+='&from='+curFrom;
  if(curTo)url+='&to='+curTo;
  try{
    var r=await fetch(url,{credentials:'include'});
    var d=await r.json();
    if(!d.success){list.innerHTML='<div class="empty"><i class="fas fa-exclamation-circle"></i><p>'+d.message+'</p></div>';return;}
    var acts=d.data.activities;
    var keys=Object.keys(acts);
    if(!keys.length&&!append){list.innerHTML='<div class="empty"><i class="fas fa-clock-rotate-left" style="font-size:48px;color:#ddd"></i><p style="margin-top:12px">Chưa có hoạt động nào</p></div>';document.getElementById('loadMoreBtn').style.display='none';return;}
    var html=append?list.innerHTML.replace(/<div class="empty">.*?<\/div>/g,''):'';
    keys.forEach(function(date){
      var d2=new Date(date);
      var label=d2.toLocaleDateString('vi-VN',{day:'numeric',month:'long',year:'numeric'});
      var today=new Date().toISOString().slice(0,10);
      var yesterday=new Date(Date.now()-86400000).toISOString().slice(0,10);
      if(date===today)label='Hôm nay';
      else if(date===yesterday)label='Hôm qua';
      html+='<div class="date-group"><div class="date-label">'+label+'</div>';
      acts[date].forEach(function(a){html+=renderActivity(a);});
      html+='</div>';
    });
    list.innerHTML=html;
    document.getElementById('loadMoreBtn').style.display=d.data.has_more?'block':'none';
  }catch(e){if(!append)list.innerHTML='<div class="empty"><p>Lỗi kết nối</p></div>';}
}

// Save scroll before navigate
function goToPost(pid){
  sessionStorage.setItem("act_scroll",String(window.scrollY));
  sessionStorage.setItem("act_filter",curFilter);
  sessionStorage.setItem("act_category",curCategory);
  sessionStorage.setItem("act_html",document.getElementById("activityList").innerHTML);
  location.href="post-detail.html?id="+pid;
}
window.addEventListener("pageshow",function(e){
  var saved=sessionStorage.getItem("act_scroll");
  var shtml=sessionStorage.getItem("act_html");
  if(shtml){document.getElementById("activityList").innerHTML=shtml;sessionStorage.removeItem("act_html");}
  if(saved){window.scrollTo(0,parseInt(saved));sessionStorage.removeItem("act_scroll");}
  var sf=sessionStorage.getItem("act_filter");
  var sc=sessionStorage.getItem("act_category");
  if(sf){curFilter=sf;sessionStorage.removeItem("act_filter");}
  if(sc){curCategory=sc;sessionStorage.removeItem("act_category");}
});
function renderActivity(a){
  var icon='',badge='',thumb='';
  if(a.type==='post'){icon='<i class="fas fa-pen"></i>';badge='<div class="act-badge post"><i class="fas fa-pen" style="font-size:9px"></i></div>';}
  else if(a.type==='comment'){icon='<i class="fas fa-comment"></i>';badge='<div class="act-badge comment"><i class="fas fa-comment" style="font-size:9px"></i></div>';}
  else if(a.type==='like'){icon='<i class="fas fa-thumbs-up"></i>';badge='<div class="act-badge like"><i class="fas fa-thumbs-up" style="font-size:9px"></i></div>';}
  else if(a.type==='save'){icon='<i class="fas fa-bookmark"></i>';badge='<div class="act-badge save"><i class="fas fa-bookmark" style="font-size:9px"></i></div>';}
  
  var avatar='';
  if(a.post_author_avatar)avatar='<div style="position:relative"><img src="'+a.post_author_avatar+'" class="act-avatar" onerror="this.className=\'act-avatar-ph\';this.innerHTML=\'?\'" loading=lazy>';
  else avatar='<div style="position:relative"><div class="act-avatar-ph">'+icon+'</div>';
  avatar+=badge+'</div>';
  
  var imgs=[];
  try{if(a.images)imgs=JSON.parse(a.images);}catch(x){}
  if(imgs&&imgs.length)thumb='<img src="'+imgs[0]+'" class="act-thumb" onerror="this.remove()" loading=lazy>';
  
  var preview=esc(a.content||a.post_content||'').substring(0,100);
  var time=new Date(a.created_at).toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});
  
  return '<div class="activity-item" onclick="goToPost('+a.post_id+')">'+avatar+'<div class="act-info"><div class="act-action">'+esc(a.action)+'</div><div class="act-preview">'+preview+'</div><div class="act-time"><i class="far fa-clock"></i> '+time+'</div></div>'+thumb+'<button class="act-menu"><i class="fas fa-ellipsis-h"></i></button></div>';
}

function loadMore(){curPage++;loadActivities(true);}

// Filter sheets
function openFilterType(){
  filterStep='main';
  document.getElementById('filterBg').classList.add('open');
  var sheet=document.getElementById('filterSheet');
  sheet.classList.add('open');
  renderFilterMain();
}
function closeFilter(){
  document.getElementById('filterBg').classList.remove('open');
  document.getElementById('filterSheet').classList.remove('open');
}
function renderFilterMain(){
  var fc=document.getElementById('filterContent');
  fc.innerHTML='<div class="sheet-title">Bộ lọc</div><div class="sheet-item" onclick="openFilterCategory()"><i class="fas fa-list"></i><span>Hạng mục</span><span style="color:var(--muted);font-size:13px">'+(curFilter==='all'?'Tất cả':titles[curFilter]||curFilter)+'</span><i class="fas fa-chevron-right" style="color:var(--muted)"></i></div><div class="sheet-item" onclick="openFilterDate()"><i class="fas fa-calendar"></i><span>Ngày</span><span style="color:var(--muted);font-size:13px">'+(curFrom||curTo?(curFrom||'...')+' → '+(curTo||'...'):'Tất cả')+'</span><i class="fas fa-chevron-right" style="color:var(--muted)"></i></div>';
}
function openFilterCategory(){
  var fc=document.getElementById('filterContent');
  var opts=[{v:'all',l:'Tất cả',i:'fas fa-list'},{v:'comments',l:'Bình luận',i:'far fa-comment'},{v:'likes',l:'Lượt thích và cảm xúc',i:'far fa-thumbs-up'}];
  if(curCategory==='posts')opts=[{v:'posts',l:'Tất cả bài viết',i:'fas fa-list'}];
  if(curCategory==='saved')opts=[{v:'saved',l:'Tất cả đã lưu',i:'fas fa-list'}];
  fc.innerHTML='<div style="display:flex;align-items:center;padding:12px 16px"><button onclick="renderFilterMain()" style="background:none;border:none;font-size:18px;cursor:pointer"><i class="fas fa-chevron-left"></i></button><div class="sheet-title" style="flex:1;padding:0">Hạng mục</div></div>'+opts.map(function(o){return '<div class="sheet-item" onclick="setFilter(\''+o.v+'\')"><i class="'+o.i+'"></i><span>'+o.l+'</span>'+(curFilter===o.v?'<i class="fas fa-check check"></i>':'')+'</div>';}).join('');
}
function openFilterDate(){
  var fc=document.getElementById('filterContent');
  fc.innerHTML='<div style="display:flex;align-items:center;padding:12px 16px"><button onclick="renderFilterMain()" style="background:none;border:none;font-size:18px;cursor:pointer"><i class="fas fa-chevron-left"></i></button><div class="sheet-title" style="flex:1;padding:0">Lọc theo ngày</div><span class="sheet-done" onclick="applyDateFilter()">Xong</span></div><div class="date-sheet"><div class="date-row" onclick="pickDate(\'from\')"><span>Ngày bắt đầu</span><span style="color:var(--muted)">'+(curFrom||'Chọn')+'</span><i class="fas fa-chevron-right"></i></div><div class="date-row" onclick="pickDate(\'to\')"><span>Ngày kết thúc</span><span style="color:var(--muted)">'+(curTo||'Chọn')+'</span><i class="fas fa-chevron-right"></i></div></div>';
}
function pickDate(which){
  var val=prompt(which==='from'?'Ngày bắt đầu (YYYY-MM-DD):':'Ngày kết thúc (YYYY-MM-DD):',which==='from'?curFrom:curTo);
  if(val!==null){if(which==='from')curFrom=val;else curTo=val;openFilterDate();}
}
function applyDateFilter(){closeFilter();curPage=1;loadActivities();}
function setFilter(f){curFilter=f;closeFilter();curPage=1;loadActivities();}

function esc(t){if(!t)return'';return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
