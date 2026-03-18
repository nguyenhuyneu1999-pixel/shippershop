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
  var likes=parseInt(p.likes_count||p.score||p.upvotes||0);
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
  var shipColors={'GHTK':'#00b14f','J&T':'#d32f2f','GHN':'#ff6600','Viettel Post':'#e21a1a','BEST':'#ffc107','Ninja Van':'#c41230','SPX':'#7C3AED','Ahamove':'#f5a623','Lalamove':'#f5a623','Grab':'#00b14f','Be':'#5bc500','Gojek':'#00aa13'};
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
  if(imgs2&&imgs2.length){var cl=imgs2.length>1?' multi':'';imgH='<div class="post-images'+(imgs2.length>1?' multi-img':'')+'">'+imgs2.slice(0,4).map(function(i){return'<img class="post-img'+cl+'" src="'+i+'" onclick="openLb(\''+i+'\')" onerror="this.style.display=\'none\'">';}).join('')+'</div>';}
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
  +'<button class="pa3-btn" onclick="openPostSheet('+p.id+')"><span id="nc'+p.id+'">Ghi chú</span></button>'
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
  // Update chuyển tiếp counter
  var card=document.getElementById('P'+pid);
  if(card){var s=card.querySelector('.pa3-stats');if(s){var spans=s.querySelectorAll('span');if(spans[2]){var cur=parseInt(spans[2].textContent)||0;spans[2].textContent=(cur+1)+' đơn chuyển tiếp';}}}
  var url=location.origin+'/post-detail.html?id='+pid;
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
}