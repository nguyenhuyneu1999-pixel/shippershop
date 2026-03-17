#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. Tab Thông báo: đổi link profile → mở panel
sed -i 's|<a href="profile.html" class="tab-item" id="tabNotif"><i class="fas fa-bell"></i><span>Thông báo</span><span class="tab-badge" id="tabNotifBadge" style="display:none"></span></a>|<a href="#" class="tab-item" id="tabNotif" onclick="toggleNotifPage();return false"><i class="fas fa-bell"></i><span>Thông báo</span><span class="tab-badge" id="tabNotifBadge" style="display:none"></span></a>|' index.html

# 2. Inject notification overlay + JS before </body>
sed -i '/<\/body>/i\
<!-- NOTIFICATION PAGE -->\
<div id="notifPage" style="display:none;position:fixed;inset:0;z-index:200;background:#fff;overflow-y:auto">\
  <div style="position:sticky;top:0;background:#fff;z-index:1;padding:12px 16px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #eee">\
    <button onclick="closeNotifPage()" style="background:none;border:none;font-size:20px;cursor:pointer;padding:4px;color:#333"><i class="fas fa-arrow-left"></i></button>\
    <h2 style="font-size:20px;font-weight:700;color:#111;flex:1">Thông báo</h2>\
    <button onclick="markAllRead()" style="background:none;border:none;font-size:13px;color:var(--primary);cursor:pointer;font-weight:600">Đọc tất cả</button>\
  </div>\
  <div id="notifPageList" style="padding:0"><div style="text-align:center;padding:60px;color:#999"><i class="fas fa-spinner spin" style="font-size:24px"></i><p style="margin-top:8px">Đang tải...</p></div></div>\
</div>\
\
<script>\
function toggleNotifPage(){\
  var p=document.getElementById("notifPage");\
  if(p.style.display==="none"||!p.style.display){\
    p.style.display="block";\
    document.body.style.overflow="hidden";\
    loadNotifPage();\
  }else{closeNotifPage();}\
}\
function closeNotifPage(){\
  document.getElementById("notifPage").style.display="none";\
  document.body.style.overflow="";\
}\
function ntAgo(d){\
  var s=Math.floor((Date.now()-new Date(d).getTime())/1000);\
  if(s<60)return s+" giây";\
  if(s<3600)return Math.floor(s/60)+" phút";\
  if(s<86400)return Math.floor(s/3600)+" giờ";\
  return Math.floor(s/86400)+" ngày";\
}\
async function loadNotifPage(){\
  var list=document.getElementById("notifPageList");\
  var token=localStorage.getItem("token");\
  if(!token){list.innerHTML="<div style=\\"text-align:center;padding:60px;color:#999\\"><i class=\\"fas fa-bell\\" style=\\"font-size:48px;color:#ddd;display:block;margin-bottom:12px\\"></i>Đăng nhập để xem thông báo</div>";return;}\
  try{\
    var r=await fetch("/api/notifications.php",{headers:{"Authorization":"Bearer "+token}});\
    var d=await r.json();\
    if(!d.success||!d.data||d.data.length===0){\
      list.innerHTML="<div style=\\"text-align:center;padding:60px;color:#999\\"><div style=\\"font-size:48px;margin-bottom:12px\\">🔔</div>Chưa có thông báo nào</div>";\
      return;\
    }\
    var unread=d.data.filter(function(n){return !n.is_read});\
    var read=d.data.filter(function(n){return n.is_read});\
    var html="";\
    if(unread.length>0){\
      html+="<div style=\\"padding:14px 16px 8px;font-weight:700;font-size:16px;color:#111\\">Mới</div>";\
      html+=unread.map(function(n){return mkNotif(n)}).join("");\
    }\
    if(read.length>0){\
      html+="<div style=\\"padding:14px 16px 8px;font-weight:700;font-size:16px;color:#111\\">Trước đó</div>";\
      html+=read.map(function(n){return mkNotif(n)}).join("");\
    }\
    list.innerHTML=html;\
    updateNotifBadge(d.data);\
  }catch(e){\
    list.innerHTML="<div style=\\"text-align:center;padding:60px;color:#999\\">Lỗi tải thông báo</div>";\
  }\
}\
function mkNotif(n){\
  var icon=n.type==="like"?"❤️":"💬";\
  var iconBg=n.type==="like"?"background:#E74C3C":"background:#3498DB";\
  var label=n.type==="like"?"đã thích bài viết của bạn":"đã bình luận bài viết của bạn";\
  var bg=n.is_read?"background:#fff;":"background:#FFF0EB;";\
  var dot=n.is_read?"":"<div style=\\"width:10px;height:10px;border-radius:50%;background:var(--primary);flex-shrink:0\\"></div>";\
  var av=n.actor_avatar\
    ?"<div style=\\"position:relative;flex-shrink:0\\"><img src=\\""+n.actor_avatar+"\\" style=\\"width:52px;height:52px;border-radius:50%;object-fit:cover\\"><div style=\\"position:absolute;bottom:-2px;right:-2px;width:20px;height:20px;border-radius:50%;"+iconBg+";display:flex;align-items:center;justify-content:center;font-size:10px;border:2px solid #fff\\">"+icon+"</div></div>"\
    :"<div style=\\"position:relative;flex-shrink:0\\"><div style=\\"width:52px;height:52px;border-radius:50%;background:#EE4D2D;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px\\">"+((n.actor_name||"U").charAt(0))+"</div><div style=\\"position:absolute;bottom:-2px;right:-2px;width:20px;height:20px;border-radius:50%;"+iconBg+";display:flex;align-items:center;justify-content:center;font-size:10px;border:2px solid #fff\\">"+icon+"</div></div>";\
  return "<div data-key=\\""+n.notif_key+"\\" data-postid=\\""+n.post_id+"\\" data-read=\\""+(n.is_read?"1":"0")+"\\" style=\\"display:flex;gap:12px;padding:12px 16px;cursor:pointer;align-items:center;"+bg+"\\" onclick=\\"clickNotif(this)\\">" +av+"<div style=\\"flex:1;min-width:0\\"><div style=\\"font-size:14px;line-height:1.4;color:#333\\"><b>"+n.actor_name+"</b> "+label+"</div><div style=\\"font-size:12px;color:var(--primary);margin-top:4px;font-weight:600\\">"+ntAgo(n.created_at)+"</div></div>"+dot+"</div>";\
}\
function clickNotif(el){\
  var key=el.dataset.key;\
  var pid=el.dataset.postid;\
  if(el.dataset.read!=="1"){\
    el.dataset.read="1";\
    el.style.background="#fff";\
    var dot=el.querySelector("div[style*=\\"border-radius:50%;background:var(--primary)\\"]");\
    if(dot)dot.remove();\
    var token=localStorage.getItem("token");\
    fetch("/api/notifications.php",{method:"POST",headers:{"Authorization":"Bearer "+token,"Content-Type":"application/json"},body:JSON.stringify({notif_key:key})});\
    var items=document.querySelectorAll("#notifPageList [data-read=\\"0\\"]");\
    var badge=document.getElementById("tabNotifBadge");\
    if(badge){if(items.length>0){badge.style.display="flex";badge.textContent=items.length>99?"99+":items.length;}else{badge.style.display="none";}}\
  }\
  if(pid){closeNotifPage();setTimeout(function(){location.href="index.html?post="+pid},100);}\
}\
function markAllRead(){\
  var items=document.querySelectorAll("#notifPageList [data-read=\\"0\\"]");\
  var token=localStorage.getItem("token");\
  items.forEach(function(el){\
    el.dataset.read="1";\
    el.style.background="#fff";\
    var dot=el.querySelector("div[style*=\\"border-radius:50%;background:var(--primary)\\"]");\
    if(dot)dot.remove();\
    fetch("/api/notifications.php",{method:"POST",headers:{"Authorization":"Bearer "+token,"Content-Type":"application/json"},body:JSON.stringify({notif_key:el.dataset.key})});\
  });\
  var badge=document.getElementById("tabNotifBadge");\
  if(badge)badge.style.display="none";\
}\
function updateNotifBadge(data){\
  var unread=data.filter(function(n){return !n.is_read}).length;\
  var badge=document.getElementById("tabNotifBadge");\
  if(badge){if(unread>0){badge.style.display="flex";badge.textContent=unread>99?"99+":unread;}else{badge.style.display="none";}}\
}\
</script>' index.html

echo "Done! Check https://shippershop.vn"
