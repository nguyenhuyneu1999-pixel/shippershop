// ShipperShop Messages — Conversation options
var coMuted=false,coPinned=false,coHidden=false,coBffOn=false;

function openChatOpts(){
  if(!curOtherId)return;
  var ov=document.getElementById("coOverlay");
  ov.style.display="block";
  requestAnimationFrame(function(){ov.classList.add("open");});
  // Fill profile
  var av=document.getElementById("chAv");
  var coAvEl=document.getElementById("coAv");
  if(av&&av.src&&av.style.display!=="none"){coAvEl.src=av.src;coAvEl.style.display="block";}
  else{coAvEl.style.display="none";}
  var name=document.getElementById("chName").textContent||"";
  document.getElementById("coName").textContent=name;
  var ship=document.getElementById("chShip").textContent||"";
  var status=document.getElementById("chStatus").textContent||"";
  document.getElementById("coSub").textContent=(status?status+" ":"")+(ship?" · "+ship:"");
  document.getElementById("coCreateGroupLabel").textContent="Tạo nhóm với "+name;
  document.getElementById("coAddGroupLabel").textContent="Thêm "+name+" vào nhóm";
  // Load media
  coLoadMedia();
}

function closeChatOpts(){
  var ov=document.getElementById("coOverlay");
  ov.classList.remove("open");
  setTimeout(function(){ov.style.display="none";},300);
}

function coLoadMedia(){
  var row=document.getElementById("coMediaRow");
  if(!curConvId){row.innerHTML="";return;}
  fetch("/api/messages-api.php?action=media&conversation_id="+curConvId+"&type=image",{credentials:"include"}).then(function(r){return r.json();}).then(function(d){
    if(!d.success||!d.data||!d.data.length){row.innerHTML="<span style='font-size:12px;color:#999;padding:4px'>Chưa có ảnh</span>";return;}
    var h="";
    for(var j=0;j<Math.min(d.data.length,4);j++){h+="<img class='co-media-thumb' src='"+d.data[j].file_url+"' onclick='viewImg(\""+d.data[j].file_url+"\")' loading=\"lazy\">";}
    h+="<div class='co-media-more' onclick='coMedia()'><i class='fas fa-arrow-right'></i></div>";
    row.innerHTML=h;
  }).catch(function(){});
}

function coSearchMsgs(){
  closeChatOpts();
  var q=prompt("Tìm tin nhắn:");
  if(!q||!q.trim())return;
  var msgs=document.querySelectorAll(".msg-bubble");
  var found=false;
  for(var i=msgs.length-1;i>=0;i--){
    if(msgs[i].textContent.toLowerCase().indexOf(q.toLowerCase())!==-1){
      msgs[i].style.background="#FFF3CD";
      msgs[i].scrollIntoView({behavior:"smooth",block:"center"});
      found=true;
      setTimeout(function(el){el.style.background="";},3000,msgs[i]);
      break;
    }
  }
  if(!found){if(typeof toast==="function")toast("Không tìm thấy","warning");}
}

function coGoProfile(){closeChatOpts();if(isGroupChat){openMembersList();return;}if(curOtherId)location.href="user.html?id="+curOtherId;}

function coBgChange(){
  var colors=["#fff","#f0f4f8","#fdf2f8","#f0fdf4","#fefce8","#1a1a2e","#0f0f23"];
  var idx=colors.indexOf(document.getElementById("chMsgs").style.background||"#fff");
  var next=colors[(idx+1)%colors.length];
  document.getElementById("chMsgs").style.background=next;
  closeChatOpts();
}

function coToggleMute(){
  coMuted=!coMuted;
  var icon=document.getElementById("coMuteIcon");
  var label=document.getElementById("coMuteLabel");
  if(coMuted){icon.innerHTML="<i class='fas fa-bell-slash'></i>";label.textContent="Bật thông báo";}
  else{icon.innerHTML="<i class='fas fa-bell'></i>";label.textContent="Tắt thông báo";}
}

function coNickname(){
  var n=prompt("Đặt tên gợi nhớ:",document.getElementById("chName").textContent);
  if(n&&n.trim()){document.getElementById("chName").textContent=n.trim();document.getElementById("coName").textContent=n.trim();}
}

function coToggleBff(el){el.classList.toggle("on");coBffOn=!coBffOn;}
function coTogglePin(el){el.classList.toggle("on");coPinned=!coPinned;}
function coToggleHide(el){el.classList.toggle("on");coHidden=!coHidden;}

function coMedia(){closeChatOpts();openMediaGallery();}
function coCreateGroup(){
  closeChatOpts();
  if(!curOtherId){toast("Lỗi: không tìm thấy người dùng");return;}
  var partnerName=document.getElementById("chName").textContent||"";
  var myName=CU?CU.fullname:"";
  var name=myName+", "+partnerName;
  toast("Đang tạo nhóm...");
  var tk=localStorage.getItem("token");
  var hdrs={"Content-Type":"application/json"};
  if(tk)hdrs["Authorization"]="Bearer "+tk;
  fetch("/api/messages-api.php?action=create_group",{method:"POST",headers:hdrs,credentials:"include",body:JSON.stringify({name:name,member_ids:[curOtherId]})}).then(function(r){return r.json();}).then(function(d){
    if(d.success&&d.data){toast("Đã tạo nhóm chat!");loadConvs();setTimeout(function(){openGroupChat(d.data.conversation_id);},500);}
    else{toast(d.message||"Lỗi tạo nhóm");}
  }).catch(function(e){toast("Lỗi kết nối: "+e.message);});
}
function coAddToGroup(){closeChatOpts();toast("Tính năng đang phát triển");}
function coSharedGroups(){closeChatOpts();toast("Tính năng đang phát triển");}

function coCategory(){
  closeChatOpts();
  if(typeof openAssignCat==="function"&&curConvId){
    ctxConvId=curConvId;ctxConvName=document.getElementById("chName").textContent||"";
    openAssignCat();
  }
}

function coAutoDelete(){
  var opts=["Không tự xóa","1 giờ","24 giờ","7 ngày","30 ngày"];
  var cur=document.getElementById("coAutoDelDesc").textContent;
  var idx=opts.indexOf(cur);
  var next=opts[(idx+1)%opts.length];
  document.getElementById("coAutoDelDesc").textContent=next;
}

function coReport(){if(confirm("Báo cáo người dùng này?")){if(typeof toast==="function")toast("Đã gửi báo cáo. Cảm ơn bạn!","success");closeChatOpts();}}

function coBlock(){
  if(confirm("Chặn người dùng này? Họ sẽ không thể nhắn tin cho bạn.")){
    if(typeof toast==="function")toast("Đã chặn người dùng","success");closeChatOpts();backToList();
  }
}

function coDeleteHistory(){
  if(!confirm("Xóa toàn bộ lịch sử trò chuyện? Hành động này không thể hoàn tác."))return;
  if(!curConvId)return;
  fetch("/api/messages-api.php?action=delete_conversation",{method:"POST",headers:{"Content-Type":"application/json"},credentials:"include",body:JSON.stringify({conversation_id:curConvId})}).then(function(r){return r.json();}).then(function(d){
    closeChatOpts();backToList();
  }).catch(function(){closeChatOpts();backToList();});
}

// ============================================
// GROUP CONVERSATIONS
// ============================================
var isGroupChat=false,groupInfo=null;

function loadGroupConvs(){
  var list=document.getElementById("convList");
  list.innerHTML="<div style='text-align:center;padding:40px;color:#999'><i class='fas fa-spinner fa-spin'></i></div>";
  var tkG=localStorage.getItem("token");var hG={};if(tkG)hG["Authorization"]="Bearer "+tkG;
  fetch("/api/messages-api.php?action=group_conversations",{credentials:"include",headers:hG}).then(function(r){return r.json();}).then(function(d){
    if(!d.success||!d.data||!d.data.length){list.innerHTML="<div style='text-align:center;padding:40px;color:#999'>Chưa có nhóm chat nào<br><button onclick='createGroupPrompt()' style='margin-top:12px;padding:8px 20px;border-radius:20px;border:none;background:#0084ff;color:#fff;font-weight:700;cursor:pointer'>+ Tạo nhóm mới</button></div>";return;}
    var h="";
    for(var i=0;i<d.data.length;i++){
      var g=d.data[i];
      var av=g.avatar?"<img class='cv-av' src='"+g.avatar+"' loading=\"lazy\">":"<div class='cv-av' style='background:#0084ff;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;border-radius:50%;width:52px;height:52px'>"+((g.name||"G")[0])+"</div>";
      var unr=g.unread_count>0?"<div class='cv-unread'></div>":"";
      h+="<div class='conv-item' onclick='openGroupChat("+g.id+")' data-conv-id='"+g.id+"'>"+av+"<div class='cv-info'><div class='cv-name'>"+esc(g.name)+"</div><div class='cv-last'>"+esc(g.last_message||"")+"</div></div><div class='cv-right'><div class='cv-time'>"+ago(g.last_message_at)+"</div><div style='font-size:11px;color:#65676B'>"+g.member_count+" TV</div>"+unr+"</div></div>";
    }
    h+="<div style='text-align:center;padding:16px'><button onclick='createGroupPrompt()' style='padding:8px 20px;border-radius:20px;border:1px solid #ddd;background:#fff;color:#333;font-weight:600;cursor:pointer'>+ Tạo nhóm mới</button></div>";
    list.innerHTML=h;
  }).catch(function(){list.innerHTML="<div style='text-align:center;padding:40px;color:#999'>Lỗi kết nối</div>";});
}

function openGroupChat(gid){
  isGroupChat=true;curConvId=gid;curOtherId=null;
  var bc=document.getElementById("btnCall");var bv=document.getElementById("btnVideo");if(bc)bc.style.display="none";if(bv)bv.style.display="none";
  document.getElementById("listView").classList.add("hidden");
  document.getElementById("chatView").classList.add("active");
      // Load group info
  var tkGI=localStorage.getItem("token");var hGI={};if(tkGI)hGI["Authorization"]="Bearer "+tkGI;
  fetch("/api/messages-api.php?action=group_info&conversation_id="+gid,{credentials:"include",headers:hGI}).then(function(r){return r.json();}).then(function(d){
    if(d.success){
      groupInfo=d.data;
      document.getElementById("chName").textContent=d.data.name;
      document.getElementById("chStatus").textContent=d.data.member_count+" thành viên";
      document.getElementById("chShip").textContent="";
      if(d.data.avatar){document.getElementById("chAv").src=d.data.avatar;document.getElementById("chAv").style.display="block";}
      else{document.getElementById("chAv").style.display="none";}
    }
  });
  loadMsgs();startPoll();
  document.getElementById("chInputWrap").style.display="flex";
  document.body.classList.add('in-chat');
}

function createGroupPrompt(){
  var name=prompt("Tên nhóm:");
  if(!name||!name.trim())return;
  fetch("/api/messages-api.php?action=create_group",{method:"POST",headers:{"Content-Type":"application/json"},credentials:"include",body:JSON.stringify({name:name.trim()})}).then(function(r){return r.json();}).then(function(d){
    if(d.success){openGroupChat(d.data.conversation_id);}
  });
}

// Override sendMsg for group support
function sendMsgGroup(){
  if(!curConvId||!isGroupChat)return;
  var inp=document.getElementById("msgInput");
  var ct=inp.value.trim();if(!ct)return;
  inp.value="";onMsgInput();
  var c=document.getElementById("chMsgs");
  var empty=c.querySelector(".empty");if(empty)empty.remove();
  c.insertAdjacentHTML("beforeend","<div class='msg-row mine'><div><div class=msg-bubble>"+esc(ct)+"</div></div></div>");
  c.scrollTop=c.scrollHeight;
  (function(){var tk=localStorage.getItem("token");var hdrs={"Content-Type":"application/json"};if(tk)hdrs["Authorization"]="Bearer "+tk;return fetch("/api/messages-api.php?action=send",{method:"POST",headers:hdrs,credentials:"include",body:JSON.stringify({group_id:curConvId,content:ct})});})().then(function(r){return r.json();}).then(function(d){
    if(d.success)loadMsgs();
  });
}


// ============================================
// GROUP OPTIONS PANEL (extends existing co-panel)
// ============================================
var _origOpenChatOpts=openChatOpts;
openChatOpts=function(){
  if(isGroupChat){openGroupOpts();return;}
  document.getElementById("coLeaveGroup").style.display="none";
  document.getElementById("coGroupLink").style.display="none";
  // Restore original onclick for 1-1 chat (undone by openGroupOpts)
  document.getElementById("coCreateGroupLabel").parentNode.parentNode.onclick=function(){coCreateGroup();};
  document.getElementById("coAddGroupLabel").parentNode.parentNode.onclick=function(){coAddToGroup();};
  _origOpenChatOpts();
};

function openGroupOpts(){
  if(!curConvId||!groupInfo)return;
  var ov=document.getElementById("coOverlay");
  ov.style.display="block";
  requestAnimationFrame(function(){ov.classList.add("open");});
  var g=groupInfo;
  document.getElementById("coAv").style.display="none";
  document.getElementById("coName").textContent=g.name||"Nhóm";
  document.getElementById("coSub").textContent=(g.member_count||0)+" thành viên";
  document.getElementById("coCreateGroupLabel").textContent="Thêm mô tả nhóm";
  document.getElementById("coAddGroupLabel").textContent="Xem thành viên ("+g.member_count+")";
  // Remap buttons for group context
  document.getElementById("coCreateGroupLabel").parentNode.parentNode.onclick=function(){closeChatOpts();var desc=prompt("Mô tả nhóm:",g.description||"");if(desc!==null){fetch("/api/messages-api.php?action=update_group",{method:"POST",headers:{"Content-Type":"application/json"},credentials:"include",body:JSON.stringify({conversation_id:curConvId,description:desc})}).then(function(){groupInfo.description=desc;});}};
  document.getElementById("coAddGroupLabel").parentNode.parentNode.onclick=function(){closeChatOpts();openMembersList();};
  document.getElementById("coLeaveGroup").style.display="flex";
  document.getElementById("coGroupLink").style.display="block";
  document.getElementById("coLinkUrl").textContent=location.origin+"/messages.html?group="+curConvId;
  coLoadMedia();
}

// ============================================
// MEMBERS LIST OVERLAY
// ============================================
function openMembersList(){
  var old=document.getElementById("membersOv");if(old)old.remove();
  var ov=document.createElement("div");
  ov.id="membersOv";
  ov.style.cssText="position:fixed;inset:0;z-index:1300;background:#fff;overflow-y:auto;";
  ov.innerHTML="<div style='position:sticky;top:0;background:#fff;z-index:1;padding:12px 16px;border-bottom:1px solid #e4e6eb;display:flex;align-items:center;gap:10px'><button onclick='document.getElementById(\"membersOv\").remove()' style='background:none;border:none;font-size:20px;cursor:pointer'><i class='fas fa-arrow-left'></i></button><h3 style='flex:1;font-size:17px;font-weight:700;margin:0'>Thành viên</h3><button style='background:none;border:none;font-size:18px;cursor:pointer;color:#0084ff' onclick='addMemberPrompt()'><i class='fas fa-user-plus'></i></button></div><div id='membersContent' style='padding:8px 0'><div style='text-align:center;padding:40px'><i class='fas fa-spinner fa-spin' style='font-size:20px;color:#999'></i></div></div>";
  document.body.appendChild(ov);
  // Fetch members
  fetch("/api/messages-api.php?action=group_members&conversation_id="+curConvId,{credentials:"include"}).then(function(r){return r.json();}).then(function(d){
    if(!d.success||!d.data.length){document.getElementById("membersContent").innerHTML="<div style='text-align:center;padding:40px;color:#999'>Không có thành viên</div>";return;}
    var h="<div style='padding:4px 16px;font-size:13px;color:#65676B;font-weight:600'>Thành viên ("+d.data.length+")</div>";
    for(var i=0;i<d.data.length;i++){
      var m=d.data[i];
      var av=m.avatar?"<img src='"+esc(m.avatar)+"' style='width:44px;height:44px;border-radius:50%;object-fit:cover' loading=\"lazy\">":"<div style='width:44px;height:44px;border-radius:50%;background:#e4e6eb;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px'>"+esc((m.fullname||"U")[0])+"</div>";
      var role=m.role==="admin"?"<span style='font-size:12px;color:#0084ff'>Trưởng nhóm</span>":"";
      var online=m.is_online?"<span style='display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;margin-right:4px'></span>":"";
      h+="<div style='display:flex;align-items:center;gap:12px;padding:10px 16px;cursor:pointer' onclick='document.getElementById(\"membersOv\").remove();openFromUser("+m.id+")'>"+av+"<div style='flex:1'><div style='font-weight:600;font-size:15px'>"+online+esc(m.fullname)+"</div>"+role+(m.shipping_company?"<div style='font-size:12px;color:#65676B'>"+esc(m.shipping_company)+"</div>":"")+"</div></div>";
    }
    document.getElementById("membersContent").innerHTML=h;
  });
}

function addMemberPrompt(){
  var uid=prompt("Nhập ID người dùng cần thêm:");
  if(!uid)return;
  fetch("/api/messages-api.php?action=add_member",{method:"POST",headers:{"Content-Type":"application/json"},credentials:"include",body:JSON.stringify({conversation_id:curConvId,user_id:parseInt(uid)})}).then(function(r){return r.json();}).then(function(d){
    if(d.success){openMembersList();}
  });
}

// ============================================  
// MEDIA GALLERY OVERLAY
// ============================================
function openMediaGallery(){
  var old=document.getElementById("mediaOv");if(old)old.remove();
  var ov=document.createElement("div");
  ov.id="mediaOv";
  ov.style.cssText="position:fixed;inset:0;z-index:1300;background:#fff;overflow-y:auto;";
  ov.innerHTML="<div style='position:sticky;top:0;background:#fff;z-index:1;padding:12px 16px;border-bottom:1px solid #e4e6eb;display:flex;align-items:center;gap:10px'><button onclick='document.getElementById(\"mediaOv\").remove()' style='background:none;border:none;font-size:20px;cursor:pointer'><i class='fas fa-arrow-left'></i></button><h3 style='flex:1;font-size:17px;font-weight:700;margin:0;color:#0084ff'>Ảnh, file, link</h3></div>"
  +"<div style='display:flex;gap:0;border-bottom:1px solid #e4e6eb'>"
  +"<div class='media-tab active' onclick='loadMediaTab(\"image\",this)' style='flex:1;padding:12px;text-align:center;font-weight:600;font-size:13px;cursor:pointer;border-bottom:2px solid #0084ff;color:#0084ff'>Ảnh</div>"
  +"<div class='media-tab' onclick='loadMediaTab(\"file\",this)' style='flex:1;padding:12px;text-align:center;font-weight:600;font-size:13px;cursor:pointer;color:#65676B'>File</div>"
  +"<div class='media-tab' onclick='loadMediaTab(\"link\",this)' style='flex:1;padding:12px;text-align:center;font-weight:600;font-size:13px;cursor:pointer;color:#65676B'>Link</div>"
  +"</div>"
  +"<div id='mediaContent' style='padding:8px'><div style='text-align:center;padding:40px'><i class='fas fa-spinner fa-spin' style='font-size:20px;color:#999'></i></div></div>";
  document.body.appendChild(ov);
  loadMediaTab("image",ov.querySelector(".media-tab"));
}

function loadMediaTab(type,el){
  // Update tab styles
  var tabs=el.parentNode.querySelectorAll("div");
  for(var i=0;i<tabs.length;i++){tabs[i].style.borderBottom="2px solid transparent";tabs[i].style.color="#65676B";}
  el.style.borderBottom="2px solid #0084ff";el.style.color="#0084ff";
  
  var wrap=document.getElementById("mediaContent");
  wrap.innerHTML="<div style='text-align:center;padding:40px'><i class='fas fa-spinner fa-spin' style='font-size:20px;color:#999'></i></div>";
  
  fetch("/api/messages-api.php?action=media&conversation_id="+curConvId+"&type="+type,{credentials:"include"}).then(function(r){return r.json();}).then(function(d){
    if(!d.success||!d.data.length){wrap.innerHTML="<div style='text-align:center;padding:40px;color:#999'>Chưa có nội dung</div>";return;}
    var h="";
    if(type==="image"){
      h="<div style='display:grid;grid-template-columns:repeat(3,1fr);gap:4px'>";
      for(var i=0;i<d.data.length;i++){
        h+="<img src='"+d.data[i].file_url+"' style='width:100%;aspect-ratio:1;object-fit:cover;border-radius:4px;cursor:pointer' onclick='viewImg(\""+d.data[i].file_url+"\")'>";
      }
      h+="</div>";
    }else if(type==="file"){
      for(var j=0;j<d.data.length;j++){
        var f=d.data[j];
        h+="<div style='display:flex;align-items:center;gap:10px;padding:10px 12px;border-bottom:1px solid #f0f0f0'><i class='fas fa-file-alt' style='font-size:24px;color:#0084ff'></i><div style='flex:1;min-width:0'><div style='font-size:14px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap'>"+esc(f.file_name||"File")+"</div><div style='font-size:12px;color:#65676B'>"+esc(f.sender_name)+" · "+ago(f.created_at)+"</div></div></div>";
      }
    }else{
      for(var k=0;k<d.data.length;k++){
        var l=d.data[k];
        var url=l.content.match(/https?:\/\/[^\s]+/);
        if(url)h+="<div style='padding:10px 12px;border-bottom:1px solid #f0f0f0'><a href='"+url[0]+"' target='_blank' style='color:#0084ff;font-size:14px;word-break:break-all'>"+url[0]+"</a><div style='font-size:12px;color:#65676B;margin-top:2px'>"+esc(l.sender_name)+" · "+ago(l.created_at)+"</div></div>";
      }
    }
    wrap.innerHTML=h||"<div style='text-align:center;padding:40px;color:#999'>Chưa có nội dung</div>";
  });
}

// Remap coMedia to open full gallery
coMedia=function(){closeChatOpts();openMediaGallery();};

// Override backToList to handle groups
var _origBackToList=backToList;
backToList=function(){
  isGroupChat=false;groupInfo=null;
  _origBackToList();
};

// Override sendMsg for groups  
var _origSendMsgFn=sendMsg;
sendMsg=function(){
  try{
    if(isGroupChat){sendMsgGroup();return;}
    _origSendMsgFn();
  }catch(e){console.error("sendMsg error:",e);}
};

function coLeaveGroup(){
  if(!confirm("Rời nhóm? Bạn sẽ không nhận tin nhắn từ nhóm này nữa."))return;
  fetch("/api/messages-api.php?action=leave_group",{method:"POST",headers:{"Content-Type":"application/json"},credentials:"include",body:JSON.stringify({conversation_id:curConvId})}).then(function(r){return r.json();}).then(function(d){
    if(d.success){closeChatOpts();backToList();if(typeof toast==="function")toast("Đã rời nhóm","success");}
  });
}

function coShareLink(){
  var link=document.getElementById("coLinkUrl").textContent;
  if(navigator.share){navigator.share({url:link,title:groupInfo?groupInfo.name:"Nhóm"});}
  else{navigator.clipboard.writeText(link);if(typeof toast==="function")toast("Đã copy link nhóm","success");}
}

// Auto-open group from URL param
(function(){
  var p=new URLSearchParams(location.search);
  var gid=parseInt(p.get("group")||0);
  if(gid){setTimeout(function(){openGroupChat(gid);},500);}
})();
