
// Notification sound (Web Audio API)
var _notifSoundCtx=null;
function playNotifSound(){
  try{
    if(!_notifSoundCtx)_notifSoundCtx=new(window.AudioContext||window.webkitAudioContext)();
    var osc=_notifSoundCtx.createOscillator();
    var gain=_notifSoundCtx.createGain();
    osc.connect(gain);gain.connect(_notifSoundCtx.destination);
    osc.frequency.setValueAtTime(800,_notifSoundCtx.currentTime);
    osc.frequency.setValueAtTime(600,_notifSoundCtx.currentTime+0.1);
    gain.gain.setValueAtTime(0.1,_notifSoundCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01,_notifSoundCtx.currentTime+0.3);
    osc.start(_notifSoundCtx.currentTime);
    osc.stop(_notifSoundCtx.currentTime+0.3);
  }catch(e){}
}

function groupNotifs(notifs){
  var grouped={};
  notifs.forEach(function(n){
    var key=(n.type||'post')+'_'+(n.post_id||n.target_id||0);
    if(!grouped[key]){grouped[key]={first:n,count:0,actors:[]};}
    grouped[key].count++;
    if(grouped[key].actors.indexOf(n.actor_name)<0&&grouped[key].actors.length<3)grouped[key].actors.push(n.actor_name);
  });
  var result=[];
  for(var k in grouped){
    var g=grouped[k];
    if(g.count>1){
      var n=Object.assign({},g.first);
      n.actor_name=g.actors.join(', ')+(g.count>3?' và '+(g.count-3)+' người khác':'');
      n._grouped=g.count;
      result.push(n);
    }else{result.push(g.first);}
  }
  return result;
}

// ShipperShop Notifications Panel
function toggleNotifPage(){
  var p=document.getElementById("notifPage");
  if(p.style.display==="none"||!p.style.display){
    p.style.display="block";
    document.body.style.overflow="hidden";
    loadNotifPage();
  }else{closeNotifPage();}
}
function closeNotifPage(){
  document.getElementById("notifPage").style.display="none";
  document.body.style.overflow="";
}
function ntAgo(d){
  var s=Math.floor((Date.now()-new Date(d).getTime())/1000);
  if(s<60)return s+" giây";
  if(s<3600)return Math.floor(s/60)+" phút";
  if(s<86400)return Math.floor(s/3600)+" giờ";
  return Math.floor(s/86400)+" ngày";
}
async function loadNotifPage(){
  var list=document.getElementById("notifPageList");
  var token=localStorage.getItem("token");
  if(!token){list.innerHTML="<div style=\"text-align:center;padding:60px;color:#999\"><i class=\"fas fa-bell\" style=\"font-size:48px;color:#ddd;display:block;margin-bottom:12px\"></i>Đăng nhập để xem thông báo</div>";return;}
  try{
    var r=await fetch("/api/notifications.php",{headers:{"Authorization":"Bearer "+token}});
    var d=await r.json();
    if(!d.success||!d.data||d.data.length===0){
      list.innerHTML="<div style=\"text-align:center;padding:60px;color:#999\"><div style=\"font-size:48px;margin-bottom:12px\">🔔</div>Chưa có thông báo nào</div>";
      return;
    }
    var unread=d.data.filter(function(n){return !n.is_read});
    var read=d.data.filter(function(n){return n.is_read});
    var html="";
    if(unread.length>0){
      html+="<div style=\"padding:14px 16px 8px;font-weight:700;font-size:16px;color:#111\">Mới</div>";
      html+=unread.map(function(n){return mkNotif(n)}).join("");
    }
    if(read.length>0){
      html+="<div style=\"padding:14px 16px 8px;font-weight:700;font-size:16px;color:#111\">Trước đó</div>";
      html+=read.map(function(n){return mkNotif(n)}).join("");
    }
    list.innerHTML=html;
    updateNotifBadge(d.data);
  }catch(e){
    list.innerHTML="<div style=\"text-align:center;padding:60px;color:#999\">Lỗi tải thông báo</div>";
  }
}
function mkNotif(n){
  var icon=n.type==="like"?"❤️":"💬";
  var iconBg=n.type==="like"?"background:#E74C3C":"background:#3498DB";
  var label=n.type==="like"?"đã thích bài viết của bạn":"đã bình luận bài viết của bạn";
  var bg=n.is_read?"background:#fff;":"background:#FFF0EB;";
  var dot=n.is_read?"":"<div style=\"width:10px;height:10px;border-radius:50%;background:var(--primary);flex-shrink:0\"></div>";
  var av=n.actor_avatar
    ?"<div style=\"position:relative;flex-shrink:0\"><img src=\""+n.actor_avatar+"\" style=\"width:52px;height:52px;border-radius:50%;object-fit:cover\" loading=\"lazy\"><div style=\"position:absolute;bottom:-2px;right:-2px;width:20px;height:20px;border-radius:50%;"+iconBg+";display:flex;align-items:center;justify-content:center;font-size:10px;border:2px solid #fff\">"+icon+"</div></div>"
    :"<div style=\"position:relative;flex-shrink:0\"><div style=\"width:52px;height:52px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px\">"+((n.actor_name||"U").charAt(0))+"</div><div style=\"position:absolute;bottom:-2px;right:-2px;width:20px;height:20px;border-radius:50%;"+iconBg+";display:flex;align-items:center;justify-content:center;font-size:10px;border:2px solid #fff\">"+icon+"</div></div>";
  return "<div data-key=\""+n.notif_key+"\" data-postid=\""+n.post_id+"\" data-read=\""+(n.is_read?"1":"0")+"\" style=\"display:flex;gap:12px;padding:12px 16px;cursor:pointer;align-items:center;"+bg+"\" onclick=\"clickNotif(this)\">" +av+"<div style=\"flex:1;min-width:0\"><div style=\"font-size:14px;line-height:1.4;color:#333\"><b>"+n.actor_name+"</b> "+label+"</div><div style=\"font-size:12px;color:var(--primary);margin-top:4px;font-weight:600\">"+ntAgo(n.created_at)+"</div></div>"+dot+"</div>";
}
function clickNotif(el){
  var key=el.dataset.key;
  var pid=el.dataset.postid;
  if(el.dataset.read!=="1"){
    el.dataset.read="1";
    el.style.background="#fff";
    var dot=el.querySelector("div[style*=\"border-radius:50%;background:var(--primary)\"]");
    if(dot)dot.remove();
    var token=localStorage.getItem("token");
    fetch("/api/notifications.php",{method:"POST",headers:{"Authorization":"Bearer "+token,"Content-Type":"application/json"},body:JSON.stringify({notif_key:key})});
    var items=document.querySelectorAll("#notifPageList [data-read=\"0\"]");
    var badge=document.getElementById("tabNotifBadge");
    if(badge){if(items.length>0){badge.style.display="flex";badge.textContent=items.length>99?"99+":items.length;}else{badge.style.display="none";}}
  }
  if(pid){closeNotifPage();var cmtId=null;var nk=el.dataset.key||"";if(nk.indexOf("cmt_")===0)cmtId=nk.replace("cmt_","");var url;
    var nType=(el.dataset.type||'').toLowerCase();
    if(nType==='group')url='group.html?id='+(el.dataset.groupid||pid);
    else if(nType==='message')url='messages.html?conv='+(el.dataset.convid||'');
    else if(nType==='follow')url='user.html?id='+(el.dataset.actorid||'');
    else url="post-detail.html?id="+pid;if(cmtId)url+="&comment="+cmtId;window.location.href=url;}
}
function markAllRead(){
  var items=document.querySelectorAll("#notifPageList [data-read=\"0\"]");
  var token=localStorage.getItem("token");
  items.forEach(function(el){
    el.dataset.read="1";
    el.style.background="#fff";
    var dot=el.querySelector("div[style*=\"border-radius:50%;background:var(--primary)\"]");
    if(dot)dot.remove();
    fetch("/api/notifications.php",{method:"POST",headers:{"Authorization":"Bearer "+token,"Content-Type":"application/json"},body:JSON.stringify({notif_key:el.dataset.key})});
  });
  var badge=document.getElementById("tabNotifBadge");
  if(badge)badge.style.display="none";
}
function updateNotifBadge(data){
  var unread=data.filter(function(n){return !n.is_read}).length;
  var badge=document.getElementById("tabNotifBadge");
  if(badge){if(unread>0){badge.style.display="flex";badge.textContent=unread>99?"99+":unread;}else{badge.style.display="none";}}
}


// === NOTIFICATION BADGE POLLING ===
var _notifTimer=null;
function pollNotifCount(){
  var token=localStorage.getItem('token');
  if(!token)return;
  fetch('/api/notifications.php?action=unread_count',{headers:{'Authorization':'Bearer '+token}})
    .then(function(r){return r.json()})
    .then(function(d){
      var count=d.data?d.data.count:0;
      // Update tab badge
      var badge=document.getElementById('tabNotifBadge');
      if(badge){
        if(count>0){badge.textContent=count>99?'99+':count;badge.style.display='inline-block';}
        else{badge.style.display='none';}
      }
      // Update bell badge
      var bell=document.getElementById('ss-bell-badge');
      if(bell){
        if(count>0){bell.textContent=count>99?'99+':count;bell.style.display='inline-block';}
        else{bell.style.display='none';}
      }
    })
    .catch(function(){});
}
// Poll every 30s, first check after 3s
if(localStorage.getItem('token')){
  setTimeout(pollNotifCount,3000);
  _notifTimer=setInterval(pollNotifCount,30000);
  // Stop polling when tab hidden
  document.addEventListener('visibilitychange',function(){
    if(document.hidden){clearInterval(_notifTimer);_notifTimer=null;}
    else if(!_notifTimer){pollNotifCount();_notifTimer=setInterval(pollNotifCount,30000);}
  });
}

// Mark single notification as read
function markNotifRead(key){
  var token=localStorage.getItem('token');
  if(!token||!key)return;
  fetch('/api/notifications.php',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({notif_key:key})}).catch(function(){});
}
// Mark all read (for header button)
function markAllRead(){
  var token=localStorage.getItem('token');
  if(!token)return;
  fetch('/api/notifications.php?action=mark_all_read',{method:'POST',headers:{'Authorization':'Bearer '+token}}).then(function(){
    var badge=document.getElementById('tabNotifBadge');
    if(badge)badge.style.display='none';
    pollNotifCount();
  }).catch(function(){});
}

// Message unread count polling
function pollMsgCount(){
  var token=localStorage.getItem('token');
  if(!token)return;
  fetch('/api/messages-api.php?action=unread_total',{headers:{'Authorization':'Bearer '+token}})
    .then(function(r){return r.json()})
    .then(function(d){
      var count=d.data?d.data.count:0;
      // Update any message badges on page
      var badges=document.querySelectorAll('.msg-unread-badge');
      badges.forEach(function(b){
        if(count>0){b.textContent=count>99?'99+':count;b.style.display='inline-block';}
        else{b.style.display='none';}
      });
    }).catch(function(){});
}
// Add to existing poll cycle
if(localStorage.getItem('token')){
  setTimeout(pollMsgCount,4000);
  setInterval(pollMsgCount,60000); // Every 60s for messages
}

// Push notification permission
function requestPushPermission(){
  if(!('Notification' in window)){toast('Trình duyệt không hỗ trợ thông báo');return;}
  if(Notification.permission==='granted'){toast('Đã bật thông báo!','success');return;}
  if(Notification.permission==='denied'){toast('Thông báo đã bị chặn. Vui lòng bật trong cài đặt trình duyệt.','warning');return;}
  Notification.requestPermission().then(function(perm){
    if(perm==='granted'){
      toast('Đã bật thông báo!','success');
      // Subscribe to push
      if('serviceWorker' in navigator&&navigator.serviceWorker.controller){
        navigator.serviceWorker.ready.then(function(reg){
          reg.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:urlB64ToUint8Array('BKxE1234placeholder')}).then(function(sub){
            var token=localStorage.getItem('token');
            fetch('/api/push-subscribe.php',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({subscription:sub.toJSON()})}).catch(function(){});
          }).catch(function(){});
        });
      }
    }
  });
}
function urlB64ToUint8Array(base64String){
  var padding='='.repeat((4-base64String.length%4)%4);
  var base64=(base64String+padding).replace(/-/g,'+').replace(/_/g,'/');
  var rawData=atob(base64);
  var outputArray=new Uint8Array(rawData.length);
  for(var i=0;i<rawData.length;++i)outputArray[i]=rawData.charCodeAt(i);
  return outputArray;
}
// Show push permission prompt after 30s on first visit
if(localStorage.getItem('token')&&!localStorage.getItem('ss_push_asked')){
  setTimeout(function(){
    if(Notification.permission==='default'){
      var banner=document.createElement('div');
      banner.style.cssText='position:fixed;bottom:80px;left:16px;right:16px;background:#fff;border-radius:12px;padding:14px 16px;box-shadow:0 4px 20px rgba(0,0,0,.15);z-index:1500;display:flex;align-items:center;gap:12px';
      banner.innerHTML='<i class="fas fa-bell" style="font-size:20px;color:#7C3AED"></i><div style="flex:1"><div style="font-weight:600;font-size:14px">Bật thông báo?</div><div style="font-size:12px;color:#65676B">Nhận thông báo khi có tương tác mới</div></div><button onclick="requestPushPermission();this.closest(\'div[style]\').remove();localStorage.setItem(\'ss_push_asked\',\'1\')" style="padding:6px 14px;background:#7C3AED;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer">Bật</button><button onclick="this.closest(\'div[style]\').remove();localStorage.setItem(\'ss_push_asked\',\'1\')" style="background:none;border:none;color:#999;cursor:pointer;font-size:18px">&times;</button>';
      document.body.appendChild(banner);
    }
    localStorage.setItem('ss_push_asked','1');
  },30000);
}
