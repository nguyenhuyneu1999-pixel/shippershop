// ShipperShop Traffic Page

var CU=JSON.parse(localStorage.getItem("user")||"null");
var curCat="all",myLat=0,myLng=0,myAddr="";
var rCat="traffic",rSev="medium",rDur=60;
var catIcons={traffic:"fa-car-crash",weather:"fa-cloud-showers-heavy",terrain:"fa-mountain",warning:"fa-triangle-exclamation",other:"fa-circle-info"};
var catNames={traffic:"Giao thông",weather:"Thời tiết",terrain:"Địa hình",warning:"Cảnh báo",other:"Khác"};
var sevColors={low:"#4caf50",medium:"#ff9800",high:"#f44336",critical:"#7C3AED"};

// Get location
if(navigator.geolocation){
  navigator.geolocation.getCurrentPosition(function(p){
    myLat=p.coords.latitude;myLng=p.coords.longitude;
    reverseGeo(myLat,myLng);
  },function(){},{ enableHighAccuracy:true, timeout:10000 });
}

async function reverseGeo(lat,lng){
  try{
    var r=await fetch("https://nominatim.openstreetmap.org/reverse?format=json&lat="+lat+"&lon="+lng+"&zoom=16&accept-language=vi");
    var d=await r.json();
    if(d.display_name){myAddr=d.display_name.split(",").slice(0,3).join(",").trim();}
    document.getElementById("qkLoc").innerHTML='<i class="fas fa-check-circle"></i> '+esc(myAddr||"Đã lấy vị trí");
  }catch(e){}
}

// Load feed
loadAlerts();
async function loadAlerts(){
  var url="/api/traffic.php?";
  if(curCat&&curCat!=="all") url+="category="+curCat+"&";
  if(myLat&&myLng) url+="lat="+myLat+"&lng="+myLng+"&";
  try{
    var r=await fetch(url);var d=await r.json();
    if(d.success&&d.data.length){
      document.getElementById("tfFeed").innerHTML=d.data.map(renderCard).join("");
    }else{
      document.getElementById("tfFeed").innerHTML='<div class="tf-empty"><i class="fas fa-shield-alt"></i><p>Chưa có cảnh báo nào</p><p style="font-size:12px;margin-top:4px">Hãy là người đầu tiên báo cáo!</p></div>';
    }
  }catch(e){document.getElementById("tfFeed").innerHTML='<div class="tf-empty"><p>Lỗi tải dữ liệu</p></div>';}
}

function renderCard(a){
  var av=a.user_avatar?'<img class="tf-av" src="'+a.user_avatar+'" loading=\"lazy\">':'<div class="tf-av-ph">'+esc((a.user_name||"U")[0])+'</div>';
  var imgs="";
  try{var im=JSON.parse(a.images||"[]");if(im.length){imgs='<div class="tf-card-imgs">';im.forEach(function(s){imgs+='<img src="'+s+'" style="max-height:200px;width:100%;object-fit:cover;cursor:pointer" onclick="openTfLb(this.src)">';});imgs+='</div>';}}catch(e){}

  var rel=Math.round(a.reliability||100);
  var relColor=rel>=70?"#2e7d32":rel>=40?"#e65100":"#7C3AED";

  return '<div class="tf-card" id="ta'+a.id+'">'
    +'<div class="tf-card-head">'+av+'<div class="tf-card-info"><div class="tf-card-name">'+esc(a.user_name||"Ẩn danh")+'</div>'
    +'<div class="tf-card-meta">'+ago(a.created_at)+(a.trust_score>0?' · <span class="trust">⭐'+a.trust_score+'</span>':'')+'</div></div>'
    +'<div class="sev-dot sev-'+a.severity+'"></div>'
    +'<span class="tf-badge '+a.category+'"><i class="fas '+catIcons[a.category]+'"></i> '+(catNames[a.category]||a.category)+'</span></div>'
    +(a.content?'<div class="tf-card-content">'+esc(a.content)+'</div>':'')
    +(a.address?'<div class="tf-card-loc"><i class="fas fa-map-marker-alt"></i> '+esc(a.address)+'</div>':'')
    +imgs
    +'<div class="tf-card-expire"><i class="fas fa-clock"></i> Còn '+esc(a.time_left||"?")+ ' · <span style="color:'+relColor+';font-weight:700">'+rel+'% tin cậy</span> · '+a.confirms+' xác nhận</div>'
    +'<div class="tf-card-actions">'
    +'<button class="tf-act" onclick="vote('+a.id+',\'confirm\',this)"><i class="fas fa-check-circle"></i> Xác nhận ('+a.confirms+')</button>'
    +'<button class="tf-act" onclick="vote('+a.id+',\'deny\',this)"><i class="fas fa-times-circle"></i> Sai ('+a.denies+')</button>'
    +'<button class="tf-act" onclick="openTfCmt('+a.id+')"><i class="fas fa-comment"></i> Ghi chú</button>'
    +'<button class="tf-act" onclick="shareAlert('+a.id+')"><i class="fas fa-share"></i> Chia sẻ</button>'
    +'</div></div>';
}

function filterCat(cat,el){
  curCat=cat;
  document.querySelectorAll(".tf-cat").forEach(function(c){c.classList.remove("active");});
  el.classList.add("active");
  loadAlerts();
}

// VOTE
async function vote(id,type,btn){
  if(!CU){alert("Đăng nhập!");return;}
  try{
    var r=await fetch("/api/traffic.php?action=vote",{method:"POST",headers:{"Content-Type":"application/json","Authorization":"Bearer "+(localStorage.getItem("token")||"")},credentials:"include",body:JSON.stringify({alert_id:id,vote:type})});
    var d=await r.json();
    if(d.success) loadAlerts();
  }catch(e){}
}

// SHARE
function shareAlert(id){
  var url=location.origin+"/traffic.html?highlight="+id;
  if(navigator.share) navigator.share({url:url,title:"Cảnh báo giao thông"});
  else{navigator.clipboard.writeText(url);alert("Đã copy link!");}
}


// REPORT MODAL
var rMediaFiles=[];
var rProvData2=[],rDistData2=[],rWardData2=[];
var lastProvince="",lastDistrict="";

(async function(){
  try{
    var r=await fetch("https://provinces.open-api.vn/api/?depth=1");
    rProvData2=await r.json();
    var sel=document.getElementById("rProvince");
    if(sel){rProvData2.forEach(function(p){var o=document.createElement("option");o.value=p.name;o.textContent=p.name;o.dataset.code=p.code;sel.appendChild(o);});}
  }catch(e){}
  loadLastLocation();
})();

async function loadLastLocation(){
  if(!CU) return;
  try{
    var r=await fetch("/api/posts.php?user_id="+CU.id+"&limit=1",{credentials:"include"});
    var d=await r.json();
    if(d.success&&d.data.posts&&d.data.posts.length){
      var lp=d.data.posts[0];
      if(lp.province){lastProvince=lp.province;}
      if(lp.district){lastDistrict=lp.district;}
    }
  }catch(e){}
}

function openReport(){
  if(!CU){alert("Vui l\u00f2ng \u0111\u0103ng nh\u1eadp!");location.href="login.html";return;}
  // Camera first - modal opens after capture in addRMedia callback
  document.getElementById("rPhotoInput").click();
}
function showReportModal(){
  document.getElementById("reportOverlay").classList.add("open");
  if(lastProvince){
    setTimeout(function(){
      var ps=document.getElementById("rProvince");
      if(ps&&!ps.value){ps.value=lastProvince;onRProvChange();}
    },300);
  }
}
function closeReport(){document.getElementById("reportOverlay").classList.remove("open");rMediaFiles=[];document.getElementById("rMediaPrev").innerHTML="";}

function addRMedia(input,type){
  if(!input.files||!input.files.length){
    // No file selected - still show modal if not open
    if(!document.getElementById("reportOverlay").classList.contains("open")) showReportModal();
    return;
  }
  Array.from(input.files).forEach(function(f){
    if(rMediaFiles.length>=5) return;
    var idx=rMediaFiles.length;
    rMediaFiles.push({file:f,type:type});
    var reader=new FileReader();
    reader.onload=function(e){
      var prev=document.getElementById("rMediaPrev");
      var div=document.createElement("div");div.className="r-media-item";div.id="rm"+idx;
      if(type==="image") div.innerHTML='<img src="'+e.target.result+'" loading=\"lazy\"><button class="r-media-rm" onclick="rmRMedia('+idx+')"><i class="fas fa-times"></i></button>';
      else div.innerHTML='<video src="'+e.target.result+'"></video><button class="r-media-rm" onclick="rmRMedia('+idx+')"><i class="fas fa-times"></i></button>';
      prev.appendChild(div);
    };
    reader.readAsDataURL(f);
  });
  input.value="";
  // Show modal after capture
  if(!document.getElementById("reportOverlay").classList.contains("open")) showReportModal();
}
function rmRMedia(idx){rMediaFiles[idx]=null;var el=document.getElementById("rm"+idx);if(el)el.remove();}

function onRProvChange(){
  var name=document.getElementById("rProvince").value;
  var dSel=document.getElementById("rDistrict");
  var wSel=document.getElementById("rWard");
  dSel.innerHTML='<option value="">Qu\u1eadn/Huy\u1ec7n</option>';
  wSel.innerHTML='<option value="">X\u00e3/Ph\u01b0\u1eddng</option>';
  if(!name) return;
  var code=null;
  for(var i=0;i<rProvData2.length;i++){if(rProvData2[i].name===name){code=rProvData2[i].code;break;}}
  if(!code) return;
  fetch("https://provinces.open-api.vn/api/p/"+code+"?depth=2").then(function(r){return r.json();}).then(function(d){
    rDistData2=d.districts||[];
    rDistData2.forEach(function(dt){var o=document.createElement("option");o.value=dt.name;o.textContent=dt.name;o.dataset.code=dt.code;dSel.appendChild(o);});
    if(lastDistrict){dSel.value=lastDistrict;onRDistChange();lastDistrict="";}
  });
}
function onRDistChange(){
  var name=document.getElementById("rDistrict").value;
  var wSel=document.getElementById("rWard");
  wSel.innerHTML='<option value="">X\u00e3/Ph\u01b0\u1eddng</option>';
  if(!name) return;
  var dist=rDistData2.find(function(d){return d.name===name;});
  if(dist){fetch("https://provinces.open-api.vn/api/d/"+dist.code+"?depth=2").then(function(r){return r.json();}).then(function(d){
    (d.wards||[]).forEach(function(w){var o=document.createElement("option");o.value=w.name;o.textContent=w.name;wSel.appendChild(o);});
  });}
}

function selCat(el,cat){rCat=cat;document.querySelectorAll("#rCatChips .tf-chip").forEach(function(c){c.classList.remove("sel");});el.classList.add("sel");}
function selSev(el,sev){rSev=sev;document.querySelectorAll("#rSevChips .tf-chip").forEach(function(c){c.classList.remove("sel");});el.classList.add("sel");}
function selDur(el,dur){rDur=dur;document.querySelectorAll(".tf-dur button").forEach(function(b){b.classList.remove("sel");});el.classList.add("sel");}

async function submitReport(){
  var content=document.getElementById("rContent").value.trim();
  var prov=document.getElementById("rProvince").value;
  var dist=document.getElementById("rDistrict").value;
  var ward=document.getElementById("rWard").value;
  var street=document.getElementById("rStreet").value.trim();
  var parts=[];
  if(street) parts.push(street);
  if(ward) parts.push(ward);
  if(dist) parts.push(dist);
  if(prov) parts.push(prov);
  var address=parts.join(", ");

  var btn=document.getElementById("rSubmit");
  btn.disabled=true;btn.textContent="\u0110ang g\u1eedi...";

  var imgUrls=[];
  var active=rMediaFiles.filter(function(m){return m!==null;});
  for(var i=0;i<active.length;i++){
    if(active[i].type==="image"){
      try{
        var fd=new FormData();fd.append("image",active[i].file);
        var ur=await fetch("/api/traffic.php?action=upload",{method:"POST",headers:{"Authorization":"Bearer "+(localStorage.getItem("token")||"")},credentials:"include",body:fd});
        var ud=await ur.json();
        if(ud.success&&ud.data.url) imgUrls.push(ud.data.url);
      }catch(ue){}
    }
  }

  try{
    var r=await fetch("/api/traffic.php",{method:"POST",headers:{"Content-Type":"application/json","Authorization":"Bearer "+(localStorage.getItem("token")||"")},credentials:"include",
      body:JSON.stringify({category:rCat,content:content,images:imgUrls,address:address,province:prov||null,district:dist||null,latitude:myLat||null,longitude:myLng||null,severity:rSev,duration:rDur,is_quick:0})
    });
    var d=await r.json();
    if(d.success){alert("\u2705 \u0110\u00e3 b\u00e1o c\u00e1o!");closeReport();document.getElementById("rContent").value="";document.getElementById("rStreet").value="";loadAlerts();}
    else alert("L\u1ed7i: "+(d.message||""));
  }catch(e){alert("L\u1ed7i k\u1ebft n\u1ed1i");}
  btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane" style="margin-right:6px"></i>\u0110\u0103ng b\u00e1o c\u00e1o';
}

// QUICK ALERT
function openQuick(){
  document.getElementById("qkOverlay").classList.add("open");
  document.getElementById("qkStatus").textContent="";
  if(myAddr) document.getElementById("qkLoc").innerHTML='<i class="fas fa-check-circle"></i> '+esc(myAddr);
  else{
    document.getElementById("qkLoc").textContent="Đang lấy vị trí GPS...";
    if(navigator.geolocation) navigator.geolocation.getCurrentPosition(function(p){
      myLat=p.coords.latitude;myLng=p.coords.longitude;
      reverseGeo(myLat,myLng);
    });
  }
}
function closeQuick(){document.getElementById("qkOverlay").classList.remove("open");}

async function sendQuick(cat){
  if(!CU){alert("Đăng nhập!");return;}
  var items=document.querySelectorAll(".qk-item");
  items.forEach(function(i){i.style.pointerEvents="none";i.style.opacity=".5";});
  document.getElementById("qkStatus").innerHTML='<i class="fas fa-spinner spin"></i> Đang gửi...';
  try{
    var r=await fetch("/api/traffic.php",{method:"POST",headers:{"Content-Type":"application/json","Authorization":"Bearer "+(localStorage.getItem("token")||"")},credentials:"include",
      body:JSON.stringify({category:cat,latitude:myLat||null,longitude:myLng||null,address:myAddr,severity:"high",duration:60,is_quick:1})
    });
    var d=await r.json();
    if(d.success){
      document.getElementById("qkStatus").innerHTML='<i class="fas fa-check-circle"></i> ĐÃ BÁO THÀNH CÔNG!';
      setTimeout(function(){closeQuick();loadAlerts();},1500);
    }else{
      document.getElementById("qkStatus").textContent="Lỗi: "+(d.message||"");
    }
  }catch(e){document.getElementById("qkStatus").textContent="Lỗi kết nối";}
  items.forEach(function(i){i.style.pointerEvents="";i.style.opacity="";});
}

// UTILS
function esc(t){return t?String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"):"";}
function ago(dt){if(!dt)return"";var s=Math.floor((Date.now()-new Date(dt.replace(" ","T")).getTime())/1000);if(s<60)return"vừa xong";if(s<3600)return Math.floor(s/60)+" phút";if(s<86400)return Math.floor(s/3600)+" giờ";return Math.floor(s/86400)+" ngày";}


// LIGHTBOX with pinch-zoom + double-tap
var tfScale=1,tfLastTap=0,tfStartDist=0,tfStartScale=1;
function openTfLb(src){
  tfScale=1;
  var lb=document.getElementById("tfLb");
  var img=document.getElementById("tfLbImg");
  img.src=src;img.style.transform="scale(1)";
  lb.style.display="flex";
  lb.onclick=function(e){if(e.target===lb)closeTfLb();};
}
function closeTfLb(){document.getElementById("tfLb").style.display="none";tfScale=1;}

// Double-tap zoom
document.addEventListener("DOMContentLoaded",function(){
  var img=document.getElementById("tfLbImg");
  if(!img)return;
  img.addEventListener("click",function(e){
    var now=Date.now();
    if(now-tfLastTap<300){
      tfScale=tfScale>1?1:2.5;
      img.style.transition="transform .25s ease";
      img.style.transform="scale("+tfScale+")";
      setTimeout(function(){img.style.transition="";},300);
    }
    tfLastTap=now;
  });

  // Pinch zoom
  img.addEventListener("touchstart",function(e){
    if(e.touches.length===2){
      e.preventDefault();
      tfStartDist=Math.hypot(e.touches[0].clientX-e.touches[1].clientX,e.touches[0].clientY-e.touches[1].clientY);
      tfStartScale=tfScale;
    }
  },{passive:false});
  img.addEventListener("touchmove",function(e){
    if(e.touches.length===2){
      e.preventDefault();
      var dist=Math.hypot(e.touches[0].clientX-e.touches[1].clientX,e.touches[0].clientY-e.touches[1].clientY);
      tfScale=Math.min(5,Math.max(0.5,tfStartScale*(dist/tfStartDist)));
      img.style.transform="scale("+tfScale+")";
    }
  },{passive:false});
  img.addEventListener("touchend",function(e){
    if(tfScale<1){tfScale=1;img.style.transition="transform .2s";img.style.transform="scale(1)";setTimeout(function(){img.style.transition="";},200);}
  });
});

// COMMENTS (using posts comment API - store alert_id as post reference)
var tfCmtAlertId=0;
function openTfCmt(id){
  tfCmtAlertId=id;
  document.getElementById("cmtOverlay").classList.add("open");
  loadTfCmts(id);
}
function closeTfCmt(){document.getElementById("cmtOverlay").classList.remove("open");}

async function loadTfCmts(id){
  var list=document.getElementById("tfCmtList");
  list.innerHTML='<div class="tf-empty" style="padding:20px"><i class="fas fa-spinner spin"></i></div>';
  try{
    var r=await fetch("/api/traffic.php?action=comments&alert_id="+id);
    var d=await r.json();
    if(d.success&&d.data.length){
      list.innerHTML=d.data.map(function(c){
        var av=c.user_avatar?'<img src="'+c.user_avatar+'" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0" loading=\"lazy\">':'<div style="width:28px;height:28px;border-radius:50%;background:#e4e6eb;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">'+esc((c.user_name||"U")[0])+'</div>';
        return '<div style="display:flex;gap:8px;margin-bottom:10px">'+av+'<div style="flex:1;min-width:0"><div style="background:#f0f2f5;border-radius:14px;padding:6px 10px;display:inline-block;max-width:100%"><div style="font-weight:700;font-size:12px">'+esc(c.user_name)+'</div><div style="font-size:13px;margin-top:1px">'+esc(c.content)+'</div></div><div style="font-size:11px;color:#999;margin-top:2px;padding-left:10px">'+ago(c.created_at)+'</div></div></div>';
      }).join("");
    }else{
      list.innerHTML='<div class="tf-empty" style="padding:16px;font-size:13px">Chưa có ghi chú</div>';
    }
  }catch(e){list.innerHTML='<div class="tf-empty">Lỗi</div>';}
}

async function sendTfCmt(){
  if(!CU){alert("Đăng nhập!");return;}
  var inp=document.getElementById("tfCmtInput");
  var ct=inp.value.trim();
  if(!ct)return;
  inp.value="";
  try{
    var r=await fetch("/api/traffic.php?action=comment",{method:"POST",headers:{"Content-Type":"application/json","Authorization":"Bearer "+(localStorage.getItem("token")||"")},credentials:"include",body:JSON.stringify({alert_id:tfCmtAlertId,content:ct})});
    var d=await r.json();
    if(d.success) loadTfCmts(tfCmtAlertId);
    else alert(d.message||"Lỗi");
  }catch(e){alert("Lỗi kết nối");}
}

// Auto refresh every 2 min
setInterval(loadAlerts, 120000);
