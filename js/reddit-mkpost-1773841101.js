function ytEmbed(u){if(u.indexOf("youtube.com/watch")!==-1)return"https://www.youtube.com/embed/"+u.split("v=")[1].split("&")[0];if(u.indexOf("youtu.be/")!==-1)return"https://www.youtube.com/embed/"+u.split("youtu.be/")[1].split("?")[0];return u;}
/* ============================================
   FETCH INTERCEPTOR - Auto-add Bearer token to all /api/ calls
   This fixes ALL auth issues across ALL functions in index.html
   ============================================ */
(function(){
  var _fetch = window.fetch;
  window.fetch = function(url, opts) {
    opts = opts || {};
    if (typeof url === 'string' && url.indexOf('/api/') !== -1) {
      var token = localStorage.getItem('token');
      if (token) {
        if (!opts.headers) opts.headers = {};
        if (opts.headers instanceof Headers) {
          if (!opts.headers.has('Authorization')) opts.headers.set('Authorization', 'Bearer ' + token);
        } else {
          if (!opts.headers['Authorization']) opts.headers['Authorization'] = 'Bearer ' + token;
        }
      }
    }
    return _fetch.call(this, url, opts);
  };
})();

/* ============================================
   mkPost - Reddit-style post card
   ============================================ */
function mkPost(p){
  var likes=parseInt(p.likes_count||0);
  var isLiked=p.user_liked||p.user_vote==='up';
  var anon=parseInt(p.is_anonymous)===1;
  var uName=anon?'Ẩn danh':(p.user_name||'Người dùng');
  var badgeCls={confession:'b-confession',review:'b-review',question:'b-question',tip:'b-tip',discussion:'b-discussion'};
  var badgeTxt={confession:'🎭 Confession',review:'⭐ Review',question:'❓ Hỏi đáp',tip:'💡 Tip',discussion:'💬 Thảo luận'};
  var badge=badgeTxt[p.type]?'<span class="type-badge '+badgeCls[p.type]+'">'+badgeTxt[p.type]+'</span>':'';
  var firstChar=esc(uName).charAt(0).toUpperCase();
  var profileLink=anon?'#':'user.html?id='+p.user_id;
  var av=anon?'<span class="avatar-sm" style="background:#888">?</span>':(p.user_avatar?'<a href="'+profileLink+'"><img class="avatar-sm" src="'+p.user_avatar+'" onerror="this.outerHTML=\'<span class=avatar-sm>'+firstChar+'</span>\'"></a>':'<a href="'+profileLink+'"><span class="avatar-sm">'+firstChar+'</span></a>');
  var pvBadge=p.province?'<span class="province-badge">'+p.province+'</span>':'';
  var anonBadge=anon?'<span class="anon-badge">🎭</span>':'';
  var shipColors={'GHTK':'#00b14f','J&T':'#d32f2f','GHN':'#ff6600','Viettel Post':'#e21a1a','BEST':'#ffc107','Ninja Van':'#c41230','SPX':'#EE4D2D','Ahamove':'#f5a623','Lalamove':'#f5a623','Grab':'#00b14f','Be':'#5bc500','Gojek':'#00aa13'};
  var shipBadge='';
  if(!anon&&p.shipping_company){
    var sc=p.shipping_company;
    var sColor=shipColors[sc]||'#666';
    shipBadge='<span style="font-size:11px;font-weight:700;color:'+sColor+';margin-left:2px">'+esc(sc)+'</span>';
  }
  var authorLink=anon?'<span class="post-author">'+esc(uName)+'</span>':'<a href="'+profileLink+'" class="post-author" style="text-decoration:none;color:#1a1a1a">'+esc(uName)+'</a>';
  var title=p.title?'<div class="post-title">'+esc(p.title)+'</div>':'';
  var body=esc(p.content||'');
  var long=body.length>400;
  var contentH='<div class="post-content'+(long?'':' full')+'" id="pc-'+p.id+'">'+body+'</div>'+(long?'<span class="show-more" onclick="xpnd(\''+p.id+'\')">Xem thêm ▼</span>':'');
  var imgH='';
  var imgs2=p.images_arr||(p.images?tryParse(p.images):null)||(p.thumbnail?[p.thumbnail]:[]);
  if(imgs2&&imgs2.length){var cl=imgs2.length>1?' multi':'';window._ssPI=window._ssPI||{};window._ssPI[p.id]=imgs2;imgH='<div class="post-images'+(imgs2.length>1?' multi-img':'')+'">'+imgs2.slice(0,4).map(function(i){return'<img class="post-img'+cl+'" src="'+i+'" loading="lazy" onclick="openLb(\''+i+'\','+p.id+',window._ssPI['+p.id+'])" onerror="this.style.display=\'none\'">';}).join('')+'</div>';}
  var vidH='';
  if(p.video_url){
    if(p.video_url.indexOf('/uploads/')!==-1){
      vidH='<video controls playsinline preload="metadata" style="width:100%;max-height:500px;border-radius:8px;margin:8px 0"><source src="'+p.video_url+'">Video không hỗ trợ</video>';
    }else{
      vidH='<div style="margin-top:8px;position:relative;padding-bottom:56%;height:0;overflow:hidden;border-radius:4px"><iframe src="'+ytEmbed(p.video_url)+'" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0" allowfullscreen></iframe></div>';
    }
  }
  var likeCls=isLiked?'act-btn liked':'act-btn';
  var likeIco=isLiked?'fas fa-heart':'far fa-heart';
  var likeTxt=likes>0?fN(likes)+' Thích':'Thích';
  var isSaved=p.user_saved?true:false;
  var savedCls=isSaved?'act-btn saved':'act-btn';
  var saveIcon=isSaved?'fas fa-bookmark':'far fa-bookmark';
  var savedTxt=isSaved?'Đã lưu':'Lưu';
  var canDel=CU&&parseInt(p.user_id)===parseInt(CU.id);
  return '<div class="post-card" id="P'+p.id+'">'
  +'<div class="post-body">'
  +'<div class="post-meta">'+av+'<div style="flex:1;min-width:0"><div style="display:flex;align-items:center;justify-content:space-between">'+authorLink+'<button class="post-dots" onclick="event.stopPropagation();togMenu('+p.id+')"><i class="fas fa-ellipsis"></i></button></div><div style="font-size:12px;color:#999;display:flex;align-items:center;gap:4px">'+shipBadge+'<span>·</span><span>'+ago(p.created_at)+'</span>'+badge+pvBadge+anonBadge+'</div></div></div>'
  +title+'<div class="post-menu" id="pm'+p.id+'" style="display:none">'+(canDel?'<div onclick="delP('+p.id+')"><i class="far fa-trash-can"></i> Xóa bài</div>':'')+'<div onclick="reportP('+p.id+')"><i class="fas fa-flag"></i> Báo cáo</div><div onclick="togMenu('+p.id+')"><i class="fas fa-times"></i> Đóng</div></div>'+contentH+imgH+vidH
  +'</div>'
  +'<div class="pa3-stats"><span>'+(likes>0?fN(likes)+' đơn giao thành công':'')+'</span><span>'+(parseInt(p.comments_count||0)>0?fN(p.comments_count||0)+' ghi chú':'')+'</span><span></span></div>'
  +'<div class="post-actions-3">'
  +'<button class="pa3-btn'+(isLiked?' pa3-active':'')+'" id="lk'+p.id+'" onclick="likePost('+p.id+',this)"><span>Thành công</span></button>'
  +'<button class="pa3-btn" onclick="openGhiChu('+p.id+')"><span id="nc'+p.id+'">Ghi chú</span></button>'
  +'<button class="pa3-btn" onclick="sharePost('+p.id+')"><span>Chuyển tiếp</span></button>'
  +'</div>'
  +'</div>';
}

/* Like/Unlike/* Like/Unlike */
async function likePost(pid,btn){
  if(!CU){toast('Đăng nhập để thích!','warning');return;}
  var isLiked=btn.classList.contains('liked');
  var vt=isLiked?'remove':'up';
  try{
    var r=await fetch('/api/posts.php?action=vote',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:pid,vote_type:vt})});
    var d=await r.json();
    if(d.success){
      var score=parseInt(d.data.score)||0;
      var liked=d.data.user_vote==='up';
      btn.className=liked?'pa3-btn pa3-active':'pa3-btn';
      btn.querySelector('span').textContent='Thành công';
      // Update stats count
      var card=btn.closest('.post-card');
      if(card){
        var statsDiv=card.querySelector('.pa3-stats');
        if(statsDiv){
          var spans=statsDiv.querySelectorAll('span');
          if(spans[0]) spans[0].textContent=score>0?score+' đơn giao thành công':'';
        }
      }
    }else{toast(d.message||'Lỗi','error');}
  }catch(e){toast('Lỗi kết nối','error');}
}

/* Save/Unsave */
async function savePost(pid,btn){
  if(!CU){toast('Đăng nhập để lưu!','warning');return;}
  try{
    var r=await fetch('/api/posts.php?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:pid})});
    var d=await r.json();
    if(d.success){
      var saved=d.data&&d.data.saved;
      btn.className=saved?'act-btn saved':'act-btn';
      btn.querySelector('i').className=saved?'fas fa-bookmark':'far fa-bookmark';
      btn.querySelector('span').textContent=saved?'Đã lưu':'Lưu';
      toast(saved?'Đã lưu bài viết':'Đã bỏ lưu','success');
    }else{toast(d.message||'Lỗi','error');}
  }catch(e){toast('Lỗi kết nối','error');}
}

/* Share - uses native share (shows Zalo, FB etc) or copy link */
function sharePost(pid){
  var url=location.origin+'/share.php?type=post&id='+pid;
  if(navigator.share){
    navigator.share({title:'ShipperShop',text:'Xem bài viết trên ShipperShop',url:url}).catch(function(){});
  }else{
    if(navigator.clipboard&&navigator.clipboard.writeText){
      navigator.clipboard.writeText(url).then(function(){toast('Đã sao chép liên kết!','success');});
    }else{
      var t=document.createElement('textarea');t.value=url;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);
      toast('Đã sao chép liên kết!','success');
    }
  }
  // Call API to increment shares_count
  var hdrs={"Content-Type":"application/json"};
  var tk=localStorage.getItem("token");
  if(tk)hdrs["Authorization"]="Bearer "+tk;
  fetch("/api/posts.php?action=share",{method:"POST",headers:hdrs,credentials:"include",body:JSON.stringify({post_id:pid})}).then(function(r){return r.json();}).then(function(d){
    if(d.success&&d.data.shares_count!==undefined){
      var card=document.getElementById('P'+pid);
      if(card){var s=card.querySelector('.pa3-stats');if(s){var spans=s.querySelectorAll('span');if(spans[2])spans[2].textContent=d.data.shares_count+' đơn chuyển tiếp';}}
    }
  }).catch(function(){});
}

/* ============================================
   BOTTOM SHEET - Ghi chú (self-contained)
   ============================================ */
var _gcPid=0,_gcReply=null,_gcStyled=false;
var _SC={"GHTK":"#00b14f","J&T":"#d32f2f","GHN":"#ff6600","Viettel Post":"#e21a1a","SPX":"#EE4D2D","Grab":"#00b14f","Be":"#5bc500","Gojek":"#00aa13","Ninja Van":"#c41230","Lalamove":"#f5a623"};

function _gcCSS(){
  if(_gcStyled)return;_gcStyled=true;
  var s=document.createElement("style");
  s.textContent=".gc-ov{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1100;display:flex;align-items:flex-end;justify-content:center;opacity:0;transition:opacity .25s;-webkit-backdrop-filter:blur(2px);backdrop-filter:blur(2px);}"
  +".gc-ov.open{opacity:1;}"
  +".gc-sh{background:#fff;width:100%;max-width:600px;max-height:92vh;border-radius:16px 16px 0 0;display:flex;flex-direction:column;transform:translateY(100%);transition:transform .3s cubic-bezier(.32,.72,0,1);overflow:hidden;}"
  +".gc-ov.open .gc-sh{transform:translateY(0);}"
  +".gc-hd{display:flex;align-items:center;padding:12px 16px;border-bottom:1px solid #e4e6eb;min-height:48px;}"
  +".gc-hd h3{flex:1;font-size:16px;font-weight:700;margin:0;}"
  +".gc-x{width:32px;height:32px;border-radius:50%;background:#e4e6eb;border:none;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;}"
  +".gc-body{flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;}"
  +".gc-post-author{display:flex;gap:10px;padding:12px 16px 0;align-items:center;}"
  +".gc-av{width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;}"
  +".gc-av-ph{width:40px;height:40px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}"
  +".gc-content{padding:8px 16px;font-size:15px;line-height:1.6;white-space:pre-wrap;word-break:break-word;}"
  +".gc-imgs{width:100%;}.gc-imgs img{width:100%;display:block;}"
  +".gc-stats{display:flex;padding:8px 16px 4px;font-size:12px;color:#65676B;}"
  +".gc-stats span{flex:1;text-align:center;}"
  +".gc-stats span:first-child{text-align:left;}"
  +".gc-stats span:last-child{text-align:right;}"
  +".gc-actions{display:flex;border-top:1px solid #e4e6eb;border-bottom:1px solid #e4e6eb;}"
  +".gc-abtn{flex:1;padding:10px 0;text-align:center;font-size:14px;font-weight:600;color:#65676B;background:none;border:none;border-right:1px solid #e4e6eb;cursor:pointer;}"
  +".gc-abtn:last-child{border-right:none;}"
  +".gc-abtn:active{background:#f0f2f5;}"
  +".gc-abtn.liked{color:#7C3AED;background:#EDE9FE;}"
  +".gc-sort{padding:10px 16px;font-size:14px;font-weight:700;color:#65676B;}"
  +".gc-cmt{display:flex;gap:6px;padding:4px 16px;}"
  +".gc-cmt-av{width:28px;height:28px;border-radius:50%;flex-shrink:0;object-fit:cover;}"
  +".gc-cmt-av-ph{width:28px;height:28px;border-radius:50%;background:#e4e6eb;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;}"
  +".gc-bubble{background:#f0f2f5;border-radius:18px;padding:6px 12px;display:inline-block;max-width:100%;}"
  +".gc-cmt-name{font-weight:700;font-size:13px;}"
  +".gc-cmt-text{font-size:14px;line-height:1.4;margin-top:1px;}"
  +".gc-cmt-meta{display:flex;gap:12px;padding:2px 12px;font-size:12px;color:#65676B;}"
  +".gc-cmt-meta a{color:#65676B;text-decoration:none;font-weight:600;cursor:pointer;}"
  +".gc-cmt-meta a.liked{color:#7C3AED;}"
  +".gc-replies{padding-left:16px;border-left:2px solid #e4e6eb;margin-left:14px;}"
  +".gc-bar{display:flex;gap:8px;padding:10px 16px;border-top:1px solid #e4e6eb;background:#fff;align-items:center;}"
  +".gc-bar-av{width:28px;height:28px;border-radius:50%;flex-shrink:0;}"
  +".gc-input{flex:1;padding:8px 14px;border-radius:20px;border:1px solid #e4e6eb;font-size:14px;outline:none;font-family:inherit;}"
  +".gc-input:focus{border-color:#7C3AED;}"
  +".gc-send{background:#7C3AED;color:#fff;border:none;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;}"
  +".gc-send:disabled{opacity:.4;}"
  +".gc-rpl{font-size:12px;color:#65676B;padding:0 16px 4px;display:none;}"
  +".gc-rpl.show{display:flex;align-items:center;gap:6px;}"
  +".gc-rpl a{color:#7C3AED;font-weight:700;cursor:pointer;text-decoration:none;}"
  +".gc-empty{text-align:center;padding:20px;color:#999;font-size:13px;}";
  document.head.appendChild(s);
}

function openGhiChu(pid){
  _gcCSS();
  _gcPid=pid;_gcReply=null;
  // Remove old sheet if exists
  var old=document.getElementById("gcOverlay");
  if(old)old.remove();
  // Create DOM
  var ov=document.createElement("div");
  ov.id="gcOverlay";ov.className="gc-ov";
  ov.onclick=function(e){if(e.target===ov)closeGhiChu();};
  var sh=document.createElement("div");sh.className="gc-sh";
  sh.innerHTML="<div class='gc-hd'><h3>Bài viết</h3><button class='gc-x' onclick='closeGhiChu()'><i class='fas fa-times'></i></button></div>"
  +"<div class='gc-body' id='gcBody'><div style='text-align:center;padding:40px'><i class='fas fa-spinner fa-spin' style='font-size:20px;color:#999'></i></div></div>"
  +"<div class='gc-rpl' id='gcRpl'><span>Trả lời <b id='gcRplName'></b></span> <a onclick='_gcReply=null;document.getElementById(\"gcRpl\").className=\"gc-rpl\"'>Hủy</a></div>"
  +"<div class='gc-bar' id='gcBar' style='display:none'><input class='gc-input' id='gcInput' placeholder='Viết bình luận...' onkeydown='if(event.key===\"Enter\"&&!event.shiftKey){event.preventDefault();sendGC();}'><button class='gc-send' id='gcSend' onclick='sendGC()'><i class='fas fa-paper-plane'></i></button></div>";
  ov.appendChild(sh);
  document.body.appendChild(ov);
  document.body.style.overflow="hidden";
  // Trigger animation
  requestAnimationFrame(function(){ov.classList.add("open");});
  // Show comment bar if logged in
  if(CU){document.getElementById("gcBar").style.display="flex";}
  // Fetch post
  _gcLoadPost(pid);
}

function closeGhiChu(){
  var ov=document.getElementById("gcOverlay");
  if(!ov)return;
  ov.querySelector(".gc-sh").style.transform="translateY(100%)";
  ov.style.opacity="0";
  document.body.style.overflow="";
  setTimeout(function(){ov.remove();},300);
}

function _gcLoadPost(pid){
  var body=document.getElementById("gcBody");
  fetch("/api/posts.php?id="+pid,{credentials:"include"}).then(function(r){return r.json();}).then(function(d){
    if(!d.success||!d.data){body.innerHTML="<div class='gc-empty'>Bài viết không tồn tại</div>";return;}
    var p=d.data;
    var av=p.user_avatar?"<img class='gc-av' src='"+esc(p.user_avatar)+"'>":"<div class='gc-av-ph'>"+esc((p.user_name||"U")[0])+"</div>";
    var sh=p.shipping_company?"<span style='font-size:11px;font-weight:700;color:"+(_SC[p.shipping_company]||"#999")+"'>"+esc(p.shipping_company)+"</span>":"";
    var imgs="";
    if(p.images){try{var a=JSON.parse(p.images);if(a&&a.length){imgs="<div class='gc-imgs'>";for(var i=0;i<a.length;i++){imgs+="<img src='"+a[i]+"' onerror='this.remove()'>";}imgs+="</div>";}}catch(x){}}
    var vid="";
    if(p.video_url){
      if(p.video_url.indexOf("/uploads/")!==-1){vid="<video controls playsinline preload='metadata' style='width:100%;max-height:400px;border-radius:0'><source src='"+p.video_url+"' type='video/mp4'></video>";}
      else{vid="<div style='position:relative;padding-bottom:56%;height:0;overflow:hidden'><iframe src='"+p.video_url+"' style='position:absolute;top:0;left:0;width:100%;height:100%;border:0' allowfullscreen></iframe></div>";}
    }
    var lk=p.user_liked;
    var h="<div class='gc-post-author'>"+av+"<div style='flex:1'><div style='font-weight:700;font-size:15px'>"+esc(p.user_name||"Ẩn danh")+"</div><div style='font-size:12px;color:#65676B;display:flex;align-items:center;gap:4px'>"+sh+(sh?"<span>·</span>":"")+"<span>"+ago(p.created_at)+"</span></div></div></div>";
    h+="<div class='gc-content'>"+esc(p.content||"")+"</div>"+imgs+vid;
    h+="<div class='gc-stats' id='gcStats'><span>"+(p.likes_count||0)+" đơn giao thành công</span><span>"+(p.comments_count||0)+" ghi chú</span><span>"+(p.shares_count||0)+" chuyển tiếp</span></div>";
    h+="<div class='gc-actions'><button class='gc-abtn"+(lk?" liked":"")+"' id='gcLkBtn' onclick='_gcLike()'>Thành công</button><button class='gc-abtn' onclick='document.getElementById(\"gcInput\").focus()'>Ghi chú</button><button class='gc-abtn' onclick='_gcShare()'>Chuyển tiếp</button></div>";
    h+="<div class='gc-sort'>Phù hợp nhất <i class='fas fa-chevron-down' style='font-size:11px'></i></div>";
    h+="<div id='gcCmts'><div class='gc-empty'><i class='fas fa-spinner fa-spin'></i></div></div>";
    body.innerHTML=h;
    _gcLoadCmts(pid);
  }).catch(function(){body.innerHTML="<div class='gc-empty'>Lỗi kết nối</div>";});
}

function _gcLoadCmts(pid){
  var wrap=document.getElementById("gcCmts");
  if(!wrap)return;
  fetch("/api/posts.php?action=comments&post_id="+pid,{credentials:"include"}).then(function(r){return r.json();}).then(function(d){
    if(!d.success||!d.data||!d.data.length){wrap.innerHTML="<div class='gc-empty'>Chưa có bình luận</div>";return;}
    var flat=d.data,map={},top=[];
    for(var i=0;i<flat.length;i++){flat[i].replies=[];map[flat[i].id]=flat[i];}
    for(var j=0;j<flat.length;j++){if(flat[j].parent_id&&flat[j].parent_id>0&&map[flat[j].parent_id])map[flat[j].parent_id].replies.push(flat[j]);else top.push(flat[j]);}
    var html="";for(var k=0;k<top.length;k++){html+=_gcCmt(top[k],0);}
    wrap.innerHTML=html;
  }).catch(function(){wrap.innerHTML="";});
}

function _gcCmt(c,dp){
  var sz=dp>0?24:28;
  var av=c.user_avatar?"<img class='gc-cmt-av' src='"+esc(c.user_avatar)+"' style='width:"+sz+"px;height:"+sz+"px'>":"<div class='gc-cmt-av-ph' style='width:"+sz+"px;height:"+sz+"px'>"+esc((c.user_name||"U")[0])+"</div>";
  var lk=c.user_vote==="up"||c.user_liked;
  var n=c.likes_count||0;
  var reps="";
  if(c.replies&&c.replies.length){reps="<div class='gc-replies'>";for(var i=0;i<c.replies.length;i++){reps+=_gcCmt(c.replies[i],dp+1);}reps+="</div>";}
  var sn=esc(c.user_name||"");
  return "<div class='gc-cmt'>"+av+"<div style='flex:1;min-width:0'><div class='gc-bubble'><div class='gc-cmt-name'>"+sn+"</div><div class='gc-cmt-text'>"+esc(c.content)+"</div></div><div class='gc-cmt-meta'><span>"+ago(c.created_at)+"</span><a class='"+(lk?"liked":"")+"' onclick='_gcLkCmt("+c.id+",this)'>Thành công"+(n>0?" · "+n:"")+"</a><a onclick='_gcSetRpl("+c.id+",\""+sn.replace(/"/g,"&quot;")+"\")'>Ghi chú</a></div>"+reps+"</div></div>";
}

function _gcLike(){
  if(!CU)return;
  fetch("/api/posts.php?action=vote",{method:"POST",headers:{"Content-Type":"application/json"},credentials:"include",body:JSON.stringify({post_id:_gcPid,vote_type:"up"})}).then(function(r){return r.json();}).then(function(d){
    if(d.success){
      var btn=document.getElementById("gcLkBtn");
      var lk=d.data.user_vote==="up";
      btn.className="gc-abtn"+(lk?" liked":"");
      var score=parseInt(d.data.score)||0;
      var ss=document.getElementById("gcStats");
      if(ss){var spans=ss.querySelectorAll("span");if(spans[0])spans[0].textContent=score+" đơn giao thành công";}
      // Update feed card too
      var card=document.getElementById("P"+_gcPid);
      if(card){var fs=card.querySelectorAll(".pa3-stats span");if(fs[0])fs[0].textContent=score>0?fN(score)+" đơn giao thành công":"";var fb=card.querySelector("#lk"+_gcPid);if(fb)fb.className=lk?"pa3-btn pa3-active":"pa3-btn";}
    }
  });
}

function _gcLkCmt(cid,el){
  if(!CU)return;
  fetch("/api/posts.php?action=vote_comment",{method:"POST",headers:{"Content-Type":"application/json"},credentials:"include",body:JSON.stringify({comment_id:cid,vote_type:"up"})}).then(function(r){return r.json();}).then(function(d){
    if(d.success){var lk=d.data.user_vote==="up";el.className=lk?"liked":"";el.textContent="Thành công"+(d.data.score>0?" · "+d.data.score:"");}
  });
}

function _gcSetRpl(cid,name){
  _gcReply=cid;
  var rpl=document.getElementById("gcRpl");
  rpl.className="gc-rpl show";
  document.getElementById("gcRplName").textContent=name;
  document.getElementById("gcInput").focus();
}

function _gcShare(){
  var u=location.origin+"/post-detail.html?id="+_gcPid;
  if(navigator.share)navigator.share({url:u});else{navigator.clipboard.writeText(u);toast("Đã copy link!","success");}
  fetch("/api/posts.php?action=share",{method:"POST",headers:{"Content-Type":"application/json"},credentials:"include",body:JSON.stringify({post_id:_gcPid})}).then(function(r){return r.json();}).then(function(d){
    if(d.success&&d.data.shares_count!==undefined){
      var ss=document.getElementById("gcStats");
      if(ss){var spans=ss.querySelectorAll("span");if(spans[2])spans[2].textContent=d.data.shares_count+" chuyển tiếp";}
      var card=document.getElementById("P"+_gcPid);
      if(card){var fs=card.querySelectorAll(".pa3-stats span");if(fs[2])fs[2].textContent=d.data.shares_count+" đơn chuyển tiếp";}
    }
  }).catch(function(){});
}

function sendGC(){
  if(!CU)return;
  var inp=document.getElementById("gcInput");
  var ct=inp.value.trim();
  if(!ct)return;
  inp.value="";
  var b={post_id:_gcPid,content:ct};
  if(_gcReply){b.parent_id=_gcReply;_gcReply=null;document.getElementById("gcRpl").className="gc-rpl";}
  fetch("/api/posts.php?action=comment",{method:"POST",headers:{"Content-Type":"application/json"},credentials:"include",body:JSON.stringify(b)}).then(function(r){return r.json();}).then(function(d){
    if(d.success){
      _gcLoadCmts(_gcPid);
      // Update feed counter
      var card=document.getElementById("P"+_gcPid);
      if(card){var nc=card.querySelector("#nc"+_gcPid);if(nc){/* keep text */}var fs=card.querySelectorAll(".pa3-stats span");if(fs[1]){var cur=parseInt(fs[1].textContent)||0;fs[1].textContent=(cur+1)+" ghi chú";}}
      // Update sheet stats
      var ss=document.getElementById("gcStats");
      if(ss){var spans=ss.querySelectorAll("span");if(spans[1]){var c2=parseInt(spans[1].textContent)||0;spans[1].textContent=(c2+1)+" ghi chú";}}
    }else{inp.value=ct;toast(d.message||"Lỗi","error");}
  }).catch(function(){inp.value=ct;toast("Lỗi kết nối","error");});
}