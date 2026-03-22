// ShipperShop Groups Page — Main logic
var CU=JSON.parse(localStorage.getItem("user")||"null"); var searchTimer=null;
var categories=[];

function toast(m){var t=document.getElementById("toast");t.textContent=m;t.className="toast show";setTimeout(function(){t.className="toast";},2500);}
function esc(s){if(!s)return"";return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");}
function fN(n){n=parseInt(n)||0;if(n>=1000000)return(n/1000000).toFixed(1)+"tr";if(n>=1000)return(n/1000).toFixed(1)+"k";return n;}

function toggleSearch(){
  document.body.classList.toggle("search-active");
  var sb=document.getElementById("searchBox");
  if(document.body.classList.contains("search-active")){sb.style.display="block";sb.focus();}
  else{sb.style.display="none";sb.value="";document.getElementById("searchResults").innerHTML="";}
}

function onSearch(v){
  clearTimeout(searchTimer);
  if(v.trim().length<2){document.getElementById("searchResults").innerHTML="<p style='text-align:center;color:var(--muted);padding:20px'>Nhập ít nhất 2 ký tự</p>";return;}
  searchTimer=setTimeout(function(){
    fetch("/api/groups.php?action=search&q="+encodeURIComponent(v.trim())).then(function(r){return r.json();}).then(function(d){
      if(!d.success||!d.data.length){document.getElementById("searchResults").innerHTML="<p style='text-align:center;color:var(--muted);padding:40px'>Không tìm thấy</p>";return;}
      document.getElementById("searchResults").innerHTML="<div class='g-list'>"+d.data.map(function(g){return mkGroupCard(g);}).join("")+"</div>";
    });
  },400);
}

function mkGroupCard(g){
  var icon=g.icon_image?"<img src='"+esc(g.icon_image)+"' onerror=\"this.style.display='none';this.parentNode.innerHTML='<span>"+esc((g.name||"G")[0])+"</span>'\">":"<span>"+esc((g.name||"G")[0])+"</span>";
  var joined=g.is_member;
  var btnCls=joined?"joined":"join";
  var btnTxt=joined?"Đã tham gia":"Tham gia";
  var members=fN(g.member_count)+" thành viên";
  var desc=g.description?(g.description.length>50?g.description.substring(0,50)+"...":g.description):"";
  return "<div class='g-card'>"+
    "<div class='g-icon' style='background:"+(g.banner_color||"var(--primary)")+"'>"+icon+"</div>"+
    "<div class='g-info' onclick='goGroup(\""+esc(g.slug)+"\")' style='cursor:pointer'>"+
      "<div class='g-name'>"+esc(g.name)+"</div>"+
      "<div class='g-meta'>"+members+(desc?" · "+esc(desc):"")+"</div>"+
    "</div>"+
    "<button class='g-join "+btnCls+"' onclick='joinGroup("+g.id+",this)'>"+btnTxt+"</button>"+
  "</div>";
}

function mkScrollCard(g){
  var icon=g.icon_image?"<img src='"+esc(g.icon_image)+"' onerror=\"this.style.display='none';this.parentNode.innerHTML='<span>"+esc((g.name||"G")[0])+"</span>'\">":"<span>"+esc((g.name||"G")[0])+"</span>";
  var joined=g.is_member;
  return "<div class='g-scroll-card' onclick='goGroup(\""+esc(g.slug)+"\")' style='cursor:pointer'>"+
    "<div style='display:flex;gap:8px;align-items:center'>"+
      "<div class='g-icon' style='background:"+(g.banner_color||"var(--primary)")+"'>"+icon+"</div>"+
      "<div style='flex:1;min-width:0'>"+
        "<div class='g-name'>"+esc(g.name)+"</div>"+
        "<div class='g-meta'>"+fN(g.member_count)+" thành viên</div>"+
      "</div>"+
      "<button class='g-join "+(joined?"joined":"join")+"' onclick='event.stopPropagation();joinGroup("+g.id+",this)' style='font-size:11px;padding:4px 10px'>"+(joined?"Đã gia nhập":"Tham gia")+"</button>"+
    "</div>"+
    "<div class='g-desc'>"+esc(g.description||"")+"</div>"+
  "</div>";
}

function goGroup(slug){location.href="group.html?slug="+slug;}

function joinGroup(gid,btn){
  if(!CU){location.href="login.html";return;}
  var hdrs={"Content-Type":"application/json"};
  var tk=localStorage.getItem("token");
  if(tk)hdrs["Authorization"]="Bearer "+tk;
  fetch("/api/groups.php?action=join",{method:"POST",headers:hdrs,credentials:"include",body:JSON.stringify({group_id:gid})})
  .then(function(r){return r.json();})
  .then(function(d){
    if(d.success){
      if(d.data.joined){btn.className="g-join joined";btn.textContent="Đã tham gia";toast("Đã tham gia nhóm!");}
      else{btn.className="g-join join";btn.textContent="Tham gia";toast("Đã rời nhóm");}
    }else{if((d.message||'').indexOf('Shipper Plus')>-1){if(confirm(d.message+'\n\nNâng cấp ngay?'))location.href='/wallet.html';}else toast(d.message||"Lỗi");}
  }).catch(function(){toast("Lỗi kết nối");});
}

// Load categories
function loadCategories(){
  fetch("/api/groups.php?action=categories").then(function(r){return r.json();}).then(function(d){
    if(!d.success)return;
    categories=d.data;
    var html="";
    d.data.forEach(function(c){
      html+="<div class='cat-chip' onclick='showCategory(\""+esc(c.slug)+"\")'>"+c.icon+" "+esc(c.name)+"</div>";
    });
    document.getElementById("catChips").innerHTML=html;
  });
}

// Load discover
function loadDiscover(){
  fetch("/api/groups.php?action=discover",{credentials:"include"}).then(function(r){return r.json();}).then(function(d){
    if(!d.success)return;
    var data=d.data;
    var html="";

    // Recommended (if logged in)
    if(data.recommended&&data.recommended.length){
      html+="<div class='section'><div class='section-head'><h2>Được đề xuất cho bạn</h2></div>";
      html+="<div class='g-list'>"+data.recommended.map(function(g){return mkGroupCard(g);}).join("")+"</div></div>";
    }

    // Popular
    if(data.popular&&data.popular.length){
      html+="<div class='section'><div class='section-head'><h2>Phổ biến nhất</h2></div>";
      html+="<div class='g-list'>"+data.popular.map(function(g){return mkGroupCard(g);}).join("")+"</div></div>";
    }

    // By category
    if(data.by_category){
      data.by_category.forEach(function(sec){
        html+="<div class='section'><div class='section-head'><h2>"+sec.category.icon+" "+esc(sec.category.name)+"</h2><a href='#' onclick='showCategory(\""+esc(sec.category.slug)+"\");return false'>Xem tất cả ›</a></div>";
        html+="<div class='g-scroll'>"+sec.groups.map(function(g){return mkScrollCard(g);}).join("")+"</div></div>";
      });
    }

    document.getElementById("discoverContent").innerHTML=html;
  });
}

// Show category page
function showCategory(slug){
  document.getElementById("mainContent").style.display="none";
  document.getElementById("catPage").style.display="block";
  document.getElementById("catPage").innerHTML="<div style='padding:40px;text-align:center'><i class='fas fa-spinner fa-spin' style='font-size:24px'></i></div>";

  fetch("/api/groups.php?action=category&slug="+encodeURIComponent(slug)).then(function(r){return r.json();}).then(function(d){
    if(!d.success){document.getElementById("catPage").innerHTML="<p style='padding:40px;text-align:center'>Không tìm thấy</p>";return;}
    var cat=d.data.category;
    var subs=d.data.subcategories||[];
    var groups=d.data.groups||[];

    var html="<div class='cat-page-head'><button onclick='backToDiscover()' style='background:none;border:none;font-size:18px;padding:4px 8px;cursor:pointer'><i class='fas fa-arrow-left'></i></button><h1>"+cat.icon+" "+esc(cat.name)+"</h1></div>";

    if(subs.length){
      html+="<div class='sub-chips'>";
      html+="<div class='sub-chip active' onclick='filterByAll(this)'>Tất cả</div>";
      subs.forEach(function(s){html+="<div class='sub-chip' onclick='filterBySub(\""+esc(s.slug)+"\",this,"+cat.id+")'>"+esc(s.name)+"</div>";});
      html+="</div>";
    }

    // Group by subcategory name
    _catGroups=groups;
    if(groups.length){
      html+="<div class='section'><div class='g-list'>"+groups.map(function(g){return mkGroupCard(g);}).join("")+"</div></div>";
    }else{
      html+="<p style='padding:40px;text-align:center;color:var(--muted)'>Chưa có nhóm nào</p>";
    }

    document.getElementById("catPage").innerHTML=html;
  });
}

var _catGroups=[];
function filterByAll(chip){
  var chips=chip.parentNode.querySelectorAll(".sub-chip");
  chips.forEach(function(c){c.classList.remove("active");});
  chip.classList.add("active");
  var listEl=document.querySelector("#catPage .g-list");
  if(listEl)listEl.innerHTML=_catGroups.map(function(g){return mkGroupCard(g);}).join("");
}
function filterBySub(subSlug,chip,parentCatId){
  // Toggle active
  var chips=chip.parentNode.querySelectorAll(".sub-chip");
  var wasActive=chip.classList.contains("active");
  chips.forEach(function(c){c.classList.remove("active");});
  if(!wasActive)chip.classList.add("active");
  
  // Filter groups
  var listEl=document.querySelector("#catPage .g-list");
  if(!listEl)return;
  
  if(wasActive){
    // Deselect = show all
    listEl.innerHTML=_catGroups.map(function(g){return mkGroupCard(g);}).join("");
    return;
  }
  
  // Find subcategory id
  var subCat=null;
  for(var i=0;i<categories.length;i++){
    if(categories[i].slug===subSlug){subCat=categories[i];break;}
  }
  
  // Fetch sub category to get its id, then filter
  fetch("/api/groups.php?action=category&slug="+encodeURIComponent(subSlug)).then(function(r){return r.json();}).then(function(d){
    if(d.success&&d.data.groups){
      listEl.innerHTML=d.data.groups.length?d.data.groups.map(function(g){return mkGroupCard(g);}).join(""):"<p style='padding:30px;text-align:center;color:var(--muted)'>Không có nhóm nào</p>";
    }
  });
}

function backToDiscover(){
  document.getElementById("catPage").style.display="none";
  document.getElementById("mainContent").style.display="block";
}

// Init
loadCategories();
loadDiscover();

function createGroup(){
  if(!CU){location.href="login.html";return;}
  location.href="create-group.html";
}

/* Desktop sidebar population */
(function(){
  if(window.innerWidth<769)return;
  
  // Category list for left sidebar
  fetch("/api/groups.php?action=categories").then(function(r){return r.json();}).then(function(d){
    var el=document.getElementById("dskCatList");if(!el||!d.success)return;
    var h="";
    d.data.forEach(function(c){
      h+="<a href='javascript:void(0)' class='sb-link' onclick='showCategory(\x22"+esc(c.slug)+"\x22)'>"+ c.icon+" "+esc(c.name)+"</a>";
    });
    el.innerHTML=h;
  }).catch(function(){});
  
  // My groups for right sidebar
  var tk=localStorage.getItem("token");
  var headers={};if(tk)headers["Authorization"]="Bearer "+tk;
  fetch("/api/groups.php?action=discover",{headers:headers,credentials:"include"}).then(function(r){return r.json();}).then(function(d){
    var el=document.getElementById("dskMyList");if(!el||!d.success)return;
    var joined=(d.data||[]).filter(function(g){return g.is_member;});
    if(!joined.length){el.innerHTML="<div style='padding:12px 14px;font-size:13px;color:#999'>Chưa tham gia nhóm nào</div>";return;}
    var colors=["#7C3AED","#1877F2","#E74C3C","#F59E0B","#00b14f","#0EA5E9","#c41230","#ff6600"];
    var h="";
    joined.slice(0,8).forEach(function(g,i){
      var icon=g.icon_image?"<img src='"+g.icon_image+"' style='width:100%;height:100%;object-fit:cover;border-radius:10px'>":"<span>"+(g.name||"G")[0]+"</span>";
      h+="<div class='sb-item' onclick='goGroup(\x22"+esc(g.slug)+"\x22)'><div class='sb-icon' style='background:"+colors[i%colors.length]+"'>"+icon+"</div><div style='flex:1;min-width:0'><div class='sb-name'>"+esc(g.name)+"</div><div class='sb-meta'>"+fN(g.member_count)+" thành viên</div></div></div>";
    });
    el.innerHTML=h;
  }).catch(function(){});
  
  // Online users
  fetch("/api/friends.php?action=online&limit=15",{headers:headers,credentials:"include"}).then(function(r){return r.json();}).then(function(d){
    var el=document.getElementById("dskOlList");if(!el||!d.success)return;
    if(!d.data||!d.data.length){el.innerHTML="<div style='padding:8px;color:#999;font-size:12px;width:100%;text-align:center'>Không ai online</div>";return;}
    var h="";
    d.data.forEach(function(u){
      if(u.avatar){h+="<img src='"+u.avatar+"' title='"+(u.fullname||"")+"' style='width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid #22c55e;cursor:pointer' onclick='location.href=\x22messages.html?user=\x22+u.id' loading=\"lazy\">";}
      else{h+="<div style='width:32px;height:32px;border-radius:50%;background:#e4e6eb;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;border:2px solid #22c55e;cursor:pointer' title='"+(u.fullname||"")+"' onclick='location.href=\x22messages.html?user=\x22+u.id'>"+(u.fullname||"U")[0]+"</div>";}
    });
    el.innerHTML=h;
  }).catch(function(){});
})();

