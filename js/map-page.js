// ShipperShop map.html — extracted for browser caching

// --- block 21538B ---

var token=localStorage.getItem("token")||"";
var user=JSON.parse(localStorage.getItem("user")||"null");
var map,markers=[],tempMarker=null,currentFilter="";
var pinLat=0,pinLng=0;

// Pin type config
var pinConfig={
    delivery:{color:"#2196f3",icon:"📦",label:"Giao hàng"},
    warning:{color:"#ff9800",icon:"⚠️",label:"Cảnh báo"},
    note:{color:"#4caf50",icon:"📝",label:"Ghi chú"},
    favorite:{color:"#ffc107",icon:"⭐",label:"Yêu thích"}
};

// INIT MAP
function initMap(){
    map=L.map("map",{zoomControl:false}).setView([21.0285,105.8542],13);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"© OpenStreetMap",maxZoom:19}).addTo(map);
    L.control.zoom({position:"bottomright"}).addTo(map);
    
    // Click to add pin - always allow, show modal immediately
    map.on("click",function(e){
        if(!user||!token){var r=confirm("Đăng nhập để thêm địa điểm?");if(r)location.href="login.html";return;}
        pinLat=e.latlng.lat;pinLng=e.latlng.lng;
        if(tempMarker)map.removeLayer(tempMarker);
        tempMarker=L.marker([pinLat,pinLng],{draggable:true}).addTo(map);
        tempMarker.on("dragend",function(ev){pinLat=ev.target.getLatLng().lat;pinLng=ev.target.getLatLng().lng;document.getElementById("pinLatLng").textContent=pinLat.toFixed(5)+", "+pinLng.toFixed(5);reverseGeocode(pinLat,pinLng);});
        document.getElementById("pinLatLng").textContent=pinLat.toFixed(5)+", "+pinLng.toFixed(5);
        document.getElementById("pinTitle").value="";
        document.getElementById("pinDesc").value="";
        document.getElementById("pinAddress").value="";
        curRating=0;renderStars();
        var diffSel=document.getElementById("pinDiff");if(diffSel)diffSel.value="";
        reverseGeocode(pinLat,pinLng);
        document.getElementById("pinModal").classList.add("active");
    });

    loadPins();
    locateMe();
}

// LOCATE ME
function locateMe(){
    if(!navigator.geolocation)return;
    navigator.geolocation.getCurrentPosition(function(pos){
        map.setView([pos.coords.latitude,pos.coords.longitude],15);
        L.circle([pos.coords.latitude,pos.coords.longitude],{radius:50,color:"#7C3AED",fillOpacity:.15,weight:2}).addTo(map);
        L.marker([pos.coords.latitude,pos.coords.longitude]).addTo(map).bindPopup("<strong>📍 Vị trí của bạn</strong>").openPopup();
    },function(err){
        // console.log("GPS error:",err.message);
    },{enableHighAccuracy:true,timeout:10000});
}

// LOAD PINS
async function loadPins(){
    var url="/api/map-pins.php";
    if(currentFilter)url+="?type="+currentFilter;
    try{
        var r=await fetch(url);var d=await r.json();
        if(d.success){
            // Clear old markers
            markers.forEach(function(m){map.removeLayer(m)});
            markers=[];
            d.data.forEach(function(pin){
                var cfg=pinConfig[pin.pin_type]||pinConfig.note;
                var icon=L.divIcon({html:'<div style="font-size:24px;text-shadow:0 2px 4px rgba(0,0,0,.3)">'+cfg.icon+'</div>',className:"",iconSize:[30,30],iconAnchor:[15,15]});
                var m=L.marker([pin.lat,pin.lng],{icon:icon}).addTo(map);
                var badge='<span class="pin-type-badge" style="background:'+cfg.color+'20;color:'+cfg.color+'">'+cfg.icon+" "+cfg.label+'</span>';
                var popup='<div class="pin-popup">'+badge+'<strong>'+esc(pin.title)+'</strong>';
                if(pin.difficulty){var diffMap={easy:['Dễ giao','diff-easy'],medium:['Trung bình','diff-medium'],hard:['Khó giao','diff-hard']};var df=diffMap[pin.difficulty]||['',''];popup+='<span class="'+df[1]+'">'+df[0]+'</span> ';}
                if(parseInt(pin.rating)>0){popup+='<div class="pin-stars">';for(var s=1;s<=5;s++){popup+=s<=pin.rating?'★':'<span class="empty">★</span>';}popup+=' <small>'+pin.rating+'/5</small></div>';}
                if(pin.description)popup+='<div style="margin-top:4px">'+esc(pin.description)+'</div>';
                if(pin.address)popup+='<div style="margin-top:4px;font-size:12px;color:#666"><i class="fas fa-map-marker-alt"></i> '+esc(pin.address)+'</div>';
                popup+='<div class="pin-user">👤 '+esc(pin.user_name||"User")+' · '+timeAgo(pin.created_at)+'</div>';
                popup+='<div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap">';
                popup+='<a href="https://www.google.com/maps/dir/?api=1&destination='+pin.lat+','+pin.lng+'" target="_blank" style="padding:4px 10px;background:#1976d2;color:#fff;border:none;border-radius:6px;font-size:11px;text-decoration:none;cursor:pointer"><i class="fas fa-directions"></i> Chỉ đường</a>';
                popup+='<button onclick="votePin('+pin.id+',1)" style="padding:4px 8px;background:#e8f5e9;color:#4caf50;border:1px solid #4caf50;border-radius:6px;font-size:11px;cursor:pointer">👍 '+(parseInt(pin.upvotes)||0)+'</button>';
                popup+='<button onclick="votePin('+pin.id+',-1)" style="padding:4px 8px;background:#fce4ec;color:#f44336;border:1px solid #f44336;border-radius:6px;font-size:11px;cursor:pointer">👎 '+(parseInt(pin.downvotes)||0)+'</button>';
                if(user&&parseInt(pin.user_id)===user.id)popup+='<button onclick="deletePin('+pin.id+')" style="padding:4px 10px;background:#f44;color:#fff;border:none;border-radius:6px;font-size:11px;cursor:pointer">Xóa</button>';
                popup+='</div>';
                popup+='</div>';
                m.bindPopup(popup);
                markers.push(m);
            });
        }
    }catch(e){console.error("Load pins error:",e);}
}

function filterPins(type,el){
    currentFilter=type;
    document.querySelectorAll(".map-filter").forEach(function(f){f.classList.remove("active")});
    el.classList.add("active");
    loadPins();
}

// ADD PIN - at map center
function startAddPin(){
    if(!user||!token){var r=confirm("Đăng nhập để thêm địa điểm?");if(r)location.href="login.html";return;}
    var center=map.getCenter();
    pinLat=center.lat;pinLng=center.lng;
    if(tempMarker)map.removeLayer(tempMarker);
    tempMarker=L.marker([pinLat,pinLng],{draggable:true}).addTo(map);
    tempMarker.on("dragend",function(ev){pinLat=ev.target.getLatLng().lat;pinLng=ev.target.getLatLng().lng;document.getElementById("pinLatLng").textContent=pinLat.toFixed(5)+", "+pinLng.toFixed(5);reverseGeocode(pinLat,pinLng);});
    document.getElementById("pinLatLng").textContent=pinLat.toFixed(5)+", "+pinLng.toFixed(5);
    document.getElementById("pinTitle").value="";
    document.getElementById("pinDesc").value="";
    document.getElementById("pinAddress").value="";
    curRating=0;renderStars();
    var diffSel2=document.getElementById("pinDiff");if(diffSel2)diffSel2.value="";
    reverseGeocode(pinLat,pinLng);
    document.getElementById("pinModal").classList.add("active");
}

function closePinModal(){
    document.getElementById("pinModal").classList.remove("active");
    if(tempMarker){map.removeLayer(tempMarker);tempMarker=null;}
    curRating=0;renderStars();
}

var curRating=0;
function setRating(n){curRating=n;renderStars();}
function renderStars(){var el=document.getElementById("pinRating");if(!el)return;var spans=el.querySelectorAll("span");for(var i=0;i<spans.length;i++){spans[i].textContent=i<curRating?"★":"☆";spans[i].style.color=i<curRating?"#ffc107":"#ccc";}}

async function savePin(){
    var title=document.getElementById("pinTitle").value.trim();
    if(!title){document.getElementById("pinTitle").focus();document.getElementById("pinTitle").style.borderColor="#f44";return;}
    document.getElementById("pinTitle").style.borderColor="#ddd";
    if(!pinLat||!pinLng){return;}
    var btn=document.getElementById("pinSubmit");
    btn.disabled=true;btn.innerHTML="<i class='fas fa-spinner fa-spin'></i> Đang lưu...";
    try{
        var r=await fetch("/api/map-pins.php",{method:"POST",headers:{"Content-Type":"application/json","Authorization":"Bearer "+token},body:JSON.stringify({lat:pinLat,lng:pinLng,title:title,description:document.getElementById("pinDesc").value.trim(),pin_type:document.getElementById("pinType").value,address:document.getElementById("pinAddress").value.trim(),rating:curRating,difficulty:document.getElementById("pinDiff").value||null})});
        var d=await r.json();
        if(d.success){
            closePinModal();
            document.getElementById("pinTitle").value="";document.getElementById("pinDesc").value="";document.getElementById("pinAddress").value="";
            loadPins();
        }else{document.getElementById("pinTitle").focus();}
    }catch(e){}
    btn.disabled=false;btn.innerHTML="<i class='fas fa-check' style='margin-right:6px'></i>Lưu địa điểm";
}

async function deletePin(id){
    if(!confirm("Xóa địa điểm này?"))return;
    try{
        await fetch("/api/map-pins.php?id="+id,{method:"DELETE",headers:{"Authorization":"Bearer "+token}});
        loadPins();
    }catch(e){}
}

// SEARCH
async function searchLocation(){
    var q=document.getElementById("mapSearch").value.trim();
    if(!q)return;
    try{
        var r=await fetch("https://nominatim.openstreetmap.org/search?format=json&q="+encodeURIComponent(q)+"&countrycodes=vn&limit=1");
        var d=await r.json();
        if(d.length>0){map.setView([d[0].lat,d[0].lon],16);
            // Show temporary marker at search result
            if(tempMarker)map.removeLayer(tempMarker);
            tempMarker=L.marker([d[0].lat,d[0].lon]).addTo(map).bindPopup("<strong>"+esc(q)+"</strong><br><small>"+esc(d[0].display_name||"")+"</small>").openPopup();
        }
        else{document.getElementById("mapSearch").style.borderColor="#f44";setTimeout(function(){document.getElementById("mapSearch").style.borderColor="#ddd";},2000);}
    }catch(e){console.error("Search error:",e);}
}

// REVERSE GEOCODE
async function reverseGeocode(lat,lng){
    try{
        var r=await fetch("https://nominatim.openstreetmap.org/reverse?format=json&lat="+lat+"&lon="+lng+"&zoom=18");
        var d=await r.json();
        if(d.display_name){document.getElementById("pinAddress").value=d.display_name.split(",").slice(0,4).join(",");}
    }catch(e){}
}

function esc(s){var d=document.createElement("div");d.textContent=s;return d.innerHTML;}
function timeAgo(d){if(!d)return"";var s=Math.floor((Date.now()-new Date(d).getTime())/1000);if(s<60)return"vừa xong";if(s<3600)return Math.floor(s/60)+" phút";if(s<86400)return Math.floor(s/3600)+" giờ";return Math.floor(s/86400)+" ngày";}

// INIT
document.addEventListener("DOMContentLoaded",initMap);

// ============================================
// FEATURE 1: TRAFFIC ALERTS ON MAP
// ============================================
var trafficMarkers=[],trafficOn=false,trafficTimer=null;
var severityColors={critical:"#f44336",high:"#ff5722",medium:"#ff9800",low:"#ffc107"};
var categoryIcons={traffic:"🚗",weather:"🌧️",terrain:"🚧",warning:"⚠️",other:"📢"};

function toggleTraffic(el){
    trafficOn=!trafficOn;
    el.classList.toggle("active");
    if(trafficOn){
        el.style.background="#f44336";el.style.color="#fff";
        loadTrafficAlerts();
        trafficTimer=setInterval(loadTrafficAlerts,120000);
        document.getElementById("mapLegend").classList.add("show");
    }else{
        el.style.background="";el.style.color="#f44336";
        trafficMarkers.forEach(function(m){map.removeLayer(m)});trafficMarkers=[];
        if(trafficTimer)clearInterval(trafficTimer);
        document.getElementById("mapLegend").classList.remove("show");
    }
}

async function loadTrafficAlerts(){
    try{
        var r=await fetch("/api/traffic.php");var d=await r.json();
        trafficMarkers.forEach(function(m){map.removeLayer(m)});trafficMarkers=[];
        if(!d.success||!d.data)return;
        d.data.forEach(function(a){
            var lat=parseFloat(a.latitude);var lng=parseFloat(a.longitude);
            if(!lat||!lng)return;
            var sev=a.severity||"medium";
            var cat=a.category||"other";
            var icon=L.divIcon({html:'<div class="traffic-marker '+sev+'">'+((categoryIcons[cat])||"⚠️")+'</div>',className:"",iconSize:[32,32],iconAnchor:[16,16]});
            var m=L.marker([lat,lng],{icon:icon}).addTo(map);
            var sevText={critical:"🔴 Nghiêm trọng",high:"🟠 Cao",medium:"🟡 Trung bình",low:"🟢 Thấp"};
            var popup='<div class="pin-popup"><strong>'+((categoryIcons[cat])||"")+" "+esc(a.content||a.title||"Cảnh báo")+'</strong>';
            popup+='<div style="margin:4px 0">Mức độ: '+(sevText[sev]||sev)+'</div>';
            if(a.address)popup+='<div style="font-size:12px;color:#666"><i class="fas fa-map-marker-alt"></i> '+esc(a.address)+'</div>';
            popup+='<div style="font-size:11px;color:#999;margin-top:4px">'+timeAgo(a.created_at)+' · 👍'+parseInt(a.confirms||0)+' · 👎'+parseInt(a.denies||0)+'</div>';
            popup+='</div>';
            m.bindPopup(popup);
            trafficMarkers.push(m);
        });
    }catch(e){console.error("Traffic load error:",e);}
}

// ============================================
// FEATURE 2: DELIVERY POINT RATING (in popup)
// ============================================
// Rating is shown in pin popup via loadPins() update above
// Vote function for upvote/downvote pins
async function votePin(id,type){
    if(!user||!token)return;
    try{
        await fetch("/api/map-pins.php",{method:"POST",headers:{"Content-Type":"application/json","Authorization":"Bearer "+token},body:JSON.stringify({action:"vote",pin_id:id,vote:type})});
        loadPins();
    }catch(e){}
}

// ============================================
// FEATURE 3: REAL-TIME SHIPPER LOCATION
// ============================================
var shipperMarkers={},shipperOn=false,shareOn=false,watchId=null;
var fbConfig={apiKey:"AIzaSyDNwf6FKPX10szjFJ2Ei6YoKJWRA6NAkKs",databaseURL:"https://shippershop-5f8d9-default-rtdb.asia-southeast1.firebasedatabase.app",projectId:"shippershop-5f8d9"};
var fbApp=null,fbDb=null,fbRef=null;

function initFirebase(){
    if(fbApp)return;
    if(typeof firebase==="undefined"){
        var s=document.createElement("script");s.src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js";
        s.onload=function(){
            var s2=document.createElement("script");s2.src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js";
            s2.onload=function(){fbApp=firebase.initializeApp(fbConfig,"mapApp");fbDb=firebase.database(fbApp);};
            document.head.appendChild(s2);
        };
        document.head.appendChild(s);
    }else{
        if(!firebase.apps.length||!firebase.apps.find(function(a){return a.name==="mapApp";})){
            fbApp=firebase.initializeApp(fbConfig,"mapApp");
        }else{fbApp=firebase.apps.find(function(a){return a.name==="mapApp";});}
        fbDb=firebase.database(fbApp);
    }
}

function toggleShippers(el){
    shipperOn=!shipperOn;
    el.classList.toggle("active");
    if(shipperOn){
        el.style.background="#4caf50";el.style.color="#fff";
        initFirebase();
        setTimeout(listenShippers,1000);
    }else{
        el.style.background="";el.style.color="#4caf50";
        if(fbRef)fbRef.off();
        Object.keys(shipperMarkers).forEach(function(k){map.removeLayer(shipperMarkers[k]);});
        shipperMarkers={};
    }
}

function listenShippers(){
    if(!fbDb)return;
    fbRef=fbDb.ref("shipper_locations");
    fbRef.on("value",function(snap){
        var data=snap.val()||{};
        // Remove old markers for users no longer sharing
        Object.keys(shipperMarkers).forEach(function(k){
            if(!data[k]){map.removeLayer(shipperMarkers[k]);delete shipperMarkers[k];}
        });
        // Update/add markers
        Object.keys(data).forEach(function(uid){
            if(user&&uid===String(user.id))return; // Skip self
            var d=data[uid];
            if(!d.lat||!d.lng)return;
            var age=(Date.now()-d.ts)/60000;
            if(age>30)return; // Skip stale (>30min)
            var icon=L.divIcon({
                html:d.avatar?'<div class="shipper-marker"><img src="'+d.avatar+'" loading=\"lazy\"></div>':'<div class="shipper-marker-ph">🛵</div>',
                className:"",iconSize:[36,36],iconAnchor:[18,18]
            });
            if(shipperMarkers[uid]){
                shipperMarkers[uid].setLatLng([d.lat,d.lng]);
                shipperMarkers[uid].setIcon(icon);
            }else{
                shipperMarkers[uid]=L.marker([d.lat,d.lng],{icon:icon}).addTo(map);
            }
            var popup='<div class="pin-popup"><strong>🛵 '+esc(d.name||"Shipper")+'</strong>';
            if(d.ship)popup+='<div style="font-size:12px;font-weight:700;color:#666">'+esc(d.ship)+'</div>';
            popup+='<div style="font-size:11px;color:#999">Cập nhật '+Math.floor(age)+' phút trước</div>';
            popup+='</div>';
            shipperMarkers[uid].bindPopup(popup);
        });
    });
}

function toggleShareLocation(){
    shareOn=!shareOn;
    var el=document.getElementById("shareToggle");
    if(shareOn){
        if(!user||!token){shareOn=false;return;}
        el.classList.add("active");
        document.getElementById("shareLabel").textContent="Đang chia sẻ...";
        initFirebase();
        setTimeout(startSharing,1000);
    }else{
        el.classList.remove("active");
        document.getElementById("shareLabel").textContent="Chia sẻ vị trí";
        stopSharing();
    }
}

function startSharing(){
    if(!fbDb||!user)return;
    if(!navigator.geolocation)return;
    // Immediate update
    navigator.geolocation.getCurrentPosition(function(pos){
        updateMyLocation(pos.coords.latitude,pos.coords.longitude);
    });
    // Watch position
    watchId=navigator.geolocation.watchPosition(function(pos){
        updateMyLocation(pos.coords.latitude,pos.coords.longitude);
    },function(){},{enableHighAccuracy:true,maximumAge:10000,timeout:15000});
}

function updateMyLocation(lat,lng){
    if(!fbDb||!user)return;
    fbDb.ref("shipper_locations/"+user.id).set({
        lat:lat,lng:lng,name:user.fullname||"Shipper",
        avatar:user.avatar||"",ship:user.shipping_company||"",
        ts:Date.now()
    });
}

function stopSharing(){
    if(watchId!==null){navigator.geolocation.clearWatch(watchId);watchId=null;}
    if(fbDb&&user){fbDb.ref("shipper_locations/"+user.id).remove();}
}

// ============================================
// FEATURE 4: HEATMAP (KHU VỰC NÓNG)
// ============================================
var heatLayer=null,heatOn=false;

function toggleHeatmap(el){
    heatOn=!heatOn;
    el.classList.toggle("active");
    if(heatOn){
        el.style.background="#ff9800";el.style.color="#fff";
        loadHeatmapData();
    }else{
        el.style.background="";el.style.color="#ff9800";
        if(heatLayer){map.removeLayer(heatLayer);heatLayer=null;}
    }
}

async function loadHeatmapData(){
    try{
        // Use posts with location + map pins as heat sources
        var heatPoints=[];
        
        // 1. Posts with province/district → approximate coordinates
        var r=await fetch("/api/posts.php?limit=200");var d=await r.json();
        if(d.success&&d.data&&d.data.posts){
            d.data.posts.forEach(function(p){
                // Use pin locations that match province
                if(p.province){
                    var districtCoords=getDistrictCoords(p.province,p.district);
                    if(districtCoords){
                        var jitter=(Math.random()-0.5)*0.01;
                        heatPoints.push([districtCoords[0]+jitter,districtCoords[1]+jitter,0.5]);
                    }
                }
            });
        }
        
        // 2. Map pins with more weight
        var r2=await fetch("/api/map-pins.php");var d2=await r2.json();
        if(d2.success&&d2.data){
            d2.data.forEach(function(pin){
                if(pin.lat&&pin.lng)heatPoints.push([parseFloat(pin.lat),parseFloat(pin.lng),1.0]);
            });
        }
        
        // 3. Traffic alerts with high weight
        var r3=await fetch("/api/traffic.php");var d3=await r3.json();
        if(d3.success&&d3.data){
            d3.data.forEach(function(a){
                var lat=parseFloat(a.latitude);var lng=parseFloat(a.longitude);
                if(lat&&lng)heatPoints.push([lat,lng,0.8]);
            });
        }
        
        if(heatLayer){map.removeLayer(heatLayer);}
        if(heatPoints.length>0){
            heatLayer=L.heatLayer(heatPoints,{radius:35,blur:25,maxZoom:17,gradient:{0.2:"#ffffb2",0.4:"#fd8d3c",0.6:"#f03b20",0.8:"#bd0026",1:"#800026"}}).addTo(map);
        }
    }catch(e){console.error("Heatmap error:",e);}
}

// Approximate coordinates for Vietnamese provinces/districts
var districtCoordsMap={
    "TP. Hồ Chí Minh":[10.7769,106.7009],
    "Hồ Chí Minh":[10.7769,106.7009],
    "Hà Nội":[21.0285,105.8542],
    "Đà Nẵng":[16.0544,108.2022],
    "Cần Thơ":[10.0452,105.7469],
    "Hải Phòng":[20.8449,106.6881],
    "Bình Dương":[11.0753,106.6512],
    "Đồng Nai":[10.9531,106.8242],
    "Long An":[10.5364,106.4126],
    "Bắc Ninh":[21.1861,106.0763],
    "Quảng Ninh":[21.0064,107.2925],
    "Thanh Hóa":[19.8067,105.7852],
    "Nghệ An":[18.6789,105.6813],
    "Khánh Hòa":[12.2388,109.1967],
    "Lâm Đồng":[11.9465,108.4419],
};
function getDistrictCoords(province,district){
    for(var key in districtCoordsMap){
        if(province&&province.indexOf(key)!==-1)return districtCoordsMap[key];
    }
    return null;
}

// Cleanup on page leave
window.addEventListener("beforeunload",function(){if(shareOn)stopSharing();});

// --- block 4676B ---

// === ASK LOCATION ===
var askProvinces=[], askDistricts=[], askWards=[];

function openAskLocation(){
  var CU2=JSON.parse(localStorage.getItem("user")||"null");
  if(!CU2){alert("Vui l\u00f2ng \u0111\u0103ng nh\u1eadp!");location.href="login.html";return;}
  document.getElementById("askOverlay").classList.add("open");
  if(askProvinces.length===0) loadProvinces();
}

function closeAskLocation(){
  document.getElementById("askOverlay").classList.remove("open");
}

async function loadProvinces(){
  try{
    var r=await fetch("https://provinces.open-api.vn/api/?depth=1");
    askProvinces=await r.json();
    var sel=document.getElementById("askProvince");
    sel.innerHTML='<option value="">-- Ch\u1ECDn t\u1EC9nh --</option>';
    askProvinces.forEach(function(p){
      sel.innerHTML+='<option value="'+p.code+'">'+p.name+'</option>';
    });
  }catch(e){console.log("Province API error",e);}
}

async function loadDistricts(){
  var code=document.getElementById("askProvince").value;
  var sel=document.getElementById("askDistrict");
  var wSel=document.getElementById("askWard");
  sel.innerHTML='<option value="">-- Ch\u1ECDn qu\u1EADn/huy\u1EC7n --</option>';
  wSel.innerHTML='<option value="">-- Ch\u1ECDn x\u00e3/ph\u01b0\u1EDDng --</option>';
  if(!code)return;
  try{
    var r=await fetch("https://provinces.open-api.vn/api/p/"+code+"?depth=2");
    var d=await r.json();
    askDistricts=d.districts||[];
    askDistricts.forEach(function(dist){
      sel.innerHTML+='<option value="'+dist.code+'">'+dist.name+'</option>';
    });
  }catch(e){}
}

async function loadWards(){
  var code=document.getElementById("askDistrict").value;
  var sel=document.getElementById("askWard");
  sel.innerHTML='<option value="">-- Ch\u1ECDn x\u00e3/ph\u01b0\u1EDDng --</option>';
  if(!code)return;
  try{
    var r=await fetch("https://provinces.open-api.vn/api/d/"+code+"?depth=2");
    var d=await r.json();
    askWards=d.wards||[];
    askWards.forEach(function(w){
      sel.innerHTML+='<option value="'+w.code+'">'+w.name+'</option>';
    });
  }catch(e){}
}

async function submitAskLocation(){
  var place=document.getElementById("askPlace").value.trim();
  if(!place){alert("Nh\u1EADp t\u00ean \u0111\u1ECBa \u0111i\u1EC3m!");return;}
  
  var provSel=document.getElementById("askProvince");
  var distSel=document.getElementById("askDistrict");
  var wardSel=document.getElementById("askWard");
  var provName=provSel.options[provSel.selectedIndex]?provSel.options[provSel.selectedIndex].text:"";
  var distName=distSel.options[distSel.selectedIndex]?distSel.options[distSel.selectedIndex].text:"";
  var wardName=wardSel.options[wardSel.selectedIndex]?wardSel.options[wardSel.selectedIndex].text:"";
  var desc=document.getElementById("askDesc").value.trim();
  
  if(!provSel.value){alert("Ch\u1ECDn t\u1EC9nh/th\u00e0nh ph\u1ED1!");return;}
  
  // Build content
  var location_parts=[];
  if(wardName&&wardName.indexOf("--")===-1) location_parts.push(wardName);
  if(distName&&distName.indexOf("--")===-1) location_parts.push(distName);
  if(provName&&provName.indexOf("--")===-1) location_parts.push(provName);
  var locationStr=location_parts.join(", ");
  
  var content="\ud83d\udccd H\u1ECFi \u0111\u1ECBa ch\u1EC9: "+place+"\n\n";
  content+="\ud83d\udccd Khu v\u1ef1c: "+locationStr+"\n";
  if(desc) content+="\n"+desc;
  content+="\n\n#h\u1ECFi\u0111\u1ECBach\u1EC9 #"+provName.replace(/\s+/g,"").toLowerCase();
  
  var btn=document.getElementById("askSubmit");
  btn.disabled=true;btn.textContent="\u0110ang \u0111\u0103ng...";
  
  try{
    var tk=localStorage.getItem("token")||"";
    var fd=new FormData();
    fd.append("content",content);
    fd.append("type","question");
    if(provName&&provName.indexOf("--")===-1) fd.append("province",provName);
    if(distName&&distName.indexOf("--")===-1) fd.append("district",distName);
    if(wardName&&wardName.indexOf("--")===-1) fd.append("ward",wardName);
    
    var hdrs={};
    if(tk) hdrs["Authorization"]="Bearer "+tk;
    
    var r=await fetch("/api/posts.php",{method:"POST",headers:hdrs,credentials:"include",body:fd});
    var d=await r.json();
    if(d.success){
      closeAskLocation();document.getElementById("askPlace").value="";document.getElementById("askDesc").value="";
      closeAskLocation();
      document.getElementById("askPlace").value="";
      document.getElementById("askDesc").value="";
    }else{
      document.getElementById("askSubmit").textContent="Lỗi: "+(d.message||"Thử lại");
    }
  }catch(e){console.error("Ask error:",e);}
  btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane" style="margin-right:6px"></i>\u0110\u0103ng h\u1ECFi';
}
