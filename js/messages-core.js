// ShipperShop Messages — Core functions
function toast(msg){var t=document.getElementById("ssToast");t.textContent=msg;t.style.opacity="1";t.style.transform="translateX(-50%) translateY(0)";clearTimeout(t._tm);t._tm=setTimeout(function(){t.style.opacity="0";t.style.transform="translateX(-50%) translateY(100px)";},2500);}
var CU=JSON.parse(localStorage.getItem("user")||"null");
// Auto-inject Bearer token on ALL fetch calls to /api/
(function(){var of=window.fetch;window.fetch=function(url,opts){opts=opts||{};if(typeof url==='string'&&url.indexOf('/api/')>-1){opts.headers=opts.headers||{};var tk=localStorage.getItem('token');if(tk&&!opts.headers['Authorization']&&!opts.headers['authorization']){opts.headers['Authorization']='Bearer '+tk;}}return of.call(this,url,opts);};})();
if(!CU)location.href="login.html";
var curConvId=null,curOtherId=null,curTab="active",pollTimer=null;
var SC={"GHTK":"#00b14f","J&T":"#d32f2f","GHN":"#ff6600","Viettel Post":"#e21a1a","SPX":"#EE4D2D","Grab":"#00b14f","Be":"#5bc500","Gojek":"#00aa13","Ninja Van":"#c41230"};

init();
function init(){loadConvs();loadOnline();loadPendingCount();
var au=new URLSearchParams(location.search).get("user");
if(au)openFromUser(parseInt(au));}

function switchTab(t){curTab=t;
document.getElementById("tabActive").className="tab"+(t==="active"?" active":"");
document.getElementById("tabPending").className="tab"+(t==="pending"?" active":"");
document.getElementById("onlineBar").style.display="none";
loadConvs();if(t==='active')loadOnline();}

async function loadConvs(){
var list=document.getElementById("convList");
list.innerHTML="<div class=empty><i class='fas fa-spinner fa-spin'></i></div>";
try{var tk=localStorage.getItem("token");var hdrs={};if(tk)hdrs["Authorization"]="Bearer "+tk;
var r=await fetch("/api/messages-api.php?action=conversations&tab="+curTab,{credentials:"include",headers:hdrs});
var d=await r.json();
if(!d.success){if(r.status===401){location.href="login.html";}else{list.innerHTML="<div class=empty><i class='fas fa-exclamation-circle'></i><p>"+esc(d.message||"Lỗi")+"</p></div>";}return;}
if(!d.data||!d.data.length){list.innerHTML="<div class=empty><i class='far fa-comment-dots'></i><p>"+(curTab==="pending"?"Không có tin nhắn đang chờ":"Chưa có tin nhắn")+"</p></div>";return;}
list.innerHTML=d.data.map(function(c){
var av=c.other_avatar?"<img class=cv-av src='"+c.other_avatar+"'onerror='this.className=\"cv-av-ph\";this.innerHTML=\""+(c.other_name||"U").charAt(0)+"\"' loading=\"lazy\">"
:"<div class=cv-av-ph>"+(c.other_name||"U").charAt(0)+"</div>";
var dot=c.other_online?"<div class=cv-dot></div>":"";
var ship=c.other_ship?"<span class=cv-ship style=color:"+(SC[c.other_ship]||"#999")+">"+esc(c.other_ship)+"</span>":"";
var unread=parseInt(c.unread_count)>0;
var status="";
if(!c.other_online&&c.other_last_active){var mn=Math.floor((Date.now()-new Date(c.other_last_active.replace(" ","T")).getTime())/60000);
if(mn<60)status=mn+"p";else if(mn<1440)status=Math.floor(mn/60)+"h";else status=Math.floor(mn/1440)+"d";}
return "<div class='conv-item"+(unread?" unread":"")+"'onclick='openConv("+c.id+","+c.other_id+",\""+esc(c.other_name)+"\",\""+esc(c.other_avatar||"")+"\","+c.other_online+",\""+esc(c.other_ship||"")+"\")'>"+
"<div class=cv-av-wrap>"+av+dot+"</div>"+
"<div class=cv-info><div class=cv-name>"+esc(c.other_name)+" "+ship+"</div><div class=cv-last>"+esc((c.last_message||"").substring(0,40))+"</div></div>"+
"<div class=cv-right><div class=cv-time>"+ago(c.last_message_at)+"</div>"+(unread?"<div class=cv-unread></div>":"")+seen+"</div></div>";
}).join("");}catch(e){list.innerHTML="<div class=empty><p>Lỗi kết nối</p></div>";}}

async function loadOnline(){try{var tkO=localStorage.getItem("token");var hO={};if(tkO)hO["Authorization"]="Bearer "+tkO;var r=await fetch("/api/messages-api.php?action=online_friends",{credentials:"include",headers:hO});
var d=await r.json();var bar=document.getElementById("onlineBar");
if(!d.success||!d.data.length){bar.style.display="none";return;}
bar.innerHTML=d.data.map(function(f){
var av=f.avatar?"<img class=ol-av src='"+f.avatar+"'onerror='this.className=\"ol-av-ph\";this.textContent=\""+(f.fullname||"U").charAt(0)+"\"' loading=\"lazy\">"
:"<div class=ol-av-ph>"+(f.fullname||"U").charAt(0)+"</div>";
return "<div class=ol-item onclick='openFromUser("+f.id+")'>"+av+(f.is_online?"<div class=ol-dot></div>":"")+"<div class=ol-name>"+esc((f.fullname||"").split(" ").pop())+"</div></div>";
}).join("");}catch(e){}}

async function loadPendingCount(){try{var tkP=localStorage.getItem("token");var hP={};if(tkP)hP["Authorization"]="Bearer "+tkP;var r=await fetch("/api/messages-api.php?action=pending_count",{credentials:"include",headers:hP});
var d=await r.json();var b=document.getElementById("pendingBadge");
if(d.success&&d.count>0){b.textContent=d.count;b.style.display="flex";var sb=document.getElementById("stBadge");if(sb){sb.textContent=d.count;sb.style.display="inline-flex";}}else{b.style.display="none";var sb=document.getElementById("stBadge");if(sb)sb.style.display="none";}}catch(e){}}

function openConv(cid,oid,name,avatar,online,ship,lastActive){
curConvId=cid;curOtherId=oid;window.curOtherId=oid;isGroupChat=false;
var bc2=document.getElementById("btnCall");var bv2=document.getElementById("btnVideo");if(bc2)bc2.style.display="";if(bv2)bv2.style.display="";
document.getElementById("listView").classList.add("hidden");
document.getElementById("chatView").classList.add("active");
document.getElementById("chName").textContent=name;
var avEl=document.getElementById("chAv");if(avatar){avEl.src=avatar;avEl.style.display="";}else avEl.style.display="none";
var stEl=document.getElementById("chStatus");
if(parseInt(online)){stEl.innerHTML="<span class=dot-online></span>Đang hoạt động";stEl.className="online";}else if(lastActive){var mn=Math.floor((Date.now()-new Date(lastActive.replace(" ","T")).getTime())/60000);var txt="";if(mn<1)txt="Vừa mới hoạt động";else if(mn<60)txt="Hoạt động "+mn+" phút trước";else if(mn<1440)txt="Hoạt động "+Math.floor(mn/60)+" giờ trước";else txt="Hoạt động "+Math.floor(mn/1440)+" ngày trước";stEl.innerHTML=txt;stEl.className="activity";}else{stEl.textContent="";stEl.className="offline";}
var shEl=document.getElementById("chShip");
if(ship){shEl.textContent=" · "+ship;shEl.style.color=SC[ship]||"#999";}else shEl.textContent="";
document.getElementById("pendingBannerChat").style.display="none";
document.getElementById("pendingBannerChat").innerHTML="";
document.body.classList.add('in-chat');
loadMsgs();startPoll();
setTimeout(function(){document.getElementById("msgInput").focus();},200);}

async function openFromUser(uid){
try{var r=await fetch("/api/messages-api.php?action=user_info&id="+uid,{credentials:"include"});
var d=await r.json();if(d.success&&d.data){
var u=d.data;openConv(u.conversation_id,uid,u.fullname,u.avatar||"",u.is_online,u.shipping_company||"");
if(u.conv_status==="pending")showPendingBanner(u.conversation_id);
}else openConv(null,uid,"Người dùng","",false,"");}catch(e){openConv(null,uid,"Người dùng","",false,"");}}

function showPendingBanner(cid){
var el=document.getElementById("pendingBannerChat");
el.style.display="block";
el.innerHTML="<div class=pending-banner><i class='fas fa-clock'></i><p>Tin nhắn đang chờ. Chấp nhận để trả lời.</p><div class=pending-btns><button class=btn-accept onclick='acceptConv("+cid+")'>Chấp nhận</button><button class=btn-delete onclick='this.parentElement.parentElement.parentElement.style.display=\"none\"'>Xoá</button></div></div>";}

async function acceptConv(cid){
try{await fetch("/api/messages-api.php?action=accept",{method:"POST",headers:{"Content-Type":"application/json"},credentials:"include",body:JSON.stringify({conversation_id:cid})});
document.getElementById("pendingBannerChat").style.display="none";}catch(e){}}

async function loadMsgs(){var c=document.getElementById("chMsgs");
if(!curConvId){c.innerHTML="<div class=empty style=padding-top:40vh><i class='far fa-hand-peace' style=font-size:48px;color:#ddd></i><p style=margin-top:8px>Gửi lời chào!</p></div>";return;}
try{var tk2=localStorage.getItem("token");var mh={};if(tk2)mh["Authorization"]="Bearer "+tk2;
var r=await fetch("/api/messages-api.php?action=messages&conversation_id="+curConvId,{credentials:"include",headers:mh});
var d=await r.json();if(!d.success){c.innerHTML="<div class=empty><p>Lỗi</p></div>";return;}
if(!d.data.length){c.innerHTML="<div class=empty style=padding-top:40vh><i class='far fa-hand-peace' style=font-size:48px;color:#ddd></i><p>Gửi lời chào!</p></div>";return;}
var html="",lastDate="";
d.data.forEach(function(m){
var dt=new Date(m.created_at.replace(" ","T"));
var dateStr=dt.toLocaleDateString("vi-VN",{day:"numeric",month:"short"});
if(dateStr!==lastDate){html+="<div class=msg-date>"+dateStr+" "+dt.toLocaleTimeString("vi-VN",{hour:"2-digit",minute:"2-digit"})+"</div>";lastDate=dateStr;}
var mine=parseInt(m.sender_id)===parseInt(CU.id);
var av="";
if(!mine){av=m.sender_avatar?"<img class=msg-av src='"+m.sender_avatar+"'onerror='this.className=\"msg-av-ph\";this.textContent=\""+(m.sender_name||"U").charAt(0)+"\"' loading=\"lazy\">":"<div class=msg-av-ph>"+(m.sender_name||"U").charAt(0)+"</div>";}
var senderLabel="";if(!mine&&isGroupChat){senderLabel="<div style='font-size:11px;font-weight:700;color:#65676B;margin-bottom:2px'>"+esc(m.sender_name||"")+"</div>";}
var bubble="";if(m.type==="image"&&m.file_url){bubble="<img class=msg-img src='"+m.file_url+"' onclick='viewImg(this.src)' loading=lazy>";}else if(m.type==="video"&&m.file_url){bubble="<video class=msg-img controls playsinline preload=metadata style='max-width:240px;border-radius:12px'><source src='"+m.file_url+"'>Video</video>";}else if(m.type==="file"&&m.file_url){bubble="<a class=msg-file href='"+m.file_url+"' target=_blank download><i class='fas fa-file-alt'></i><span>"+esc(m.file_name||m.content||"File")+"</span></a>";}else if(m.type==="location"&&m.file_url){bubble="<a class=msg-loc href='"+m.file_url+"' target=_blank><span>📍 Xem vị trí trên bản đồ</span></a>";}else{bubble="<div class=msg-bubble>"+esc(m.content)+"</div>";}var seen=mine&&m.is_read?'<div style="font-size:10px;color:#7C3AED;text-align:right;margin-top:1px">Đã xem</div>':'';
    html+="<div class='msg-row "+(mine?"mine":"other")+"'>"+av+"<div>"+senderLabel+bubble+"</div></div>";});
c.innerHTML=html;c.scrollTop=c.scrollHeight;}catch(e){c.innerHTML="<div class=empty><p>Lỗi tải</p></div>";}}

async function sendMsg(){var inp=document.getElementById("msgInput");var ct=inp.value.trim();if(!ct)return;
inp.value="";var c=document.getElementById("chMsgs");
var empty=c.querySelector(".empty");if(empty)empty.remove();
c.insertAdjacentHTML("beforeend","<div class='msg-row mine'><div class=msg-bubble>"+esc(ct)+"</div></div>");
c.scrollTop=c.scrollHeight;
try{var tk=localStorage.getItem("token");var hdrs={"Content-Type":"application/json"};if(tk)hdrs["Authorization"]="Bearer "+tk;
var r=await fetch("/api/messages-api.php?action=send",{method:"POST",headers:hdrs,credentials:"include",body:JSON.stringify({to_user_id:curOtherId,content:ct})});
var d=await r.json();if(d.success){if(!curConvId){curConvId=d.data.conversation_id;}startPoll();loadMsgs();}else{toast(d.message||"Không gửi được","error");}}catch(e){toast("Lỗi kết nối","error");}}

function startPoll(){if(pollTimer)clearInterval(pollTimer);
if(curConvId)pollTimer=setInterval(function(){loadMsgs();},5000);}

function backToList(){document.getElementById("listView").classList.remove("hidden");
document.getElementById("chatView").classList.remove("active");
document.body.classList.remove("in-chat");
curConvId=null;curOtherId=null;if(pollTimer)clearInterval(pollTimer);loadConvs();loadPendingCount();}

function esc(t){if(!t)return"";return String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}
function ago(dt){if(!dt)return"";var s=Math.floor((new Date()-new Date(dt.replace(" ","T")))/1000);if(s<60)return"Vừa xong";if(s<3600)return Math.floor(s/60)+"p";if(s<86400)return Math.floor(s/3600)+"h";if(s<604800)return Math.floor(s/86400)+"d";return new Date(dt).toLocaleDateString("vi-VN");}

function viewImg(src){
    if(typeof openLb==='function'){openLb(src,0,[src]);}
    else{window.open(src,'_blank');}
}

// Typing indicator
var _typingTimer=null;
function sendTyping(convId){
    if(_typingTimer)return;
    _typingTimer=setTimeout(function(){_typingTimer=null;},3000);
    var token=localStorage.getItem('token');
    if(!token)return;
    fetch('/api/messages-api.php?action=typing',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({conversation_id:convId})}).catch(function(){});
}
function showTyping(name){
    var el=document.getElementById('typingIndicator');
    if(!el){
        el=document.createElement('div');
        el.id='typingIndicator';
        el.style.cssText='padding:4px 16px;font-size:12px;color:#65676B;font-style:italic;display:none';
        var msgList=document.getElementById('msgList')||document.querySelector('.msg-list');
        if(msgList)msgList.parentNode.insertBefore(el,msgList.nextSibling);
    }
    el.textContent=name+' đang nhập...';
    el.style.display='block';
    clearTimeout(el._hide);
    el._hide=setTimeout(function(){el.style.display='none';},4000);
}

// Voice note (placeholder — needs MediaRecorder support check)
function startVoiceNote(convId){
  if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){
    toast('Trình duyệt không hỗ trợ ghi âm');return;
  }
  toast('Đang phát triển tính năng ghi âm...','info');
}

// Search conversations by name
function searchConvs(query){
  var items=document.querySelectorAll('.conv-item');
  var q=query.toLowerCase();
  items.forEach(function(item){
    var name=item.querySelector('.conv-name');
    if(name){
      var show=!q||name.textContent.toLowerCase().indexOf(q)>-1;
      item.style.display=show?'':'none';
    }
  });
}
