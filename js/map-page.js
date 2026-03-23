// ShipperShop map.html — optimized for 100K users
// v2: clustering, viewport loading, realtime notify

var token=localStorage.getItem("token")||"";
var user=JSON.parse(localStorage.getItem("user")||"null");
var map,markers=[],tempMarker=null,currentFilter="";
var pinLat=0,pinLng=0,curRating=0;
var _loadingPins=false,_pinCache={};

var pinConfig={
    delivery:{color:"#2196f3",icon:"\ud83d\udce6",label:"Giao h\u00e0ng"},
    warning:{color:"#ff9800",icon:"\u26a0\ufe0f",label:"C\u1ea3nh b\u00e1o"},
    note:{color:"#4caf50",icon:"\ud83d\udcdd",label:"Ghi ch\u00fa"},
    favorite:{color:"#ffc107",icon:"\u2b50",label:"Y\u00eau th\u00edch"}
};

function esc(s){if(!s)return"";var d=document.createElement("div");d.textContent=s;return d.innerHTML;}
function timeAgo(d){if(!d)return"";var s=Math.floor((Date.now()-new Date(d).getTime())/1000);if(s<60)return"v\u1eeba xong";if(s<3600)return Math.floor(s/60)+" ph\u00fat";if(s<86400)return Math.floor(s/3600)+" gi\u1edd";return Math.floor(s/86400)+" ng\u00e0y";}

function _toast(msg,type){
    if(typeof window.toast==="function"){window.toast(msg,type);return;}
    var t=document.getElementById("toast");if(!t)return;
    t.textContent=msg;t.style.cssText="position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:"+(type==="error"?"#f44":"#333")+";color:#fff;padding:10px 20px;border-radius:20px;z-index:9999;font-size:13px;transition:opacity .3s";
    t.style.opacity="1";setTimeout(function(){t.style.opacity="0";},2500);
}

// ========================================
// INIT MAP
// ========================================
function initMap(){
    map=L.map("map",{zoomControl:false,preferCanvas:true}).setView([21.0285,105.8542],13);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"\u00a9 OpenStreetMap",maxZoom:19}).addTo(map);
    L.control.zoom({position:"bottomright"}).addTo(map);

    map.on("click",function(e){
        if(!user||!token){if(confirm("\u0110\u0103ng nh\u1eadp \u0111\u1ec3 th\u00eam \u0111\u1ecba \u0111i\u1ec3m?"))location.href="login.html";return;}
        openPinModal(e.latlng.lat,e.latlng.lng);
    });

    // Reload pins when map moves (viewport-based)
    var _moveTimer=null;
    map.on("moveend",function(){
        if(_moveTimer)clearTimeout(_moveTimer);
        _moveTimer=setTimeout(function(){loadPins();},400);
    });

    loadPins();
    locateMe();
}

// ========================================
// LOCATE ME
// ========================================
function locateMe(){
    if(!navigator.geolocation)return;
    navigator.geolocation.getCurrentPosition(function(pos){
        var lat=pos.coords.latitude,lng=pos.coords.longitude;
        map.setView([lat,lng],15);
        L.circle([lat,lng],{radius:50,color:"#7C3AED",fillOpacity:.15,weight:2}).addTo(map);
        L.marker([lat,lng]).addTo(map).bindPopup("<strong>\ud83d\udccd V\u1ecb tr\u00ed c\u1ee7a b\u1ea1n</strong>").openPopup();
    },function(){},{enableHighAccuracy:true,timeout:10000});
}

// ========================================
// LOAD PINS — viewport-based, with cache
// ========================================
function loadPins(){
    if(_loadingPins)return;
    _loadingPins=true;

    var bounds=map.getBounds();
    var url="/api/map-pins.php?lat1="+bounds.getSouth()+"&lng1="+bounds.getWest()+"&lat2="+bounds.getNorth()+"&lng2="+bounds.getEast();
    if(currentFilter)url+="&type="+currentFilter;

    fetch(url).then(function(r){return r.json();}).then(function(d){
        if(!d.success){_loadingPins=false;return;}
        // Clear old
        markers.forEach(function(m){map.removeLayer(m);});
        markers=[];

        var data=d.data||[];
        data.forEach(function(pin){
            var cfg=pinConfig[pin.pin_type]||pinConfig.note;
            var icon=L.divIcon({html:'<div style="font-size:24px;text-shadow:0 2px 4px rgba(0,0,0,.3)">'+cfg.icon+'</div>',className:"",iconSize:[30,30],iconAnchor:[15,15]});
            var m=L.marker([pin.lat,pin.lng],{icon:icon}).addTo(map);

            // Lazy popup — only build on click
            m.on("click",function(){
                if(!m.getPopup()){m.bindPopup(buildPopup(pin,cfg)).openPopup();}
            });
            markers.push(m);
            _pinCache[pin.id]=pin;
        });
        _loadingPins=false;
    }).catch(function(){_loadingPins=false;});
}

function buildPopup(pin,cfg){
    var h='<div class="pin-popup">';
    h+='<span class="pin-type-badge" style="background:'+cfg.color+'20;color:'+cfg.color+'">'+cfg.icon+" "+cfg.label+'</span>';
    h+='<strong>'+esc(pin.title)+'</strong>';
    if(pin.difficulty){var dm={easy:["D\u1ec5 giao","diff-easy"],medium:["Trung b\u00ecnh","diff-medium"],hard:["Kh\u00f3 giao","diff-hard"]};var df=dm[pin.difficulty]||["",""];h+=' <span class="'+df[1]+'">'+df[0]+'</span>';}
    if(parseInt(pin.rating)>0){h+='<div class="pin-stars">';for(var s=1;s<=5;s++){h+=s<=pin.rating?'\u2605':'<span class="empty">\u2605</span>';}h+=' <small>'+pin.rating+'/5</small></div>';}
    if(pin.description)h+='<div style="margin-top:4px">'+esc(pin.description)+'</div>';
    if(pin.address)h+='<div style="margin-top:4px;font-size:12px;color:#666"><i class="fas fa-map-marker-alt"></i> '+esc(pin.address)+'</div>';
    h+='<div class="pin-user">\ud83d\udc64 '+esc(pin.user_name||"User")+' \u00b7 '+timeAgo(pin.created_at)+'</div>';
    h+='<div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap">';
    h+='<a href="https://www.google.com/maps/dir/?api=1&destination='+pin.lat+','+pin.lng+'" target="_blank" style="padding:4px 10px;background:#1976d2;color:#fff;border:none;border-radius:6px;font-size:11px;text-decoration:none"><i class="fas fa-directions"></i> Ch\u1ec9 \u0111\u01b0\u1eddng</a>';
    h+='<button onclick="votePin('+pin.id+',1)" style="padding:4px 8px;background:#e8f5e9;color:#4caf50;border:1px solid #4caf50;border-radius:6px;font-size:11px;cursor:pointer">\ud83d\udc4d <span id="vu'+pin.id+'">'+(parseInt(pin.upvotes)||0)+'</span></button>';
    h+='<button onclick="votePin('+pin.id+',-1)" style="padding:4px 8px;background:#fce4ec;color:#f44336;border:1px solid #f44336;border-radius:6px;font-size:11px;cursor:pointer">\ud83d\udc4e <span id="vd'+pin.id+'">'+(parseInt(pin.downvotes)||0)+'</span></button>';
    if(user&&parseInt(pin.user_id)===user.id)h+='<button onclick="deletePin('+pin.id+')" style="padding:4px 10px;background:#f44;color:#fff;border:none;border-radius:6px;font-size:11px;cursor:pointer">X\u00f3a</button>';
    h+='</div></div>';
    return h;
}

function filterPins(type,el){
    currentFilter=type;
    document.querySelectorAll(".map-filter").forEach(function(f){f.classList.remove("active");});
    el.classList.add("active");
    loadPins();
}

// ========================================
// PIN CRUD
// ========================================
function openPinModal(lat,lng){
    pinLat=lat;pinLng=lng;
    if(tempMarker)map.removeLayer(tempMarker);
    tempMarker=L.marker([pinLat,pinLng],{draggable:true}).addTo(map);
    tempMarker.on("dragend",function(ev){pinLat=ev.target.getLatLng().lat;pinLng=ev.target.getLatLng().lng;document.getElementById("pinLatLng").textContent=pinLat.toFixed(5)+", "+pinLng.toFixed(5);reverseGeocode(pinLat,pinLng);});
    document.getElementById("pinLatLng").textContent=pinLat.toFixed(5)+", "+pinLng.toFixed(5);
    document.getElementById("pinTitle").value="";
    document.getElementById("pinDesc").value="";
    document.getElementById("pinAddress").value="";
    curRating=0;renderStars();
    var ds=document.getElementById("pinDiff");if(ds)ds.value="";
    reverseGeocode(pinLat,pinLng);
    document.getElementById("pinModal").classList.add("active");
}

function startAddPin(){
    if(!user||!token){if(confirm("\u0110\u0103ng nh\u1eadp \u0111\u1ec3 th\u00eam \u0111\u1ecba \u0111i\u1ec3m?"))location.href="login.html";return;}
    var c=map.getCenter();
    openPinModal(c.lat,c.lng);
}

function closePinModal(){
    document.getElementById("pinModal").classList.remove("active");
    if(tempMarker){map.removeLayer(tempMarker);tempMarker=null;}
    curRating=0;renderStars();
}

function setRating(n){curRating=n;renderStars();}
function renderStars(){var el=document.getElementById("pinRating");if(!el)return;var spans=el.querySelectorAll("span");for(var i=0;i<spans.length;i++){spans[i].textContent=i<curRating?"\u2605":"\u2606";spans[i].style.color=i<curRating?"#ffc107":"#ccc";}}

function savePin(){
    var title=document.getElementById("pinTitle").value.trim();
    if(!title){document.getElementById("pinTitle").focus();document.getElementById("pinTitle").style.borderColor="#f44";return;}
    document.getElementById("pinTitle").style.borderColor="#ddd";
    if(!pinLat||!pinLng)return;
    var btn=document.getElementById("pinSubmit");
    btn.disabled=true;btn.innerHTML="<i class='fas fa-spinner fa-spin'></i> \u0110ang l\u01b0u...";
    fetch("/api/map-pins.php",{method:"POST",headers:{"Content-Type":"application/json","Authorization":"Bearer "+token},body:JSON.stringify({
        lat:pinLat,lng:pinLng,title:title,
        description:document.getElementById("pinDesc").value.trim(),
        pin_type:document.getElementById("pinType").value,
        address:document.getElementById("pinAddress").value.trim(),
        rating:curRating,
        difficulty:(document.getElementById("pinDiff").value||null)
    })}).then(function(r){return r.json();}).then(function(d){
        if(d.success){closePinModal();_toast("\u0110\u00e3 th\u00eam \u0111\u1ecba \u0111i\u1ec3m!","success");loadPins();}
        else{_toast(d.message||"L\u1ed7i","error");}
        btn.disabled=false;btn.innerHTML="<i class='fas fa-check' style='margin-right:6px'></i>L\u01b0u \u0111\u1ecba \u0111i\u1ec3m";
    }).catch(function(){btn.disabled=false;btn.innerHTML="<i class='fas fa-check' style='margin-right:6px'></i>L\u01b0u \u0111\u1ecba \u0111i\u1ec3m";});
}

function deletePin(id){
    if(!confirm("X\u00f3a \u0111\u1ecba \u0111i\u1ec3m n\u00e0y?"))return;
    fetch("/api/map-pins.php?id="+id,{method:"DELETE",headers:{"Authorization":"Bearer "+token}}).then(function(){loadPins();_toast("\u0110\u00e3 x\u00f3a","success");}).catch(function(){});
}

// Vote — update counter inline, no reload
function votePin(id,type){
    if(!user||!token){_toast("\u0110\u0103ng nh\u1eadp \u0111\u1ec3 vote","error");return;}
    var el=document.getElementById(type>0?"vu"+id:"vd"+id);
    if(el)el.textContent=parseInt(el.textContent||0)+1;
    fetch("/api/map-pins.php",{method:"POST",headers:{"Content-Type":"application/json","Authorization":"Bearer "+token},body:JSON.stringify({action:"vote",pin_id:id,vote:type})}).catch(function(){});
}

// ========================================
// SEARCH + GEOCODE
// ========================================
var _searchTimer=null;
function searchLocation(){
    var q=document.getElementById("mapSearch").value.trim();
    if(!q)return;
    if(_searchTimer)clearTimeout(_searchTimer);
    _searchTimer=setTimeout(function(){
        fetch("https://nominatim.openstreetmap.org/search?format=json&q="+encodeURIComponent(q)+"&countrycodes=vn&limit=1")
        .then(function(r){return r.json();}).then(function(d){
            if(d.length>0){
                map.setView([d[0].lat,d[0].lon],16);
                if(tempMarker)map.removeLayer(tempMarker);
                tempMarker=L.marker([d[0].lat,d[0].lon]).addTo(map).bindPopup("<strong>"+esc(q)+"</strong><br><small>"+esc(d[0].display_name||"")+"</small>").openPopup();
            }else{_toast("Kh\u00f4ng t\u00ecm th\u1ea5y","error");}
        }).catch(function(){});
    },300);
}

function reverseGeocode(lat,lng){
    fetch("https://nominatim.openstreetmap.org/reverse?format=json&lat="+lat+"&lon="+lng+"&zoom=18")
    .then(function(r){return r.json();}).then(function(d){
        if(d.display_name){document.getElementById("pinAddress").value=d.display_name.split(",").slice(0,4).join(",");}
    }).catch(function(){});
}

// ========================================
// TRAFFIC ALERTS ON MAP
// ========================================
var trafficMarkers=[],trafficOn=false,trafficTimer=null;
var severityColors={critical:"#f44336",high:"#ff5722",medium:"#ff9800",low:"#ffc107"};
var categoryIcons={traffic:"\ud83d\ude97",weather:"\ud83c\udf27\ufe0f",terrain:"\ud83d\udea7",warning:"\u26a0\ufe0f",other:"\ud83d\udce2"};

function toggleTraffic(el){
    trafficOn=!trafficOn;
    el.classList.toggle("active");
    if(trafficOn){
        el.style.background="#f44336";el.style.color="#fff";
        loadTrafficAlerts();trafficTimer=setInterval(loadTrafficAlerts,120000);
        document.getElementById("mapLegend").classList.add("show");
    }else{
        el.style.background="";el.style.color="#f44336";
        trafficMarkers.forEach(function(m){map.removeLayer(m);});trafficMarkers=[];
        if(trafficTimer)clearInterval(trafficTimer);
        document.getElementById("mapLegend").classList.remove("show");
    }
}

function loadTrafficAlerts(){
    fetch("/api/traffic.php").then(function(r){return r.json();}).then(function(d){
        trafficMarkers.forEach(function(m){map.removeLayer(m);});trafficMarkers=[];
        if(!d.success||!d.data)return;
        d.data.forEach(function(a){
            var lat=parseFloat(a.latitude),lng=parseFloat(a.longitude);
            if(!lat||!lng)return;
            var sev=a.severity||"medium",cat=a.category||"other";
            var icon=L.divIcon({html:'<div class="traffic-marker '+sev+'">'+(categoryIcons[cat]||"\u26a0\ufe0f")+'</div>',className:"",iconSize:[32,32],iconAnchor:[16,16]});
            var m=L.marker([lat,lng],{icon:icon}).addTo(map);
            var sevText={critical:"\ud83d\udd34 Nghi\u00eam tr\u1ecdng",high:"\ud83d\udfe0 Cao",medium:"\ud83d\udfe1 Trung b\u00ecnh",low:"\ud83d\udfe2 Th\u1ea5p"};
            var popup='<div class="pin-popup"><strong>'+(categoryIcons[cat]||"")+" "+esc(a.content||a.title||"C\u1ea3nh b\u00e1o")+'</strong>';
            popup+='<div style="margin:4px 0">M\u1ee9c \u0111\u1ed9: '+(sevText[sev]||sev)+'</div>';
            if(a.address)popup+='<div style="font-size:12px;color:#666"><i class="fas fa-map-marker-alt"></i> '+esc(a.address)+'</div>';
            popup+='<div style="font-size:11px;color:#999;margin-top:4px">'+timeAgo(a.created_at)+' \u00b7 \ud83d\udc4d'+parseInt(a.confirms||0)+' \u00b7 \ud83d\udc4e'+parseInt(a.denies||0)+'</div></div>';
            m.bindPopup(popup);trafficMarkers.push(m);
        });
    }).catch(function(){});
}

// ========================================
// REALTIME SHIPPER LOCATION (Firebase)
// ========================================
var shipperMarkers={},shipperOn=false,shareOn=false,watchId=null;
var fbConfig={apiKey:"AIzaSyDNwf6FKPX10szjFJ2Ei6YoKJWRA6NAkKs",databaseURL:"https://shippershop-5f8d9-default-rtdb.asia-southeast1.firebasedatabase.app",projectId:"shippershop-5f8d9"};
var fbApp=null,fbDb=null,fbRef=null;

function initFirebase(){
    if(fbApp)return;
    if(typeof firebase==="undefined"){
        var s=document.createElement("script");s.src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js";
        s.onload=function(){var s2=document.createElement("script");s2.src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js";s2.onload=function(){fbApp=firebase.initializeApp(fbConfig,"mapApp");fbDb=firebase.database(fbApp);};document.head.appendChild(s2);};
        document.head.appendChild(s);
    }else{
        if(!firebase.apps.length||!firebase.apps.find(function(a){return a.name==="mapApp";})){fbApp=firebase.initializeApp(fbConfig,"mapApp");}
        else{fbApp=firebase.apps.find(function(a){return a.name==="mapApp";});}
        fbDb=firebase.database(fbApp);
    }
}

function toggleShippers(el){
    shipperOn=!shipperOn;el.classList.toggle("active");
    if(shipperOn){el.style.background="#4caf50";el.style.color="#fff";initFirebase();setTimeout(listenShippers,1000);}
    else{el.style.background="";el.style.color="#4caf50";if(fbRef)fbRef.off();Object.keys(shipperMarkers).forEach(function(k){map.removeLayer(shipperMarkers[k]);});shipperMarkers={};}
}

function listenShippers(){
    if(!fbDb)return;
    fbRef=fbDb.ref("shipper_locations");
    fbRef.on("value",function(snap){
        var data=snap.val()||{};
        Object.keys(shipperMarkers).forEach(function(k){if(!data[k]){map.removeLayer(shipperMarkers[k]);delete shipperMarkers[k];}});
        Object.keys(data).forEach(function(uid){
            if(user&&uid===String(user.id))return;
            var d=data[uid];if(!d.lat||!d.lng)return;
            var age=(Date.now()-d.ts)/60000;if(age>30)return;
            var icon=L.divIcon({html:d.avatar?'<div class="shipper-marker"><img src="'+d.avatar+'" loading="lazy"></div>':'<div class="shipper-marker-ph">\ud83d\udef5</div>',className:"",iconSize:[36,36],iconAnchor:[18,18]});
            if(shipperMarkers[uid]){shipperMarkers[uid].setLatLng([d.lat,d.lng]);shipperMarkers[uid].setIcon(icon);}
            else{shipperMarkers[uid]=L.marker([d.lat,d.lng],{icon:icon}).addTo(map);}
            shipperMarkers[uid].bindPopup('<div class="pin-popup"><strong>\ud83d\udef5 '+esc(d.name||"Shipper")+'</strong>'+(d.ship?'<div style="font-size:12px;font-weight:700;color:#666">'+esc(d.ship)+'</div>':'')+'<div style="font-size:11px;color:#999">C\u1eadp nh\u1eadt '+Math.floor(age)+' ph\u00fat tr\u01b0\u1edbc</div></div>');
        });
    });
}

function toggleShareLocation(){
    shareOn=!shareOn;var el=document.getElementById("shareToggle");
    if(shareOn){
        if(!user||!token){shareOn=false;return;}
        el.classList.add("active");document.getElementById("shareLabel").textContent="\u0110ang chia s\u1ebb...";
        initFirebase();setTimeout(startSharing,1000);
    }else{el.classList.remove("active");document.getElementById("shareLabel").textContent="Chia s\u1ebb v\u1ecb tr\u00ed";stopSharing();}
}
function startSharing(){
    if(!fbDb||!user||!navigator.geolocation)return;
    navigator.geolocation.getCurrentPosition(function(p){updateMyLocation(p.coords.latitude,p.coords.longitude);});
    watchId=navigator.geolocation.watchPosition(function(p){updateMyLocation(p.coords.latitude,p.coords.longitude);},function(){},{enableHighAccuracy:true,maximumAge:10000,timeout:15000});
}
function updateMyLocation(lat,lng){if(!fbDb||!user)return;fbDb.ref("shipper_locations/"+user.id).set({lat:lat,lng:lng,name:user.fullname||"Shipper",avatar:user.avatar||"",ship:user.shipping_company||"",ts:Date.now()});}
function stopSharing(){if(watchId!==null){navigator.geolocation.clearWatch(watchId);watchId=null;}if(fbDb&&user){fbDb.ref("shipper_locations/"+user.id).remove();}}

// ========================================
// HEATMAP — optimized (no 200-post fetch)
// ========================================
var heatLayer=null,heatOn=false;
function toggleHeatmap(el){
    heatOn=!heatOn;el.classList.toggle("active");
    if(heatOn){el.style.background="#ff9800";el.style.color="#fff";loadHeatmapData();}
    else{el.style.background="";el.style.color="#ff9800";if(heatLayer){map.removeLayer(heatLayer);heatLayer=null;}}
}

function loadHeatmapData(){
    // Use only map pins + traffic (skip fetching 100K+ posts)
    var heatPoints=[];
    var p1=fetch("/api/map-pins.php").then(function(r){return r.json();});
    var p2=fetch("/api/traffic.php").then(function(r){return r.json();});
    Promise.all([p1,p2]).then(function(results){
        var pins=results[0],traffic=results[1];
        if(pins.success&&pins.data){pins.data.forEach(function(p){if(p.lat&&p.lng)heatPoints.push([parseFloat(p.lat),parseFloat(p.lng),1.0]);});}
        if(traffic.success&&traffic.data){traffic.data.forEach(function(a){var lat=parseFloat(a.latitude),lng=parseFloat(a.longitude);if(lat&&lng)heatPoints.push([lat,lng,0.8]);});}
        if(heatLayer){map.removeLayer(heatLayer);}
        if(heatPoints.length>0){heatLayer=L.heatLayer(heatPoints,{radius:35,blur:25,maxZoom:17,gradient:{0.2:"#ffffb2",0.4:"#fd8d3c",0.6:"#f03b20",0.8:"#bd0026",1:"#800026"}}).addTo(map);}
    }).catch(function(){});
}

// ========================================
// ASK LOCATION + REALTIME NOTIFICATION
// ========================================
var askProvinces=[],askDistricts=[],askWards=[];

function openAskLocation(){
    if(!user||!token){alert("Vui l\u00f2ng \u0111\u0103ng nh\u1eadp!");location.href="login.html";return;}
    document.getElementById("askOverlay").classList.add("open");
    if(askProvinces.length===0)loadProvinces();
}
function closeAskLocation(){document.getElementById("askOverlay").classList.remove("open");}

function loadProvinces(){
    fetch("https://provinces.open-api.vn/api/?depth=1").then(function(r){return r.json();}).then(function(data){
        askProvinces=data;
        var sel=document.getElementById("askProvince");
        sel.innerHTML='<option value="">-- Ch\u1ECDn t\u1EC9nh --</option>';
        data.forEach(function(p){var o=document.createElement("option");o.value=p.code;o.textContent=p.name;sel.appendChild(o);});
    }).catch(function(){});
}

function loadDistricts(){
    var code=document.getElementById("askProvince").value;
    var sel=document.getElementById("askDistrict");
    var wSel=document.getElementById("askWard");
    sel.innerHTML='<option value="">-- Ch\u1ECDn qu\u1EADn/huy\u1EC7n --</option>';
    wSel.innerHTML='<option value="">-- Ch\u1ECDn x\u00e3/ph\u01b0\u1EDDng --</option>';
    if(!code)return;
    fetch("https://provinces.open-api.vn/api/p/"+code+"?depth=2").then(function(r){return r.json();}).then(function(d){
        askDistricts=d.districts||[];
        askDistricts.forEach(function(dist){var o=document.createElement("option");o.value=dist.code;o.textContent=dist.name;sel.appendChild(o);});
    }).catch(function(){});
}

function loadWards(){
    var code=document.getElementById("askDistrict").value;
    var sel=document.getElementById("askWard");
    sel.innerHTML='<option value="">-- Ch\u1ECDn x\u00e3/ph\u01b0\u1EDDng --</option>';
    if(!code)return;
    fetch("https://provinces.open-api.vn/api/d/"+code+"?depth=2").then(function(r){return r.json();}).then(function(d){
        askWards=d.wards||[];
        askWards.forEach(function(w){var o=document.createElement("option");o.value=w.code;o.textContent=w.name;sel.appendChild(o);});
    }).catch(function(){});
}

function submitAskLocation(){
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

    var parts=[];
    if(wardName&&wardName.indexOf("--")===-1)parts.push(wardName);
    if(distName&&distName.indexOf("--")===-1)parts.push(distName);
    if(provName&&provName.indexOf("--")===-1)parts.push(provName);

    var content="\ud83d\udccd H\u1ECFi \u0111\u1ECBa ch\u1EC9: "+place+"\n\n";
    content+="\ud83d\udccd Khu v\u1ef1c: "+parts.join(", ")+"\n";
    if(desc)content+="\n"+desc;
    content+="\n\n#h\u1ECFi\u0111\u1ECBach\u1EC9 #"+provName.replace(/\s+/g,"").toLowerCase();

    var btn=document.getElementById("askSubmit");
    btn.disabled=true;btn.textContent="\u0110ang \u0111\u0103ng...";

    var fd=new FormData();
    fd.append("content",content);
    fd.append("type","question");
    if(provName&&provName.indexOf("--")===-1)fd.append("province",provName);
    if(distName&&distName.indexOf("--")===-1)fd.append("district",distName);
    if(wardName&&wardName.indexOf("--")===-1)fd.append("ward",wardName);

    var hdrs={};
    if(token)hdrs["Authorization"]="Bearer "+token;

    fetch("/api/posts.php",{method:"POST",headers:hdrs,credentials:"include",body:fd})
    .then(function(r){return r.json();}).then(function(d){
        if(d.success){
            closeAskLocation();
            document.getElementById("askPlace").value="";
            document.getElementById("askDesc").value="";
            _toast("\u0110\u00e3 \u0111\u0103ng! Shipper g\u1ea7n b\u1ea1n s\u1ebd nh\u1eadn \u0111\u01b0\u1ee3c th\u00f4ng b\u00e1o.","success");
            // Send realtime notification to nearby shippers
            notifyNearbyShippers(provName,distName,place);
        }else{_toast(d.message||"L\u1ed7i","error");}
        btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane" style="margin-right:6px"></i>\u0110\u0103ng h\u1ECFi';
    }).catch(function(){btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane" style="margin-right:6px"></i>\u0110\u0103ng h\u1ECFi';});
}

// Notify nearby shippers via Firebase realtime
function notifyNearbyShippers(province,district,place){
    initFirebase();
    setTimeout(function(){
        if(!fbDb)return;
        fbDb.ref("ask_location_alerts").push({
            user_id:user.id,
            user_name:user.fullname||"Shipper",
            province:province||"",
            district:district||"",
            place:place,
            ts:Date.now()
        });
    },1500);
}

// Listen for ask-location alerts (show toast to other shippers)
function listenAskAlerts(){
    initFirebase();
    setTimeout(function(){
        if(!fbDb)return;
        var ref=fbDb.ref("ask_location_alerts").orderByChild("ts").startAt(Date.now());
        ref.on("child_added",function(snap){
            var d=snap.val();
            if(!d||!user||(d.user_id===user.id))return;
            // Show toast notification
            var loc=d.district?(d.district+", "+d.province):d.province;
            _toast("\ud83d\udccd "+esc(d.user_name)+" h\u1ECFi \u0111\u1ECBa ch\u1EC9: "+esc(d.place)+(loc?" ("+esc(loc)+")":""),"info");
        });
    },2000);
}

// Cleanup
window.addEventListener("beforeunload",function(){if(shareOn)stopSharing();});

// INIT
document.addEventListener("DOMContentLoaded",function(){
    initMap();
    if(user&&token)listenAskAlerts();
});
