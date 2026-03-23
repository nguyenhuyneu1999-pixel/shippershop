// ShipperShop user.html

var CU=JSON.parse(localStorage.getItem('user')||'null');var targetId=parseInt(new URLSearchParams(location.search).get('id'))||(CU?CU.id:0);var userData=null;
var shipColors={'GHTK':'#00b14f','J&T':'#d32f2f','GHN':'#ff6600','SPX':'#7C3AED','Grab':'#00b14f','Be':'#5bc500','Gojek':'#00aa13','Ninja Van':'#c41230','Viettel Post':'#e21a1a','Lalamove':'#f5a623'};
async function loadProfile(){if(!targetId){location.href='login.html';return;}try{var r=await fetch('/api/user-page.php?id='+targetId,{credentials:'include'});var d=await r.json();if(!d.success){document.getElementById('tabContent').innerHTML='<div class="empty"><i class="fas fa-user-slash"></i><p>Không tìm thấy</p></div>';return;}userData=d.data;renderProfile();loadTab('posts');
    if(userData.is_self)loadStreak(userData.id);
    // Dynamic SEO
    var _u=userData;
    document.title=_u.fullname+' - ShipperShop';
    var _sm=function(p,v){var m=document.querySelector('meta[property="'+p+'"]');if(m)m.content=v;else{m=document.createElement('meta');m.setAttribute('property',p);m.content=v;document.head.appendChild(m);}};
    _sm('og:title',_u.fullname+' - ShipperShop');
    _sm('og:description',(_u.shipping_company||'Shipper')+' · '+(_u.follower_count||0)+' nguoi theo doi');
    if(_u.avatar)_sm('og:image','https://shippershop.vn'+_u.avatar);}catch(e){document.getElementById('tabContent').innerHTML='<div class="empty"><p>Lỗi</p></div>';}}
function renderProfile(){var u=userData;document.title=u.fullname+' - ShipperShop';document.getElementById('navTitle').textContent=u.fullname;document.getElementById('pName').innerHTML=esc(u.fullname)+(u.sub_badge?'<span style="font-size:11px;padding:2px 8px;border-radius:4px;background:'+(u.sub_badge_color||'#7C3AED')+';color:#fff;margin-left:6px;font-weight:600;vertical-align:middle">'+esc(u.sub_badge)+'</span>':'')+(u.is_online?'<span class="online-dot"></span>':'');document.getElementById('pUsername').textContent='u/'+u.username;document.getElementById('pBio').textContent=u.bio||'';document.getElementById('sKarma').textContent=fN(u.total_success||0);document.getElementById('sPosts').textContent=fN(u.post_count);document.getElementById('sAge').textContent=u.account_age_days;document.getElementById('sFollowers').textContent=fN(u.follower_count);
  // Stats grid
  var sg=document.getElementById('statsGrid');
  if(sg){sg.innerHTML='<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;text-align:center;padding:12px 0">'
    +'<div><div style="font-size:18px;font-weight:700;color:#7C3AED">'+fN(u.post_count)+'</div><div style="font-size:11px;color:#999">Bài viết</div></div>'
    +'<div><div style="font-size:18px;font-weight:700;color:#00b14f">'+fN(u.total_success)+'</div><div style="font-size:11px;color:#999">Thành công</div></div>'
    +'<div><div style="font-size:18px;font-weight:700;color:#1877F2">'+fN(u.follower_count)+'</div><div style="font-size:11px;color:#999">Theo dõi</div></div>'
    +'<div><div style="font-size:18px;font-weight:700;color:#F59E0B">'+fN(u.account_age_days||0)+'</div><div style="font-size:11px;color:#999">Ngày</div></div>'+'<div><div style="font-size:18px;font-weight:700;color:#EE4D2D">'+fN(u.engagement_score||0)+'</div><div style="font-size:11px;color:#999">'+esc(u.engagement_level||"")+ '</div></div>'
    +'</div>';}
  // Badges
  // Achievements link
  var achLink=document.getElementById('pAchLink');
  if(achLink)achLink.innerHTML='<a href="achievements.html?id='+u.id+'" style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#f5f3ff;border-radius:8px;color:#7C3AED;font-size:12px;font-weight:600;text-decoration:none;margin:4px 0"><i class="fas fa-trophy"></i> Xem thành tựu</a>';
  if(u.badges&&u.badges.length){var bHtml='';u.badges.forEach(function(b){bHtml+='<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;background:#f5f3ff;border-radius:12px;font-size:11px;color:#7C3AED;font-weight:600;margin:2px">'+b.badge_icon+' '+b.badge_name+'</span>';});var bEl=document.getElementById('pBadges');if(bEl)bEl.innerHTML=bHtml;}
if(u.cover_image)document.getElementById('cover').style.backgroundImage='url('+u.cover_image+')';
if(u.is_self){document.getElementById('coverEditBtn').style.display='flex';document.getElementById('avEditBtn').style.display='flex';}
if(u.avatar)document.getElementById('avatar').outerHTML='<img class="profile-av" id="avatar" src="'+u.avatar+'" onerror="this.outerHTML=\'<div class=profile-av loading=lazy>'+((u.fullname||'?').charAt(0))+'</div>\'">';
if(u.shipping_company){var c=shipColors[u.shipping_company]||'#999';document.getElementById('pShipBadge').innerHTML='<div class="ship-tag" style="background:'+c+'15;color:'+c+'"><i class="fas fa-truck"></i> '+esc(u.shipping_company)+'</div>';}
var info='';if(u.address)info+='<div class="info-row"><i class="fas fa-map-marker-alt"></i> '+esc(u.address)+'</div>';info+='<div class="info-row"><i class="fas fa-cake-candles"></i> Tham gia '+new Date(u.created_at).toLocaleDateString('vi-VN')+'</div>';document.getElementById('pInfo').innerHTML=info;
var act='';if(u.is_self){act='<button class="p-btn secondary" onclick="openEdit()"><i class="fas fa-pen"></i> Sửa hồ sơ</button>';}else{act='<button class="p-btn '+(u.is_following?'secondary':'primary')+'" onclick="toggleFollow()"><i class="fas fa-'+(u.is_following?'check':'user-plus')+'"></i> '+(u.is_following?'Đang theo dõi':'Theo dõi')+'</button>';act+='<button class="p-btn secondary" onclick="location.href=\'messages.html?user='+targetId+'\'"><i class="fas fa-comment-dots"></i> Nhắn tin</button>';}document.getElementById('pActions').innerHTML=act;}
function setTab(tab,el){document.querySelectorAll('.tab').forEach(function(t){t.classList.remove('active');});el.classList.add('active');loadTab(tab);}
async function loadTab(tab){var c=document.getElementById('tabContent');c.innerHTML='<div class="empty"><i class="fas fa-spinner spin"></i></div>';
if(tab==='posts'){try{var r=await fetch('/api/user-page.php?action=posts&id='+targetId,{credentials:'include'});var d=await r.json();if(d.success&&flat.length){c.innerHTML=d.data.map(function(p){return mkPost(p);}).join('');}else c.innerHTML='<div class="empty"><i class="fas fa-file-lines"></i><p>Chưa có bài đăng</p></div>';}catch(e){c.innerHTML='<div class="empty"><p>Lỗi</p></div>';}}
if(tab==='comments'){try{var r=await fetch('/api/user-page.php?action=comments&id='+targetId,{credentials:'include'});var d=await r.json();if(d.success&&d.data.length){c.innerHTML=d.data.map(function(cm){return '<div class="comment-card"><div class="cmt-ref" onclick="location.href=\'post-detail.html?id='+cm.post_id+'\'"><i class="fas fa-reply"></i> '+esc((cm.post_content||'').substring(0,60))+'...</div><div class="post-content" style="font-size:13px">'+esc(cm.content)+'</div><div class="post-stats" style="font-size:12px"><span>'+ago(cm.created_at)+'</span></div></div>';}).join('');}else c.innerHTML='<div class="empty"><i class="far fa-comment"></i><p>Chưa có ghi chú</p></div>';}catch(e){c.innerHTML='<div class="empty"><p>Lỗi</p></div>';}}
if(tab==='about'){var u=userData;var html='<div class="about-section"><h3>Giới thiệu</h3>';html+='<div class="about-item"><i class="fas fa-user"></i><div><b>'+esc(u.fullname)+'</b></div></div>';if(u.username)html+='<div class="about-item"><i class="fas fa-at"></i><div>u/'+esc(u.username)+'</div></div>';if(u.shipping_company)html+='<div class="about-item"><i class="fas fa-truck"></i><div>'+esc(u.shipping_company)+'</div></div>';if(u.address)html+='<div class="about-item"><i class="fas fa-map-marker-alt"></i><div>'+esc(u.address)+'</div></div>';if(u.bio)html+='<div class="about-item"><i class="fas fa-quote-left"></i><div>'+esc(u.bio)+'</div></div>';html+='<div class="about-item"><i class="fas fa-calendar"></i><div>Tham gia '+new Date(u.created_at).toLocaleDateString('vi-VN')+'</div></div>';html+='<div class="about-item"><i class="fas fa-check-circle"></i><div><b>'+fN(u.total_success||0)+'</b> đơn giao thành công</div></div>';html+='<div class="about-item"><i class="fas fa-users"></i><div><b>'+fN(u.follower_count)+'</b> theo dõi · <b>'+fN(u.following_count)+'</b> đang theo dõi</div></div></div>';c.innerHTML=html;}}
async function toggleFollow(){if(!CU){toast('Đăng nhập!');return;}try{var r=await fetch('/api/social.php?action=follow',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({user_id:targetId})});var d=await r.json();if(d.success){userData.is_following=!userData.is_following;renderProfile();}}catch(e){toast('Lỗi');}}
function openEdit(){document.getElementById('eName').value=userData.fullname||'';document.getElementById('eBio').value=userData.bio||'';document.getElementById('editModal').classList.add('open');}
function closeEdit(){document.getElementById('editModal').classList.remove('open');}
async function saveProfile(){try{var r=await fetch('/api/user-page.php?action=update_profile',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({display_name:document.getElementById('eName').value.trim(),bio:document.getElementById('eBio').value.trim()})});var d=await r.json();if(d.success){toast('Đã cập nhật!');closeEdit();loadProfile();}else toast(d.message||'Lỗi');}catch(e){toast('Lỗi');}}
function esc(t){if(!t)return'';return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function fN(n){n=parseInt(n)||0;if(n>=1000)return(n/1000).toFixed(1)+'K';return n.toString();}
function ago(dt){if(!dt)return'';var s=Math.floor((new Date()-new Date(dt.replace(' ','T')))/1000);if(s<60)return s+'s';if(s<3600)return Math.floor(s/60)+'m';if(s<86400)return Math.floor(s/3600)+'h';if(s<604800)return Math.floor(s/86400)+'d';return new Date(dt).toLocaleDateString('vi-VN');}
async function uploadImg(type){
  var inp=document.getElementById(type==='avatar'?'avInput':'coverInput');
  var file=inp.files[0];if(!file)return;
  if(file.size>5*1024*1024){toast('Ảnh tối đa 5MB');inp.value='';return;}
  var action=type==='avatar'?'upload_avatar':'upload_cover';
  var fd=new FormData();fd.append('image',file);
  toast('Đang tải lên...');
  try{
    var r=await fetch('/api/user-page.php?action='+action,{method:'POST',credentials:'include',body:fd});
    var d=await r.json();
    if(d.success){
      if(type==='avatar'){
        document.getElementById('avatar').outerHTML='<img class="profile-av" id="avatar" src="'+d.data.url+'" loading=lazy>';
        userData.avatar=d.data.url;
        var cu=JSON.parse(localStorage.getItem('user')||'{}');cu.avatar=d.data.url;localStorage.setItem('user',JSON.stringify(cu));
      }else{
        document.getElementById('cover').style.backgroundImage='url('+d.data.url+')';
        userData.cover_image=d.data.url;
      }
      toast('Cập nhật thành công!');
    }else{toast(d.message||'Lỗi');}
  }catch(e){toast('Lỗi kết nối');}
  inp.value='';
}
function toast(msg){var t=document.getElementById('toast');t.textContent=msg;t.className='toast show';setTimeout(function(){t.classList.remove('show');},2500);}
loadProfile();
window.addEventListener('scroll',function(){var nav=document.getElementById('backNav');if(window.scrollY>160)nav.classList.add('scrolled');else nav.classList.remove('scrolled');});


var sheetPostId=0,sheetReplyTo=null;var SC2={"GHTK":"#00b14f","J&T":"#d32f2f","GHN":"#ff6600","Viettel Post":"#e21a1a","SPX":"#7C3AED","Grab":"#00b14f","Be":"#5bc500","Gojek":"#00aa13","Ninja Van":"#c41230","Lalamove":"#f5a623"};document.addEventListener("DOMContentLoaded",function(){var ta=document.getElementById("cmtModalText");if(ta)ta.addEventListener("input",function(){this.style.height="auto";this.style.height=this.scrollHeight+"px";document.getElementById("cmtModalSend").disabled=!this.value.trim();});});
function openPostSheet(pid){sheetPostId=pid;sheetReplyTo=null;var ov=document.getElementById("postSheetOverlay");ov.classList.add("open");document.body.style.overflow="hidden";if(CU){document.getElementById("sheetCmtBar").style.display="flex";var av=document.getElementById("sheetUserAv");if(CU.avatar&&av.tagName!=="IMG")av.outerHTML="<img src=\""+CU.avatar+"\" style=\"width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0\" id=\"sheetUserAv\" loading=lazy>";else if(!CU.avatar&&av.tagName!=="IMG")av.textContent=(CU.fullname||"U")[0];}loadSheetPost(pid);}
function closePostSheet(){var ov=document.getElementById("postSheetOverlay");var s=ov.querySelector(".post-sheet");s.style.transform="translateY(100%)";setTimeout(function(){ov.classList.remove("open");s.style.transform="";document.body.style.overflow="";},300);}
async function loadSheetPost(pid){var body=document.getElementById("sheetBody");body.innerHTML="<div style=\"text-align:center;padding:40px;color:#999\"><i class=\"fas fa-spinner spin\" style=\"font-size:24px\"></i></div>";try{var r=await fetch("/api/posts.php?id="+pid);var d=await r.json();if(!d.success||!d.data){body.innerHTML="<div style=\"text-align:center;padding:40px;color:#999\">Khong tim thay</div>";return;}var p=d.data;var av=p.user_avatar?"<img src=\""+p.user_avatar+"\" style=\"width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0\" loading=lazy>":"<div style=\"width:40px;height:40px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0\">"+esc((p.user_name||"U")[0])+"</div>";var sh=p.shipping_company?"<span style=\"font-size:11px;font-weight:700;color:"+(SC2[p.shipping_company]||"#999")+"\">"+esc(p.shipping_company)+"</span>":"";var imgs="";if(p.images){try{var a=JSON.parse(p.images);if(a&&a.length){imgs="<div>";a.forEach(function(s){imgs+="<img src=\""+s+"\" style=\"width:100%;max-height:500px;object-fit:cover\" onerror=\"this.remove()\">";});imgs+="</div>";}}catch(x){}}var vid="";if(p.video_url&&p.video_url.indexOf("/uploads/")>-1)vid="<video controls playsinline preload=metadata style=\"width:100%;max-height:500px\"><source src=\""+p.video_url+"\" type=video/mp4></video>";var lk=p.user_liked;var h="<div style=\"display:flex;align-items:center;gap:10px;padding:14px 16px 0\">"+av+"<div style=\"flex:1\"><div style=\"font-weight:700;font-size:15px\"><a href=\"user.html?id="+p.user_id+"\" style=\"text-decoration:none;color:inherit\">"+esc(p.user_name||"An danh")+"</a></div><div style=\"font-size:12px;color:#65676B;display:flex;align-items:center;gap:4px\">"+sh+"<span>\u00b7</span><span>"+ago(p.created_at)+"</span></div></div></div>";h+="<div class=\"sheet-post-content\">"+esc(p.content||"")+"</div>"+imgs+vid;h+="<div class=\"sheet-post-stats\"><span>"+(p.likes_count||0)+" \u0111\u01a1n giao th\u00e0nh c\u00f4ng</span><span>"+(p.comments_count||0)+" ghi ch\u00fa</span><span>"+(p.shares_count||0)+" \u0111\u01a1n chuy\u1ec3n ti\u1ebfp</span></div>";h+="<div class=\"post-actions-3\" style=\"border-top:1px solid #e4e6eb;border-bottom:1px solid #e4e6eb\"><button class=\"pa3-btn"+(lk?" pa3-active":"")+"\" id=\"sheetLkBtn\" onclick=\"togSheetLike()\">Th\u00e0nh c\u00f4ng</button><button class=\"pa3-btn\" onclick=\"openCmtModal()\">Ghi ch\u00fa</button><button class=\"pa3-btn\" onclick=\"shrSheet()\">Chuy\u1ec3n ti\u1ebfp</button></div>";h+="<div style=\"padding:12px 16px;font-size:14px;font-weight:700;color:#65676B\">Ph\u00f9 h\u1ee3p nh\u1ea5t <i class=\"fas fa-chevron-down\" style=\"font-size:12px\"></i></div>";h+="<div id=\"sheetCmts\"><div style=\"text-align:center;padding:20px;color:#999\"><i class=\"fas fa-spinner spin\"></i></div></div>";body.innerHTML=h;loadSheetCmts(pid);}catch(e){body.innerHTML="<div style=\"text-align:center;padding:40px;color:#999\">Loi ket noi</div>";}}
async function loadSheetCmts(pid){var wrap=document.getElementById("sheetCmts");if(!wrap)return;try{var r=await fetch("/api/posts.php?action=comments&post_id="+pid);var d=await r.json();if(!d.success||!d.data||!d.data.length){wrap.innerHTML="<div style=\"text-align:center;padding:20px;font-size:13px;color:#999\">Chua co binh luan</div>";return;}var _raw=d.data;var flat=Array.isArray(_raw)?_raw:(_raw&&_raw.comments?_raw.comments:[]),map={},list=[];flat.forEach(function(c){map[c.id]=c;});function addToList(cc,depth,pn){list.push({c:cc,depth:Math.min(depth,1),replyTo:depth>0?pn:null});flat.filter(function(x){return x.parent_id===cc.id;}).forEach(function(ch){addToList(ch,depth+1,cc.user_name);});}flat.filter(function(c){return !c.parent_id||c.parent_id<=0||!map[c.parent_id];}).forEach(function(c){addToList(c,0,null);});wrap.innerHTML=list.map(function(item){return mkSC(item.c,item.depth,item.replyTo);}).join("");}catch(x){wrap.innerHTML="";}}
function mkSC(c,dp,replyTo){var lk=c.user_vote==="up"||c.user_liked;var n=c.likes_count||0;var sz=dp>0?22:28;var indent=dp>0?"padding:3px 6px 3px 20px;border-left:2px solid #e4e6eb;margin-left:14px":"padding:3px 6px";var av=c.user_avatar?"<img src=\""+esc(c.user_avatar)+"\" style=\"width:"+sz+"px;height:"+sz+"px;border-radius:50%;object-fit:cover;flex-shrink:0\" loading=lazy>":"<div style=\"width:"+sz+"px;height:"+sz+"px;border-radius:50%;background:#e4e6eb;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0\">"+esc((c.user_name||"U")[0])+"</div>";var rplTag=replyTo?"<span style=\"color:#7C3AED;font-size:11px\">\u21a9 "+esc(replyTo)+"</span> ":"";var sn=esc(c.user_name||"");return "<div style=\"display:flex;gap:6px;"+indent+"\">"+av+"<div style=\"flex:1;min-width:0\"><div class=\"cmt-bubble\"><a href=\"user.html?id="+c.user_id+"\" class=\"cmt-author\" style=\"text-decoration:none;color:inherit\">"+esc(c.user_name||"\u1ea8n danh")+"</a><div class=\"cmt-text\" style=\"word-break:break-word\">"+rplTag+esc(c.content)+"</div></div><div class=\"cmt-meta\"><span>"+ago(c.created_at)+"</span><a class=\""+(lk?"liked":"")+"\" onclick=\"lkSC("+c.id+",this)\">Th\u00e0nh c\u00f4ng"+(n>0?" \u00b7 "+n:"")+"</a><a onclick=\"setSheetReply("+c.id+")\">Ghi ch\u00fa</a></div></div></div>";}
function openCmtModal(){if(!CU){toast("Dang nhap de binh luan!","warning");return;}document.getElementById("cmtModalOverlay").classList.add("open");setTimeout(function(){document.getElementById("cmtModalText").focus();},200);}
function closeCmtModal(){document.getElementById("cmtModalOverlay").classList.remove("open");document.getElementById("cmtModalText").value="";document.getElementById("cmtModalSend").disabled=true;cancelSheetReply();}
function setSheetReply(id,name){sheetReplyTo=id;document.getElementById("cmtModalReply").classList.add("show");document.getElementById("cmtReplyName").textContent=name;openCmtModal();}
function cancelSheetReply(){sheetReplyTo=null;document.getElementById("cmtModalReply").classList.remove("show");}
async function sendSheetCmt(){if(!CU||!sheetPostId)return;var ta=document.getElementById("cmtModalText");var ct=ta.value.trim();if(!ct)return;var btn=document.getElementById("cmtModalSend");btn.disabled=true;btn.innerHTML="<i class=\"fas fa-spinner fa-spin\"></i>";var b={post_id:sheetPostId,content:ct};if(sheetReplyTo)b.parent_id=sheetReplyTo;try{var r=await fetch("/api/posts.php?action=comment",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(b)});var d=await r.json();if(d.success){closeCmtModal();loadSheetCmts(sheetPostId);}else{toast(d.message||"Loi","error");btn.disabled=false;}}catch(x){toast("Loi ket noi","error");btn.disabled=false;}btn.innerHTML="<i class=\"fas fa-paper-plane\" style=\"margin-right:4px\"></i> G\u1eedi";}
async function togSheetLike(){if(!CU){toast("Dang nhap!","warning");return;}try{var r=await fetch("/api/posts.php?action=vote",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({post_id:sheetPostId,vote_type:"up"})});var d=await r.json();if(d.success){var btn=document.getElementById("sheetLkBtn");var lk=d.data.user_vote==="up";btn.className="pa3-btn"+(lk?" pa3-active":"");btn.innerHTML="Th\u00e0nh c\u00f4ng";}}catch(x){}}
async function lkSC(cid,btn){if(!CU){toast("Dang nhap!","warning");return;}try{var r=await fetch("/api/posts.php?action=vote_comment",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({comment_id:cid,vote_type:"up"})});var d=await r.json();if(d.success){var lk=d.data.user_vote==="up";btn.className=lk?"liked":"";btn.textContent="Th\u00e0nh c\u00f4ng"+(d.data.score>0?" \u00b7 "+d.data.score:"");}}catch(x){}}
function shrSheet(){var u=location.origin+"/post-detail.html?id="+sheetPostId;if(navigator.share)navigator.share({url:u});else{navigator.clipboard.writeText(u);toast("Da copy link!","success");}}

function editBio(){
  if(!userData||!userData.is_self)return;
  var bioEl=document.getElementById('pBio');
  if(!bioEl)return;
  var current=userData.bio||'';
  bioEl.innerHTML='<textarea id="bioEdit" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px;resize:vertical;min-height:60px;box-sizing:border-box">'+current+'</textarea><div style="display:flex;gap:6px;justify-content:flex-end;margin-top:6px"><button onclick="cancelBioEdit()" style="padding:4px 12px;border:1px solid #ddd;border-radius:6px;background:#fff;font-size:12px;cursor:pointer">Hủy</button><button onclick="saveBio()" style="padding:4px 12px;border:none;border-radius:6px;background:#7C3AED;color:#fff;font-size:12px;cursor:pointer;font-weight:600">Lưu</button></div>';
  document.getElementById('bioEdit').focus();
}
function cancelBioEdit(){
  var bioEl=document.getElementById('pBio');
  if(bioEl)bioEl.innerHTML=esc(userData.bio||'Chưa có tiểu sử');
}
function saveBio(){
  var newBio=document.getElementById('bioEdit').value.trim();
  var token=localStorage.getItem('token');
  fetch('/api/user-page.php',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({action:'update_profile',bio:newBio})})
    .then(function(r){return r.json()})
    .then(function(d){
      if(d.success){userData.bio=newBio;cancelBioEdit();toast('Đã cập nhật!');}
      else toast(d.message||'Lỗi','error');
    });
}

// Follow button animation
function animateFollowBtn(btn, isFollowing){
  btn.style.transform='scale(0.9)';
  setTimeout(function(){
    btn.style.transform='scale(1.1)';
    setTimeout(function(){btn.style.transform='scale(1)';},150);
  },100);
  btn.style.transition='transform .15s ease';
}

// Tab memory
function rememberTab(tab){sessionStorage.setItem('ss_user_tab_'+userData.id,tab);}
function getLastTab(){return sessionStorage.getItem('ss_user_tab_'+(userData||{}).id)||'posts';}

function formatJoinDate(dateStr){
  if(!dateStr)return '';
  var d=new Date(dateStr);
  var months=['Th01','Th02','Th03','Th04','Th05','Th06','Th07','Th08','Th09','Th10','Th11','Th12'];
  return 'Tham gia ' + months[d.getMonth()] + ' ' + d.getFullYear();
}

function shareProfileQR(userId, fullname){
  var url='https://shippershop.vn/user.html?id='+userId;
  var qrUrl='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='+encodeURIComponent(url);
  var ov=document.createElement('div');
  ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
  ov.innerHTML='<div style="background:#fff;border-radius:16px;padding:24px;max-width:300px;width:90%;text-align:center"><div style="font-size:16px;font-weight:700;margin-bottom:12px">'+esc(fullname)+'</div><img src="'+qrUrl+'" style="width:200px;height:200px;margin:8px auto;display:block;border-radius:8px"><div style="font-size:12px;color:#999;margin-top:8px">Quét mã QR để xem trang cá nhân</div><button onclick="this.closest(\'[style]\').remove()" style="margin-top:12px;padding:8px 20px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer">Đóng</button></div>';
  ov.onclick=function(e){if(e.target===ov)ov.remove();};
  document.body.appendChild(ov);
}

// Check-in streak display
function loadStreak(userId){
  fetch('/api/checkin.php?action=status')
    .then(function(r){return r.json()})
    .then(function(d){
      if(!d.success)return;
      var el=document.getElementById('pStreak');
      if(!el||!d.data)return;
      var s=d.data;
      if(s.streak>0){
        el.innerHTML='<div style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;background:#FEF3C7;border-radius:8px;font-size:12px;color:#92400E;font-weight:600">🔥 '+s.streak+' ngày liên tiếp</div>';
      }
    }).catch(function(){});
}

// Post analytics for own posts
function showPostAnalytics(postId){
  var ov=document.createElement('div');
  ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
  ov.innerHTML='<div style="background:#fff;border-radius:16px;padding:20px;max-width:320px;width:90%;text-align:center"><i class="fas fa-spinner fa-spin" style="font-size:24px;color:#7C3AED"></i></div>';
  ov.onclick=function(e){if(e.target===ov)ov.remove();};
  document.body.appendChild(ov);
  
  fetch('/api/posts.php?id='+postId).then(function(r){return r.json()}).then(function(d){
    if(!d.success)return;
    var p=d.data;
    ov.querySelector('div').innerHTML='<h3 style="margin:0 0 16px;font-size:16px">📊 Thống kê bài viết</h3>'
      +'<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">'
      +'<div style="background:#f5f3ff;border-radius:8px;padding:12px"><div style="font-size:22px;font-weight:700;color:#7C3AED">'+(p.views_count||0)+'</div><div style="font-size:11px;color:#999">Lượt xem</div></div>'
      +'<div style="background:#f0fdf4;border-radius:8px;padding:12px"><div style="font-size:22px;font-weight:700;color:#00b14f">'+(p.likes_count||0)+'</div><div style="font-size:11px;color:#999">Thành công</div></div>'
      +'<div style="background:#eff6ff;border-radius:8px;padding:12px"><div style="font-size:22px;font-weight:700;color:#1877F2">'+(p.comments_count||0)+'</div><div style="font-size:11px;color:#999">Ghi chú</div></div>'
      +'<div style="background:#fff5f3;border-radius:8px;padding:12px"><div style="font-size:22px;font-weight:700;color:#EE4D2D">'+(p.shares_count||0)+'</div><div style="font-size:11px;color:#999">Chia sẻ</div></div>'
      +'</div><button onclick="this.closest(\'[style]\').remove()" style="margin-top:16px;padding:8px 20px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer">Đóng</button>';
  });
}

// Follow with animation
function followWithAnim(userId, btn){
  var token=localStorage.getItem('token');
  if(!token){toast('Đăng nhập!');return;}
  btn.disabled=true;
  btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
  
  fetch('/api/social.php?action=follow',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({user_id:userId})})
    .then(function(r){return r.json()})
    .then(function(d){
      if(d.success){
        var isFollowing=d.data&&d.data.following;
        btn.innerHTML=isFollowing?'Đang theo dõi':'Theo dõi';
        btn.style.background=isFollowing?'#f0f0f0':'#7C3AED';
        btn.style.color=isFollowing?'#333':'#fff';
        btn.style.transform='scale(1.1)';
        setTimeout(function(){btn.style.transform='';},200);
        if(typeof haptic==='function')haptic('light');
        // Update follower count
        var fc=document.getElementById('pFollowers');
        if(fc){var n=parseInt(fc.textContent)||0;fc.textContent=isFollowing?n+1:Math.max(0,n-1);}
      }else{toast(d.message||'Lỗi','error');}
      btn.disabled=false;
    }).catch(function(){btn.disabled=false;btn.innerHTML='Theo dõi';});
}

function blockUser(userId){
  if(!confirm('Chặn người dùng này? Bạn sẽ không thấy bài viết của họ.'))return;
  var token=localStorage.getItem('token');
  fetch('/api/social.php?action=block',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({user_id:userId})})
    .then(function(r){return r.json()})
    .then(function(d){
      toast(d.message||'Done',d.success?'success':'error');
      if(d.success&&d.data&&d.data.blocked)location.reload();
    });
}

function uploadCover(){
  var input=document.createElement('input');
  input.type='file';input.accept='image/jpeg,image/png,image/webp';
  input.onchange=function(){
    if(!input.files[0])return;
    if(input.files[0].size>5*1024*1024){toast('File quá lớn (max 5MB)','error');return;}
    var fd=new FormData();
    fd.append('cover',input.files[0]);
    var token=localStorage.getItem('token');
    toast('Đang tải lên...','info');
    fetch('/api/user-profile.php?action=upload_cover',{method:'POST',headers:{'Authorization':'Bearer '+(token||'')},body:fd})
      .then(function(r){return r.json()})
      .then(function(d){
        if(d.success){
          toast('Đã cập nhật!','success');
          var coverEl=document.getElementById('pCover');
          if(coverEl&&d.data&&d.data.cover_image)coverEl.style.backgroundImage='url('+d.data.cover_image+')';
        }else{toast(d.message||'Lỗi','error');}
      });
  };
  input.click();
}
