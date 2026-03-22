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
  if(pid){closeNotifPage();var cmtId=null;var nk=el.dataset.key||"";if(nk.indexOf("cmt_")===0)cmtId=nk.replace("cmt_","");var url="post-detail.html?id="+pid;if(cmtId)url+="&comment="+cmtId;window.location.href=url;}
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
