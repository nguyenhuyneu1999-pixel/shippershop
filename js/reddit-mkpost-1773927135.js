
function xpToast(msg){
  var t=document.createElement("div");
  t.textContent=msg;
  t.style.cssText="position:fixed;top:80px;right:16px;background:#7C3AED;color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;z-index:9999;animation:xpIn .3s;box-shadow:0 4px 12px rgba(124,58,237,.4)";
  document.body.appendChild(t);
  setTimeout(function(){t.style.opacity="0";t.style.transition="opacity .3s";setTimeout(function(){t.remove();},300);},2000);
}
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



// Double-tap to like
var _dblTapTimer={};
function dblTapLike(pid,el){
  if(_dblTapTimer[pid]){
    clearTimeout(_dblTapTimer[pid]);
    _dblTapTimer[pid]=null;
    // Double tap → like
    var btn=el.closest('.post-card').querySelector('.pa3-btn');
    if(btn&&!btn.classList.contains('liked'))btn.click();
    // Heart animation
    var heart=document.createElement('div');
    heart.innerHTML='<i class="fas fa-heart"></i>';
    heart.style.cssText='position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) scale(0);font-size:60px;color:#e74c3c;z-index:10;pointer-events:none;transition:transform .3s,opacity .5s';
    el.style.position='relative';
    el.appendChild(heart);
    requestAnimationFrame(function(){heart.style.transform='translate(-50%,-50%) scale(1)';});
    setTimeout(function(){heart.style.opacity='0';},500);
    setTimeout(function(){heart.remove();},1000);
  }else{
    _dblTapTimer[pid]=setTimeout(function(){_dblTapTimer[pid]=null;},300);
  }
}



// Check achievements after actions (debounced)
var _achTimer=null;
function checkAchievements(){
  if(_achTimer)return;
  _achTimer=setTimeout(function(){
    _achTimer=null;
    var token=localStorage.getItem('token');
    if(!token)return;
    fetch('/api/achievements.php?action=check',{method:'POST',headers:{'Authorization':'Bearer '+token}})
      .then(function(r){return r.json()})
      .then(function(d){
        if(d.success&&d.data.new_badges&&d.data.new_badges.length){
          d.data.new_badges.forEach(function(id){
            showAchievementToast(id);
          });
        }
      }).catch(function(){});
  },3000);
}
function showAchievementToast(badgeId){
  var badges={first_post:'📝 Bài viết đầu tiên',post_10:'✍️ Người viết tích cực',post_50:'🏆 Cây viết vàng',like_100:'❤️ Được yêu thích',streak_7:'🔥 7 ngày liên tiếp',streak_30:'💪 Shipper kiên trì',comment_50:'💬 Người giúp đỡ',group_join:'👥 Thành viên cộng đồng',follower_10:'⭐ Influencer nhí',follower_100:'🌟 Shipper nổi tiếng'};
  var name=badges[badgeId]||badgeId;
  var div=document.createElement('div');
  div.style.cssText='position:fixed;top:80px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#7C3AED,#5B21B6);color:#fff;padding:14px 24px;border-radius:16px;z-index:3000;text-align:center;box-shadow:0 8px 30px rgba(124,58,237,.4);animation:bounceIn .5s';
  div.innerHTML='<div style="font-size:11px;opacity:.8;margin-bottom:4px">🎉 THÀNH TỰU MỚI!</div><div style="font-size:16px;font-weight:700">'+name+'</div>';
  document.body.appendChild(div);
  haptic('success');
  setTimeout(function(){div.style.opacity='0';div.style.transition='opacity .5s';setTimeout(function(){div.remove();},500);},4000);
}


function editPost(pid){
  var card=document.getElementById('P'+pid);
  if(!card)return;
  var contentEl=card.querySelector('.post-content');
  if(!contentEl)return;
  var oldText=contentEl.textContent.trim();
  
  var ov=document.createElement('div');
  ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
  ov.innerHTML='<div style="background:#fff;border-radius:16px;padding:20px;max-width:400px;width:90%"><h3 style="margin:0 0 12px;font-size:16px">✏️ Chỉnh sửa bài viết</h3><textarea id="editTA" style="width:100%;min-height:120px;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;resize:vertical;box-sizing:border-box;font-family:inherit">'+oldText.replace(/</g,"&lt;")+'</textarea><div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px"><button onclick="this.closest(\'[style]\').remove()" style="padding:8px 16px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer">Hủy</button><button onclick="submitEdit('+pid+',this.closest(\'[style]\'))" style="padding:8px 16px;background:#7C3AED;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer">Lưu</button></div></div>';
  ov.onclick=function(e){if(e.target===ov)ov.remove();};
  document.body.appendChild(ov);
  setTimeout(function(){var ta=document.getElementById('editTA');if(ta){ta.focus();ta.selectionStart=ta.value.length;}},100);
}
function submitEdit(pid,overlay){
  var ta=document.getElementById('editTA');
  if(!ta)return;
  var newContent=ta.value.trim();
  if(newContent.length<5){toast('Tối thiểu 5 ký tự','error');return;}
  var token=localStorage.getItem('token');
  fetch('/api/posts.php?action=edit',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({post_id:pid,content:newContent})})
    .then(function(r){return r.json()})
    .then(function(d){
      if(d.success){
        toast('Đã cập nhật!','success');
        if(overlay)overlay.remove();
        var card=document.getElementById('P'+pid);
        if(card){var ce=card.querySelector('.post-content');if(ce)ce.textContent=newContent;}
      }else{toast(d.message||'Lỗi','error');}
    });
}

function updateCommentCount(postId, delta){
  var card=document.getElementById('P'+postId);
  if(!card)return;
  var stats=card.querySelectorAll('.pa3-stats span');
  if(stats&&stats[1]){
    var current=parseInt(stats[1].textContent)||0;
    var newCount=current+delta;
    stats[1].textContent=newCount>0?fN(newCount)+' ghi chú':'';
  }
}

function expandPost(pid){
  var short=document.getElementById('pc'+pid);
  var full=document.getElementById('pf'+pid);
  if(short)short.style.display='none';
  if(full)full.style.display='inline';
}

function hashtagify(text){
  return text.replace(/#([a-zA-ZÀ-ỹ0-9_]+)/gu, function(m, tag){
    return '<a href="javascript:void(0)" onclick="doSearch(\'#'+tag+'\')" style="color:#7C3AED;text-decoration:none;font-weight:600">#'+tag+'</a>';
  });
}




function addSpoilerBlur(html, post){
  // Check for content warning tags
  if(post.type==='confession'||(post.content&&(post.content.indexOf('[CW]')>-1||post.content.indexOf('[NSFW]')>-1||post.content.indexOf('[Spoiler]')>-1))){
    var label=post.content.indexOf('[CW]')>-1?'Cảnh báo nội dung':(post.content.indexOf('[NSFW]')>-1?'Nội dung nhạy cảm':'Spoiler');
    return '<div class="cw-wrap" onclick="this.classList.add(\'cw-revealed\')"><div class="cw-label"><i class="fas fa-eye-slash"></i> '+label+' · Nhấn để xem</div><div class="cw-content">'+html+'</div></div>';
  }
  return html;
}

function markdownLite(text){
  // Bold: **text** or __text__
  text=text.replace(/\*\*([^*]+)\*\*/g,'<b>$1</b>');
  text=text.replace(/__([^_]+)__/g,'<b>$1</b>');
  // Italic: *text* or _text_
  text=text.replace(/\*([^*]+)\*/g,'<i>$1</i>');
  text=text.replace(/_([^_]+)_/g,'<i>$1</i>');
  return text;
}





function engScore(p){
  return (parseInt(p.likes_count||0)*3)+(parseInt(p.comments_count||0)*5)+(parseInt(p.views_count||0));
}

function isNew(dateStr){
  if(!dateStr)return false;
  var d=new Date(dateStr.replace(' ','T'));
  return (Date.now()-d.getTime())<3600000;
}

function typeBadge(type){
  var badges={
    confession:['🎭','Confession','#9C5FFF','#f5f3ff'],
    review:['⭐','Review','#F59E0B','#fffbeb'],
    tip:['💡','Mẹo hay','#00b14f','#f0fdf4'],
    question:['❓','Hỏi đáp','#1877F2','#eff6ff'],
    discussion:['💬','Thảo luận','#EE4D2D','#fff5f3']
  };
  var b=badges[type];
  if(!b||type==='post')return '';
  return '<span style="display:inline-flex;align-items:center;gap:2px;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;background:'+b[3]+';color:'+b[2]+';margin-left:4px">'+b[0]+' '+b[1]+'</span>';
}

function linkPhone(text){
  return text.replace(/(0[1-9]\d{8,9})/g, function(m){
    return '<a href="tel:'+m+'" style="color:#7C3AED;font-weight:600;text-decoration:none">'+m+'</a>';
  });
}

function detectLinks(text){
  var urlRegex=/(https?:\/\/[^\s<]+)/g;
  return text.replace(urlRegex, function(url){
    return '<a href="'+url+'" target="_blank" rel="noopener" style="color:#7C3AED;text-decoration:none;word-break:break-all">'+url+'</a>';
  });
}

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
  var lvlBadge=p.user_level&&p.user_level>1?'<span style="font-size:10px;padding:1px 5px;border-radius:3px;background:linear-gradient(135deg,#7C3AED,#5B21B6);color:#fff;font-weight:600">Lv.'+p.user_level+'</span>':'';
  var subBadge=p.sub_badge?'<span style="font-size:10px;padding:1px 5px;border-radius:3px;background:'+(p.sub_badge_color||'#7C3AED')+';color:#fff;margin-left:4px;font-weight:600">'+esc(p.sub_badge)+'</span>':'';
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
  if(p.images){
    try{
      var imgs=JSON.parse(p.images);
      if(imgs&&imgs.length===1){
        imgH='<div class="post-images"><img class="post-img" src="'+imgs[0]+'" loading="lazy" onclick="if(typeof openLb===\'function\')openLb(this.src,0,[this.src])" ondblclick="dblTapLike('+p.id+',this.parentNode)" onerror="this.style.display=\'none\'"></div>';
      }else if(imgs&&imgs.length>1){
        imgH='<div class="post-images carousel" data-idx="0" style="position:relative;overflow:hidden">';
        imgH+='<div class="carousel-track" style="display:flex;transition:transform .3s;will-change:transform">';
        imgs.forEach(function(src,i){
          imgH+='<img class="post-img" src="'+src+'" loading="lazy" style="min-width:100%;max-width:100%;flex-shrink:0" onclick="if(typeof openLb===\'function\')openLb(this.src,'+i+','+JSON.stringify(imgs).replace(/'/g,"\\x27")+')" onerror="this.style.display=\'none\'">';
        });
        imgH+='</div>';
        if(imgs.length>1){
          imgH+='<div style="position:absolute;bottom:8px;left:50%;transform:translateX(-50%);display:flex;gap:4px">';
          imgs.forEach(function(_,i){imgH+='<div class="carousel-dot'+(i===0?' active':'')+'" style="width:6px;height:6px;border-radius:50%;background:'+(i===0?'#fff':'rgba(255,255,255,.5)')+'"></div>';});
          imgH+='</div>';
          imgH+='<button onclick="slideCarousel(this.parentNode,-1)" style="position:absolute;left:4px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.4);color:#fff;border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:12px"><i class="fas fa-chevron-left"></i></button>';
          imgH+='<button onclick="slideCarousel(this.parentNode,1)" style="position:absolute;right:4px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.4);color:#fff;border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:12px"><i class="fas fa-chevron-right"></i></button>';
        }
        imgH+='</div>';
      }
    }catch(e){}
  }
  var vidH='';
  if(p.video_url){
    if(p.video_url.indexOf('/uploads/')!==-1){
      vidH='<video controls playsinline preload="metadata" style="width:100%;max-height:500px;display:block"><source src="'+p.video_url+'">Video không hỗ trợ</video>';
    }else{
      vidH='<div style="margin-top:8px;position:relative;padding-bottom:56%;height:0;overflow:hidden;border-radius:4px;background:#000;cursor:pointer" onclick="this.innerHTML=\'<iframe src=&quot;'+ytEmbed(p.video_url)+'&quot; style=&quot;position:absolute;top:0;left:0;width:100%;height:100%;border:0&quot; allowfullscreen autoplay></iframe>\'"><div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center"><div style="width:56px;height:56px;background:rgba(255,0,0,.85);border-radius:12px;display:flex;align-items:center;justify-content:center"><i class=\'fas fa-play\' style=\'color:#fff;font-size:20px;margin-left:3px\'></i></div></div></div>';
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
  +'<div class="post-meta">'+av+'<div style="flex:1;min-width:0"><div style="display:flex;align-items:center;justify-content:space-between">'+authorLink+'<button class="post-dots" onclick="event.stopPropagation();togMenu('+p.id+')"><i class="fas fa-ellipsis"></i></button></div><div style="font-size:12px;color:#999;display:flex;align-items:center;gap:4px">'+shipBadge+lvlBadge+'<span>·</span><span>'+ago(p.created_at)+(p.edited_at?' · <span style="font-size:11px;color:#999">đã chỉnh sửa</span>':'')+(isNew(p.created_at)?'<span style="margin-left:4px;padding:1px 5px;border-radius:3px;background:#00b14f;color:#fff;font-size:9px;font-weight:700">MỚI</span>':'')+typeBadge(p.type)+'</span>'+badge+pvBadge+anonBadge+subBadge+'</div></div></div>'
  +title+'<div class="post-menu" id="pm'+p.id+'" style="display:none"><div id="sv'+p.id+'" onclick="togSave('+p.id+')"><i class="'+(isSaved?'fas':'far')+' fa-bookmark" style="color:'+(isSaved?'#7C3AED':'inherit')+'"></i> '+(isSaved?'Bỏ lưu':'Lưu bài viết')+'</div>'+(canDel?'<div onclick="editP('+p.id+')"><i class="fas fa-pen"></i> Sửa bài</div><div onclick="delP('+p.id+')"><i class="far fa-trash-can"></i> Xóa bài</div>':'')+'<div onclick="reportP('+p.id+')"><i class="fas fa-flag"></i> Báo cáo</div>'+(canDel?'':'<div onclick="muteUser('+p.user_id+')"><i class="fas fa-volume-mute"></i> Tắt tiếng</div><div onclick="blockUser('+p.user_id+')"><i class="fas fa-ban"></i> Chặn</div>')+'<div onclick="copyLink('+p.id+')"><i class="fas fa-link"></i> Sao chép liên kết</div><div onclick="shareToGroup('+p.id+')"><i class="fas fa-share-from-square"></i> Chia sẻ vào nhóm</div><div onclick="togMenu('+p.id+')"><i class="fas fa-times"></i> Đóng</div></div>'+contentH+addSpoilerBlur(imgH+vidH,p)
  +'</div>'
  +'<div class="poll-container" id="poll'+p.id+'"></div><div class="pa3-stats"><span>'+(likes>0?fN(likes)+' đơn giao thành công':'')+'</span><span>'+(parseInt(p.comments_count||0)>0?fN(p.comments_count||0)+' ghi chú':'')+'</span><span>'+(parseInt(p.views_count||0)>0?fN(p.views_count)+' lượt xem':'')+'</span></div>'
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
  if(!CU){var sbtn=document.querySelector('#P'+pid+' .pa3-btn[onclick*="savePost"]');if(sbtn){sbtn.style.transform='scale(1.3)';setTimeout(function(){sbtn.style.transform='';},200);}
    toast('Đăng nhập để lưu!','warning');return;}
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

/* Toggle save from 3-dot menu */
async function togSave(pid){
  if(!CU){toast('Đăng nhập để lưu!','warning');return;}
  togMenu(pid);
  try{
    var r=await fetch('/api/posts.php?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:pid})});
    var d=await r.json();
    if(d.success){
      var saved=d.data&&d.data.saved;
      var el=document.getElementById('sv'+pid);
      if(el){
        el.innerHTML='<i class="'+(saved?'fas':'far')+' fa-bookmark" style="color:'+(saved?'#7C3AED':'inherit')+'"></i> '+(saved?'Bỏ lưu':'Lưu bài viết');
      }
      toast(saved?'Đã lưu bài viết':'Đã bỏ lưu','success');
    }else{toast(d.message||'Lỗi','error');}
  }catch(e){toast('Lỗi kết nối','error');}
}

/* Toggle 3-dot menu */

function editP(pid){
  togMenu(pid);
  var card=document.getElementById('post-'+pid);
  if(!card)return;
  var contentEl=card.querySelector('.post-content');
  if(!contentEl)return;
  var oldText=contentEl.textContent||'';
  var ta=document.createElement('textarea');
  ta.value=oldText;
  ta.style.cssText='width:100%;min-height:80px;padding:8px;border:1px solid #7C3AED;border-radius:8px;font-size:14px;resize:vertical;margin:4px 12px;box-sizing:border-box';
  var btnWrap=document.createElement('div');
  btnWrap.style.cssText='display:flex;gap:8px;padding:4px 12px 8px;justify-content:flex-end';
  btnWrap.innerHTML='<button onclick="cancelEdit('+pid+')" style="padding:6px 16px;border:1px solid #ddd;border-radius:6px;background:#fff;cursor:pointer">Hủy</button><button onclick="saveEdit('+pid+')" style="padding:6px 16px;border:none;border-radius:6px;background:#7C3AED;color:#fff;cursor:pointer;font-weight:600">Lưu</button>';
  contentEl.style.display='none';
  contentEl.parentNode.insertBefore(ta,contentEl.nextSibling);
  contentEl.parentNode.insertBefore(btnWrap,ta.nextSibling);
  ta.focus();
}
function cancelEdit(pid){
  var card=document.getElementById('post-'+pid);
  if(!card)return;
  var ta=card.querySelector('textarea');
  var btnWrap=ta?ta.nextElementSibling:null;
  if(ta)ta.remove();
  if(btnWrap)btnWrap.remove();
  var contentEl=card.querySelector('.post-content');
  if(contentEl)contentEl.style.display='';
}
function saveEdit(pid){
  var card=document.getElementById('post-'+pid);
  if(!card)return;
  var ta=card.querySelector('textarea');
  if(!ta||!ta.value.trim())return;
  var token=localStorage.getItem('token');
  fetch('/api/posts.php?action=edit',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({post_id:pid,content:ta.value.trim()})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){
      var contentEl=card.querySelector('.post-content');
      contentEl.textContent=ta.value.trim();
      cancelEdit(pid);
      toast('Đã cập nhật bài viết');
    }else{
      alert(d.message||'Lỗi');
    }
  });
}



function muteUser(uid){
  document.querySelectorAll('.post-menu').forEach(function(el){el.style.display='none';});
  var token=localStorage.getItem('token');
  fetch('/api/social.php?action=mute',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({user_id:uid})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){toast(d.message,'success');if(d.data&&d.data.muted)location.reload();}
  }).catch(function(){toast('Lỗi','error');});
}

function blockUser(uid){
  if(!confirm('Chặn người dùng này? Bạn sẽ không thấy bài viết của họ.'))return;
  document.querySelectorAll('.post-menu').forEach(function(el){el.style.display='none';});
  var token=localStorage.getItem('token');
  fetch('/api/social.php?action=block',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({user_id:uid})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){toast(d.message,'success');if(d.data&&d.data.blocked)location.reload();}
    else{toast(d.message||'Lỗi','error');}
  }).catch(function(){toast('Lỗi kết nối','error');});
}



function copyLink(pid){
  document.querySelectorAll('.post-menu').forEach(function(el){el.style.display='none';});
  var url=location.origin+'/post-detail.html?id='+pid;
  if(navigator.clipboard&&navigator.clipboard.writeText){
    navigator.clipboard.writeText(url).then(function(){toast('Đã sao chép liên kết!','success');});
  }else{
    var t=document.createElement('textarea');t.value=url;t.style.cssText='position:fixed;opacity:0';document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);
    toast('Đã sao chép liên kết!','success');
  }
}

function shareToGroup(pid){
  var token=localStorage.getItem('token');
  if(!token){toast('Đăng nhập!');return;}
  // Fetch user's groups
  fetch('/api/groups.php?action=my_groups',{headers:{'Authorization':'Bearer '+token}})
    .then(function(r){return r.json()})
    .then(function(d){
      var groups=(d.data&&d.data.groups)?d.data.groups:(d.data||[]);
      if(!groups.length){toast('Bạn chưa tham gia nhóm nào');return;}
      var ov=document.createElement('div');
      ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
      var html='<div style="background:#fff;border-radius:12px;max-width:340px;width:90%;max-height:60vh;overflow-y:auto"><div style="padding:14px 16px;font-weight:700;font-size:16px;border-bottom:1px solid #f0f0f0">Chia sẻ vào nhóm</div>';
      groups.forEach(function(g){
        var icon=g.icon_image?'<img src="'+g.icon_image+'" style="width:36px;height:36px;border-radius:8px;object-fit:cover">':'<div style="width:36px;height:36px;border-radius:8px;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center">👥</div>';
        html+='<div onclick="doShareToGroup('+pid+','+g.id+',this.parentNode.parentNode)" style="display:flex;align-items:center;gap:10px;padding:10px 16px;cursor:pointer;border-bottom:1px solid #f8f8f8">'+icon+'<div style="flex:1"><div style="font-weight:600;font-size:14px">'+esc(g.name)+'</div><div style="font-size:12px;color:#999">'+(g.member_count||0)+' thành viên</div></div></div>';
      });
      html+='<div onclick="this.parentNode.parentNode.remove()" style="padding:12px;text-align:center;color:#999;cursor:pointer;font-size:14px">Hủy</div></div>';
      ov.innerHTML=html;
      ov.onclick=function(e){if(e.target===ov)ov.remove();};
      document.body.appendChild(ov);
    });
}
function doShareToGroup(pid,gid,overlay){
  var token=localStorage.getItem('token');
  fetch('/api/groups.php?action=share_post',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({post_id:pid,group_id:gid})})
    .then(function(r){return r.json()})
    .then(function(d){
      if(overlay)overlay.remove();
      toast(d.success?'Đã chia sẻ!':'Lỗi: '+(d.message||''),'success');
    });
}


function loadPoll(postId, container){
  fetch('/api/polls.php?action=results&post_id='+postId)
    .then(function(r){return r.json()})
    .then(function(d){
      if(!d.success||!d.data||!d.data.options)return;
      var p=d.data;
      var total=p.total_votes||0;
      var html='<div class="poll-box" style="padding:8px 12px;border-top:1px solid #f0f0f0"><div style="font-weight:600;font-size:14px;margin-bottom:8px">'+esc(p.question||'Khảo sát')+'</div>';
      p.options.forEach(function(opt){
        var pct=total>0?Math.round(opt.vote_count/total*100):0;
        var isVoted=p.user_vote===opt.id;
        html+='<div onclick="votePoll('+p.poll_id+','+opt.id+','+postId+')" style="position:relative;padding:8px 12px;margin:4px 0;border-radius:8px;cursor:pointer;border:1px solid '+(isVoted?'#7C3AED':'#e4e6eb')+';overflow:hidden"><div style="position:absolute;left:0;top:0;bottom:0;width:'+pct+'%;background:'+(isVoted?'rgba(124,58,237,.12)':'#f5f5f5')+';border-radius:8px;transition:width .3s"></div><div style="position:relative;display:flex;justify-content:space-between;align-items:center"><span style="font-size:13px;'+(isVoted?'font-weight:600;color:#7C3AED':'')+'">'+esc(opt.text)+'</span><span style="font-size:12px;color:#65676B">'+pct+'%</span></div></div>';
      });
      html+='<div style="font-size:11px;color:#999;margin-top:6px">'+total+' phiếu'+(p.expired?' · Đã kết thúc':'')+'</div></div>';
      container.innerHTML=html;
    }).catch(function(){});
}
function votePoll(pollId,optId,postId){
  var token=localStorage.getItem('token');
  if(!token){toast('Đăng nhập!');return;}
  fetch('/api/polls.php?action=vote',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({poll_id:pollId,option_id:optId})})
    .then(function(r){return r.json()})
    .then(function(d){
      if(d.success){
        var card=document.getElementById('P'+postId);
        if(card){var pb=card.querySelector('.poll-box');if(pb)loadPoll(postId,pb.parentNode);}
      }else{toast(d.message||'Lỗi','error');}
    });
}

function togMenu(pid){
  // Close all menus first
  document.querySelectorAll('.post-menu').forEach(function(el){el.style.display='none';});
  document.querySelectorAll('.post-menu-overlay').forEach(function(el){el.remove();});
  
  var menu=document.getElementById('pm'+pid);
  if(!menu)return;
  
  if(menu.style.display==='none'||!menu.style.display){
    menu.style.display='block';
    // Mobile overlay
    if(window.innerWidth<769){
      var ov=document.createElement('div');
      ov.className='post-menu-overlay';
      ov.onclick=function(){menu.style.display='none';ov.remove();};
      document.body.appendChild(ov);
    }
  }else{
    menu.style.display='none';
  }
}


/* Report post */
function reportP(pid){
  togMenu(pid);
  var reasons=['Spam/Quảng cáo','Nội dung không phù hợp','Thông tin sai lệch','Quấy rối/Bắt nạt','Khác'];
  var html='<div style="padding:16px"><h3 style="margin:0 0 12px;font-size:16px">Báo cáo bài viết</h3>';
  reasons.forEach(function(r,i){
    html+='<div onclick="submitReport('+pid+',this.textContent)" style="padding:10px 12px;margin:4px 0;border:1px solid #e4e6eb;border-radius:8px;cursor:pointer;font-size:14px">'+r+'</div>';
  });
  html+='<button onclick="this.parentNode.parentNode.remove()" style="width:100%;margin-top:12px;padding:10px;border:1px solid #ddd;border-radius:8px;background:#fff;font-size:14px;cursor:pointer">Hủy</button></div>';
  var ov=document.createElement('div');
  ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
  ov.innerHTML='<div style="background:#fff;border-radius:12px;max-width:340px;width:90%">'+html+'</div>';
  ov.onclick=function(e){if(e.target===ov)ov.remove();};
  document.body.appendChild(ov);
}
function submitReport(pid,reason){
  document.querySelector('[style*="z-index:2000"]').remove();
  var token=localStorage.getItem('token');
  fetch('/api/posts.php?action=report',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({post_id:pid,reason:reason})}).then(function(r){return r.json()}).then(function(d){
    toast(d.message||'Đã báo cáo','success');
  }).catch(function(){toast('Lỗi kết nối','error');});
}

/* Delete post */
async function delP(pid){
  if(!confirm('Xóa bài viết này?'))return;
  togMenu(pid);
  try{
    var r=await fetch('/api/posts.php?action=delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:pid})});
    var d=await r.json();
    if(d.success){
      var el=document.getElementById('P'+pid)||document.getElementById('gp'+pid);
      if(el)el.style.display='none';
      toast('Đã xóa bài viết','success');
    }else{toast(d.message||'Lỗi','error');}
  }catch(e){toast('Lỗi','error');}
}

/* Close menus on outside click */
document.addEventListener('click',function(e){
  if(!e.target.closest('.post-dots')&&!e.target.closest('.post-menu')){
    document.querySelectorAll('.post-menu').forEach(function(m){m.style.display='none';});
  }
});

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
  s.textContent=".gc-ov{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1100;display:flex;align-items:flex-end;justify-content:center;opacity:0;transition:opacity .25s;-webkit-backdrop-filter:blur(2px);backdrop-filter:blur(2px);touch-action:none;overscroll-behavior:contain;}"
  +".gc-ov.open{opacity:1;}"
  +".gc-sh{position:fixed;top:44px;bottom:0;left:0;right:0;background:#fff;width:100%;max-width:600px;margin:0 auto;border-radius:16px 16px 0 0;display:flex;flex-direction:column;transform:translateY(100%);transition:transform .3s cubic-bezier(.32,.72,0,1);overflow:hidden;box-shadow:0 -4px 20px rgba(0,0,0,.15);}"
  +".gc-ov.open .gc-sh{transform:translateY(0);}.gc-ov::after{content:'';position:fixed;bottom:0;left:0;right:0;height:60px;background:#fff;z-index:-1;}"
  +".gc-hd{display:flex;align-items:center;padding:12px 16px;border-bottom:1px solid #e4e6eb;min-height:48px;}"
  +".gc-hd h3{flex:1;font-size:16px;font-weight:700;margin:0;}"
  +".gc-x{width:32px;height:32px;border-radius:50%;background:#e4e6eb;border:none;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;}"
  +".gc-body{flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;padding-bottom:8px;}"
  +".gc-post-author{display:flex;gap:10px;padding:12px 16px 0;align-items:center;}"
  +".gc-av{width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;}"
  +".gc-av-ph{width:40px;height:40px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}"
  +".gc-content{padding:8px 16px;font-size:15px;line-height:1.6;white-space:pre-wrap;word-break:break-word;}"
  +".gc-imgs{width:100%;}.gc-imgs img{width:100%;display:block;}"
  +".gc-stats{display:flex;padding:4px 12px;font-size:10px;color:#65676B;}"
  +".gc-stats span{flex:1;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;}"
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
  +".gc-cmt-reply{padding-left:20px;border-left:2px solid #e4e6eb;margin-left:14px;}"
  +".gc-bar{display:flex;gap:8px;padding:10px 16px calc(10px + env(safe-area-inset-bottom));border-top:1px solid #e4e6eb;background:#fff;align-items:center;flex-shrink:0;}"
  +".gc-bar-av{width:28px;height:28px;border-radius:50%;flex-shrink:0;}"
  +".gc-input{flex:1;padding:8px 14px;border-radius:20px;border:1px solid #e4e6eb;font-size:14px;outline:none;font-family:inherit;}"
  +".gc-input:focus{border-color:#7C3AED;}"
  +".gc-send{background:#7C3AED;color:#fff;border:none;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;}"
  +".gc-send:disabled{opacity:.4;}"
  +".gc-rpl{font-size:12px;color:#65676B;padding:0 16px 4px;display:none;}"
  +".gc-rpl.show{display:flex;align-items:center;gap:6px;}"
  +".gc-rpl a{color:#7C3AED;font-weight:700;cursor:pointer;text-decoration:none;}.gc-sh .post-menu{position:fixed;right:16px;top:auto;z-index:1200;transform:translateY(-100%);}"
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
  +"<div class='gc-bar' id='gcBar' style='display:none'><input class='gc-input' id='gcInput' placeholder='Vi\u1ebft ghi ch\u00fa...' onkeydown='if(event.key===\"Enter\"&&!event.shiftKey){event.preventDefault();sendGC();}'><button class='gc-send' id='gcSend' onclick='sendGC()'><i class='fas fa-paper-plane'></i></button></div>";
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
    if(p.images){try{var a=JSON.parse(p.images);if(a&&a.length){imgs="<div class='gc-imgs'>";for(var i=0;i<a.length;i++){imgs+="<img src='"+a[i]+"' loading='lazy' onerror='this.remove()'>";}imgs+="</div>";}}catch(x){}}
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
  fetch("/api/posts.php?action=comments&post_id="+pid+"&_t="+Date.now(),{credentials:"include"}).then(function(r){return r.json();}).then(function(d){
    var _raw=d.data; var flat=Array.isArray(_raw)?_raw:(_raw&&_raw.comments?_raw.comments:[]); if(!d.success||!flat.length){wrap.innerHTML="<div class='gc-empty'>Chưa có ghi chú</div>";return;}
    var map={},list=[];
    for(var i=0;i<flat.length;i++){map[flat[i].id]=flat[i];}
    function addF(cc,depth,pn){list.push({c:cc,dp:Math.min(depth,1),rpl:depth>0?pn:null});for(var x=0;x<flat.length;x++){if(flat[x].parent_id===cc.id)addF(flat[x],depth+1,cc.user_name);}}
    for(var j=0;j<flat.length;j++){if(!flat[j].parent_id||flat[j].parent_id<=0||!map[flat[j].parent_id])addF(flat[j],0,null);}
    var html="";for(var k=0;k<list.length;k++){html+=_gcCmt(list[k].c,list[k].dp,list[k].rpl);}
    wrap.innerHTML=html;
  }).catch(function(){wrap.innerHTML="";});
}

function _gcCmt(c,dp,rplTo){
  var sz=dp>0?22:28;
  var av=c.user_avatar?"<img class='gc-cmt-av' src='"+esc(c.user_avatar)+"' style='width:"+sz+"px;height:"+sz+"px'>":"<div class='gc-cmt-av-ph' style='width:"+sz+"px;height:"+sz+"px'>"+esc((c.user_name||"U")[0])+"</div>";
  var lk=c.user_vote==="up"||c.user_liked;
  var n=c.likes_count||0;
  var sn=esc(c.user_name||"");
  var rplTag=rplTo?"<span style='color:#7C3AED;font-size:11px'>\u21a9 "+esc(rplTo)+"</span> ":"";
  var indent=dp>0?"padding-left:20px;margin-left:14px;border-left:2px solid #e4e6eb":"";
  return "<div class='gc-cmt' style='"+indent+"'>"+av+"<div style='flex:1;min-width:0'><div class='gc-bubble'><div class='gc-cmt-name'>"+sn+"</div><div class='gc-cmt-text'>"+rplTag+esc(c.content)+"</div></div><div class='gc-cmt-meta'><span>"+ago(c.created_at)+"</span><a class='"+(lk?"liked":"")+"' onclick='_gcLkCmt("+c.id+",this)'>Th\u00e0nh c\u00f4ng"+(n>0?" \u00b7 "+n:"")+"</a><a onclick='_gcSetRpl("+c.id+",\""+sn.replace(/"/g,"&quot;")+"\")'>Ghi ch\u00fa</a></div></div></div>";
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
      _gcLoadCmts(_gcPid);try{xpToast("+5 XP Ghi chú");}catch(e){}
      // Update feed counter
      var card=document.getElementById("P"+_gcPid);
      if(card){var nc=card.querySelector("#nc"+_gcPid);if(nc){/* keep text */}var fs=card.querySelectorAll(".pa3-stats span");if(fs[1]){var cur=parseInt(fs[1].textContent)||0;fs[1].textContent=(cur+1)+" ghi chú";}}
      // Update sheet stats
      var ss=document.getElementById("gcStats");
      if(ss){var spans=ss.querySelectorAll("span");if(spans[1]){var c2=parseInt(spans[1].textContent)||0;spans[1].textContent=(c2+1)+" ghi chú";}}
    }else{inp.value=ct;toast(d.message||"Lỗi","error");}
  }).catch(function(){inp.value=ct;toast("Lỗi kết nối","error");});
}

/* ========================================
   UPGRADE PROMPT - shown when user hits free limit
   ======================================== */
function showUpgradePrompt(msg){
  var existing=document.getElementById("ssUpgradeOvl");
  if(existing)existing.remove();
  var ovl=document.createElement("div");
  ovl.id="ssUpgradeOvl";
  ovl.style.cssText="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;animation:ssUpFade .2s ease";
  ovl.onclick=function(e){if(e.target===ovl)ovl.remove();};
  var modal=document.createElement("div");
  modal.style.cssText="background:#fff;border-radius:16px;max-width:360px;width:100%;overflow:hidden;transform:scale(.9);animation:ssUpScale .25s ease forwards";
  var head=document.createElement("div");
  head.style.cssText="background:linear-gradient(135deg,#7C3AED,#5B21B6);color:#fff;padding:20px;text-align:center";
  head.innerHTML="<div style='font-size:32px;margin-bottom:8px'>\uD83D\uDC9C</div><div style='font-size:17px;font-weight:700'>Shipper Plus</div><div style='font-size:13px;opacity:.85;margin-top:4px'>29.000\u0111/th\u00e1ng</div>";
  var body=document.createElement("div");
  body.style.cssText="padding:16px 20px;text-align:center";
  body.innerHTML="<div style='font-size:14px;color:#333;line-height:1.6;margin-bottom:16px'>"+esc(msg)+"</div><div style='font-size:12px;color:#65676B;margin-bottom:16px'>N\u00e2ng c\u1ea5p \u0111\u1ec3 m\u1edf kh\u00f3a t\u1ea5t c\u1ea3 t\u00ednh n\u0103ng kh\u00f4ng gi\u1edbi h\u1ea1n</div>";
  var btnWrap=document.createElement("div");
  btnWrap.style.cssText="display:flex;gap:8px;padding:0 20px 20px";
  var closeBtn=document.createElement("button");
  closeBtn.style.cssText="flex:1;padding:10px;border-radius:10px;border:1.5px solid #e0e0e0;background:#fff;color:#333;font-size:14px;font-weight:600;cursor:pointer";
  closeBtn.textContent="\u0110\u1ec3 sau";
  closeBtn.onclick=function(){ovl.remove();};
  var upgradeBtn=document.createElement("button");
  upgradeBtn.style.cssText="flex:1;padding:10px;border-radius:10px;border:none;background:#7C3AED;color:#fff;font-size:14px;font-weight:700;cursor:pointer";
  upgradeBtn.textContent="N\u00e2ng c\u1ea5p ngay";
  upgradeBtn.onclick=function(){location.href="/wallet.html";};
  btnWrap.appendChild(closeBtn);
  btnWrap.appendChild(upgradeBtn);
  modal.appendChild(head);
  modal.appendChild(body);
  modal.appendChild(btnWrap);
  ovl.appendChild(modal);
  document.body.appendChild(ovl);
  // Inject animation CSS
  if(!document.getElementById("ssUpCSS")){
    var st=document.createElement("style");
    st.id="ssUpCSS";
    st.textContent="@keyframes ssUpFade{from{opacity:0}to{opacity:1}}@keyframes ssUpScale{from{transform:scale(.9);opacity:0}to{transform:scale(1);opacity:1}}";
    document.head.appendChild(st);
  }
}

/* Check if error is an upgrade prompt */
function handleApiError(d, fallbackMsg){
  var msg=d.message||fallbackMsg||"Lỗi";
  if(d.upgrade||msg.indexOf("Shipper Plus")>-1||msg.indexOf("Nâng cấp")>-1){
    showUpgradePrompt(msg);
    return true;
  }
  return false;
}
// Touch swipe for carousel
document.addEventListener('touchstart',function(e){
  var c=e.target.closest('.carousel');
  if(!c)return;
  c._touchX=e.touches[0].clientX;
},{passive:true});
document.addEventListener('touchend',function(e){
  var c=e.target.closest('.carousel');
  if(!c||!c._touchX)return;
  var diff=e.changedTouches[0].clientX-c._touchX;
  if(Math.abs(diff)>40){slideCarousel(c,diff<0?1:-1);}
  c._touchX=null;
},{passive:true});
