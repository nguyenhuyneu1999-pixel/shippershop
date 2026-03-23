(function(){
function _esc(s){if(!s)return '';var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML;}
var CU=JSON.parse(localStorage.getItem("user")||"null");
var SC={"GHTK":"#00b14f","J&T":"#d32f2f","GHN":"#ff6600","Viettel Post":"#e21a1a","SPX":"#EE4D2D","Grab":"#00b14f","Be":"#5bc500","Gojek":"#00aa13"};
var spmFiles=[],spmType="post";

var css=document.createElement("style");
css.textContent=".spm-overlay{position:fixed;inset:0;background:#fff;z-index:9999;display:none;flex-direction:column;}"
+".spm-overlay.open{display:flex;}"
+".spm-head{display:flex;align-items:center;padding:12px 16px;border-bottom:1px solid #e4e6eb;background:#fff;flex-shrink:0;}"
+".spm-back{background:none;border:none;font-size:20px;cursor:pointer;color:#333;padding:4px 8px 4px 0;}"
+".spm-head h3{flex:1;font-size:17px;font-weight:700;margin:0;}"
+".spm-post-btn{background:#7C3AED;color:#fff;border:none;border-radius:8px;padding:8px 20px;font-size:14px;font-weight:700;cursor:pointer;}"
+".spm-post-btn:disabled{opacity:.4;}"
+".spm-schedule{display:flex;align-items:center;gap:8px;padding:8px 16px;font-size:13px;color:#65676B}"
+".spm-schedule input{border:1px solid #ddd;border-radius:6px;padding:4px 8px;font-size:13px}"

+".spm-user-row{display:flex;align-items:center;gap:12px;padding:16px 16px 8px;}"
+".spm-av{width:44px;height:44px;border-radius:50%;object-fit:cover;flex-shrink:0;}"
+".spm-av-ph{width:44px;height:44px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;flex-shrink:0;}"
+".spm-uname{font-weight:700;font-size:15px;}"
+".spm-ship{font-size:11px;font-weight:700;}"
+".spm-tags{display:flex;gap:6px;margin-top:4px;flex-wrap:wrap;}"
+".spm-tag{padding:3px 10px;border-radius:14px;font-size:11px;font-weight:600;border:1px solid #ddd;background:#f0f2f5;color:#333;cursor:pointer;display:flex;align-items:center;gap:4px;}"
+".spm-tag.sel{background:#EDE9FE;border-color:#7C3AED;color:#7C3AED;}"
+".spm-body{flex:1;overflow-y:auto;padding:0 16px;}"
+".spm-ta{width:100%;min-height:200px;border:none;outline:none;font-size:18px;resize:none;font-family:inherit;line-height:1.6;color:#1C1C1C;padding:8px 0;}"
+".spm-ta::placeholder{color:#B0B3B8;font-size:18px;}"
+".spm-media{display:flex;gap:8px;flex-wrap:wrap;padding:8px 0;}"
+".spm-thumb{position:relative;border-radius:12px;overflow:hidden;}"
+".spm-thumb img,.spm-thumb video{width:100px;height:100px;object-fit:cover;display:block;}"
+".spm-rm{position:absolute;top:6px;right:6px;width:24px;height:24px;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;border:none;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;}"
+".spm-toolbar{display:flex;align-items:center;padding:12px 16px;border-top:1px solid #e4e6eb;background:#fff;flex-shrink:0;gap:4px;}"
+".spm-tool{width:40px;height:40px;border-radius:50%;border:none;background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:20px;}"
+".spm-tool:active{background:#f0f2f5;}"
+".spm-divider{flex:1;}"
+".spm-charcount{font-size:11px;color:#B0B3B8;}"
+".spm-picker{position:fixed;bottom:0;left:0;right:0;z-index:10000;display:none;}"
+".spm-picker.open{display:block;}"
+".spm-picker-bg{position:fixed;inset:0;background:rgba(0,0,0,.4);}"
+".spm-picker-sheet{position:absolute;bottom:0;left:0;right:0;background:#fff;border-radius:16px 16px 0 0;padding:8px 0 calc(8px + env(safe-area-inset-bottom));transform:translateY(100%);transition:transform .3s cubic-bezier(.32,.72,0,1);}"
+".spm-picker.open .spm-picker-sheet{transform:translateY(0);}"
+".spm-picker-item{display:flex;align-items:center;gap:16px;padding:16px 20px;cursor:pointer;font-size:16px;color:#333;}"
+".spm-picker-item:active{background:#f5f5f5;}"
+".spm-picker-item i{width:28px;text-align:center;font-size:22px;color:#65676B;}"
+".spm-picker-close{position:absolute;top:12px;right:16px;width:36px;height:36px;border-radius:50%;background:#e4e6eb;border:none;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;}";
document.head.appendChild(css);

var d=document.createElement("div");
d.innerHTML='<div class="spm-overlay" id="spmOverlay">'
+'<div class="spm-head">'
+'<button class="spm-back" onclick="closeSPM()"><i class="fas fa-arrow-left"></i></button>'
+'<h3>T\u1ea1o b\u00e0i vi\u1ebft</h3>'
+'<button class="spm-post-btn" id="spmSend" disabled onclick="submitSPM()">\u0110\u0103ng</button>'
+'</div>'
+'<div class="spm-user-row">'
+'<div id="spmAvatar"></div>'
+'<div><div class="spm-uname" id="spmName"></div><div id="spmShip"></div>'
+'<div class="spm-tags" id="spmTypes">'
+'<span class="spm-tag sel" data-t="post" onclick="selType(this)"><i class="fas fa-pen-to-square"></i> B\u00e0i vi\u1ebft</span>'
+'<span class="spm-tag" data-t="review" onclick="selType(this)"><i class="fas fa-star"></i> Review</span>'
+'<span class="spm-tag" data-t="question" onclick="selType(this)"><i class="fas fa-circle-question"></i> H\u1ecfi \u0111\u00e1p</span>'
+'<span class="spm-tag" data-t="tip" onclick="selType(this)"><i class="fas fa-lightbulb"></i> M\u1eb9o</span>'
+'</div></div></div>'
+'<div class="spm-body">'
+'<textarea class="spm-ta" id="spmText" placeholder="B\u1ea1n \u0111ang ngh\u0129 g\u00ec?" oninput="document.getElementById(\'spmSend\').disabled=!this.value.trim()"></textarea>'
+'<div class="spm-media" id="spmMedia"></div>'
+'</div>'
+'<div class="spm-toolbar">'
+'<button class="spm-tool" onclick="openMediaPicker(\'image\')" title="\u1ea2nh"><i class="fas fa-image" style="color:#45BD62"></i></button>'
+'<button class="spm-tool" onclick="openMediaPicker(\'video\')" title="Video"><i class="fas fa-video" style="color:#E74C3C"></i></button>'
+'<button class="spm-tool" title="Emoji" onclick="insertSpmEmoji()"><i class="far fa-face-smile" style="color:#F7B928"></i></button>'
+'<button class="spm-tool" title="V\u1ecb tr\u00ed" onclick="addSpmLocation()"><i class="fas fa-location-dot" style="color:#E74C3C"></i></button>'
+'<button class="spm-tool" title="#Hashtag" onclick="addSpmHashtag()"><i class="fas fa-hashtag" style="color:#7C3AED"></i></button>'
+'<div class="spm-divider"></div>'
+'<span class="spm-charcount" id="spmCount">0</span>'
+'</div>'
+'</div>'
+'<div class="spm-picker" id="spmPicker" onclick="if(event.target.classList.contains(\'spm-picker-bg\'))closeMediaPicker()">'
+'<div class="spm-picker-bg"></div>'
+'<div class="spm-picker-sheet">'
+'<button class="spm-picker-close" onclick="closeMediaPicker()"><i class="fas fa-times"></i></button>'
+'<div class="spm-picker-item" onclick="pickCamera()"><i class="fas fa-camera"></i> Ch\u1ee5p \u1ea3nh</div>'
+'<div class="spm-picker-item" onclick="pickGallery()"><i class="fas fa-images"></i> Th\u01b0 vi\u1ec7n \u1ea3nh</div>'
+'<div class="spm-picker-item" id="spmPickerVideo" style="display:none" onclick="pickVideoFile()"><i class="fas fa-film"></i> Ch\u1ecdn video</div>'
+'</div>'
+'</div>'
+'<input type="file" id="spmImg" style="display:none" accept="image/*" multiple>'
+'<input type="file" id="spmCam" style="display:none" accept="image/*" capture="environment">'
+'<input type="file" id="spmVid" style="display:none" accept="video/*">';
if(document.body)document.body.appendChild(d);

document.getElementById("spmImg").onchange=function(){addSPMFiles(this);closeMediaPicker();};
document.getElementById("spmCam").onchange=function(){addSPMFiles(this);closeMediaPicker();};
document.getElementById("spmVid").onchange=function(){addSPMFiles(this);closeMediaPicker();};

var _pickerType="image";
window.openMediaPicker=function(type){
  _pickerType=type||"image";
  var vidItem=document.getElementById("spmPickerVideo");
  if(type==="video"){vidItem.style.display="flex";}else{vidItem.style.display="none";}
  var pk=document.getElementById("spmPicker");
  pk.style.display="block";
  requestAnimationFrame(function(){pk.classList.add("open");});
};
window.closeMediaPicker=function(){
  var pk=document.getElementById("spmPicker");
  pk.classList.remove("open");
  setTimeout(function(){pk.style.display="none";},300);
};
window.pickCamera=function(){
  document.getElementById("spmCam").click();
};
window.pickGallery=function(){
  document.getElementById("spmImg").click();
};
window.pickVideoFile=function(){
  document.getElementById("spmVid").click();
};

document.getElementById("spmText").addEventListener("input",function(){
  this.style.height="auto";
  this.style.height=Math.max(200,this.scrollHeight)+"px";
  document.getElementById("spmCount").textContent=this.value.length;
});

window.openSPM=function(){
  if(!CU){location.href="login.html";return;}
  var avEl=document.getElementById("spmAvatar");
  avEl.innerHTML=CU.avatar?"<img class='spm-av' src='"+_esc(CU.avatar)+"'>":"<div class='spm-av-ph'>"+_esc((CU.fullname||"?")[0])+"</div>";
  document.getElementById("spmName").textContent=CU.fullname;
  var shipEl=document.getElementById("spmShip");
  if(CU.shipping_company){shipEl.innerHTML="<span class='spm-ship' style='color:"+(SC[CU.shipping_company]||"#999")+"'>"+_esc(CU.shipping_company)+"</span>";}
  else{shipEl.innerHTML="";}
  document.getElementById("spmOverlay").classList.add("open");
  document.body.style.overflow="hidden";
  setTimeout(function(){document.getElementById("spmText").focus();},200);
};

window.closeSPM=function(){window._spmGroupId=null;
  document.getElementById("spmOverlay").classList.remove("open");
  document.body.style.overflow="";
  document.getElementById("spmText").value="";
  document.getElementById("spmText").style.height="200px";
  document.getElementById("spmMedia").innerHTML="";
  document.getElementById("spmCount").textContent="0";
  document.getElementById("spmSend").disabled=true;
  spmFiles=[];spmType="post";
  setTimeout(function(){var ta=document.getElementById('spmText');if(ta){setupMention(ta);ta.focus();}var md=document.getElementById('spmModal');if(md)trapFocus(md);},200);
  loadDraft();startDraftSave();
  createFormatToolbar();
  document.querySelectorAll(".spm-tag").forEach(function(t){t.classList.remove("sel");});
  var first=document.querySelector(".spm-tag[data-t='post']");if(first)first.classList.add("sel");
};

window.selType=function(el){
  document.querySelectorAll(".spm-tag").forEach(function(t){t.classList.remove("sel");});
  el.classList.add("sel");spmType=el.getAttribute("data-t");
};

window.addSPMFiles=function(input){
  if(!input.files)return;
  for(var i=0;i<input.files.length;i++){
    var f=input.files[i];
        // Compress image client-side
        if(f.type.indexOf('image')>-1&&f.size>200000){
            (function(file,fileIdx){
                var reader=new FileReader();
                reader.onload=function(e){
                    var img=new Image();
                    img.onload=function(){
                        var w=img.width,h=img.height,maxW=1200;
                        if(w>maxW){h=Math.round(h*maxW/w);w=maxW;}
                        var c=document.createElement('canvas');c.width=w;c.height=h;
                        c.getContext('2d').drawImage(img,0,0,w,h);
                        c.toBlob(function(blob){
                            if(blob&&blob.size<file.size){spmFiles[fileIdx]=new File([blob],file.name,{type:'image/jpeg'});}
                        },'image/jpeg',0.82);
                    };
                    img.src=e.target.result;
                };
                reader.readAsDataURL(file);
            })(f,spmFiles.length-1);
        }var idx=spmFiles.length-1;
    var el=document.createElement("div");el.className="spm-thumb";
    if(f.type.indexOf("image")>-1){el.innerHTML="<img src='"+URL.createObjectURL(f)+"'><button class='spm-rm' onclick='rmSPMFile("+idx+",this.parentElement)'><i class='fas fa-times'></i></button>";}
    else{el.innerHTML="<video src='"+URL.createObjectURL(f)+"'></video><button class='spm-rm' onclick='rmSPMFile("+idx+",this.parentElement)'><i class='fas fa-times'></i></button>";}
    document.getElementById("spmMedia").appendChild(el);
  }input.value="";
};

window.rmSPMFile=function(idx,el){spmFiles[idx]=null;el.remove();};

window.insertSpmEmoji=function(){
  var ta=document.getElementById("spmText");
  var emojis=["\ud83d\ude0a","\ud83d\udc4d","\ud83d\udeb5","\ud83d\udce6","\ud83d\udcaa","\ud83d\udd25","\u2764\ufe0f","\u2705","\u2b50","\ud83c\udf89"];
  ta.value+=emojis[Math.floor(Math.random()*emojis.length)];
  ta.focus();
  document.getElementById("spmSend").disabled=!ta.value.trim();
  document.getElementById("spmCount").textContent=ta.value.length;
};

window.addSpmLocation=function(){
  var ta=document.getElementById("spmText");
  if(navigator.geolocation){
    navigator.geolocation.getCurrentPosition(function(pos){
      ta.value+="\n\ud83d\udccd "+pos.coords.latitude.toFixed(4)+", "+pos.coords.longitude.toFixed(4);
      ta.focus();
      document.getElementById("spmSend").disabled=!ta.value.trim();
    });
  }
};

window.addSpmHashtag=function(){
  var ta=document.getElementById("spmText");
  ta.value+=" #";ta.focus();
  document.getElementById("spmSend").disabled=!ta.value.trim();
};

window.submitSPM=function(){
  var ct=document.getElementById("spmText").value.trim();
  if(!ct)return;
  var btn=document.getElementById("spmSend");btn.disabled=true;btn.textContent="\u0110ang \u0111\u0103ng...";
  var fd=new FormData();fd.append("content",ct);fd.append("type",spmType);
  if(_spmScheduled){var sv=document.getElementById('spmSchedInput');if(sv)fd.append('scheduled_at',sv.value);}
  spmFiles.forEach(function(f){if(f){if(f.type.indexOf("video")>-1)fd.append("video",f);else fd.append("images[]",f);}});
  var tk=localStorage.getItem("token");var hdrs={};if(tk)hdrs["Authorization"]="Bearer "+tk;
  (function(){
    var url="/api/posts.php";
    if(window._spmGroupId){
      fd.append("group_id",window._spmGroupId);
      url="/api/groups.php?action=post";
    }
    return fetch(url,{method:"POST",headers:hdrs,credentials:"include",body:fd});
  })()
  .then(function(r){return r.json();})
  .then(function(d){if(d.success){closeSPM();if(typeof loadPosts==="function")loadPosts();else clearDraft();location.reload();}else{if(typeof handleApiError==="function"&&handleApiError(d)){}else if(typeof toast==="function")toast(d.message||"L\u1ed7i","error");}})
  .catch(function(){if(typeof toast==="function")toast("L\u1ed7i k\u1ebft n\u1ed1i","error");})
  .finally(function(){btn.disabled=false;btn.textContent="\u0110\u0103ng";});
};


// Auto-save draft every 10s
var _draftTimer=null;
function startDraftSave(){
    if(_draftTimer)clearInterval(_draftTimer);
    _draftTimer=setInterval(function(){
        var txt=document.getElementById('spmText');
        if(txt&&txt.value.trim()){
            localStorage.setItem('ss_draft',JSON.stringify({content:txt.value,type:spmType,ts:Date.now()}));
        }
    },10000);
}
function loadDraft(){
    var raw=localStorage.getItem('ss_draft');
    if(!raw)return;
    try{
        var d=JSON.parse(raw);
        if(d.content&&(Date.now()-d.ts)<86400000){
            var txt=document.getElementById('spmText');
            if(txt&&!txt.value){txt.value=d.content;if(d.type)spmType=d.type;}
        }
    }catch(e){}
}
function clearDraft(){localStorage.removeItem('ss_draft');}


// @mention autocomplete
var _mentionQuery='',_mentionEl=null;
function setupMention(textarea){
  textarea.addEventListener('input',function(e){
    var val=textarea.value;
    var pos=textarea.selectionStart;
    var before=val.substring(0,pos);
    var match=before.match(/@([a-zA-ZÀ-ỹ0-9_]{1,20})$/u);
    if(match){
      _mentionQuery=match[1];
      fetchMentions(_mentionQuery,textarea);
    } else { hideMentionPopup(); }
  });
}
function fetchMentions(q,textarea){
  fetch('/api/search.php?q='+encodeURIComponent(q)+'&type=mentions&limit=5')
    .then(function(r){return r.json()})
    .then(function(d){
      var users=(d.data&&d.data.users)?d.data.users:(d.data||[]);
      if(!users.length){hideMentionPopup();return;}
      showMentionPopup(users,textarea);
    }).catch(function(){hideMentionPopup();});
}
function showMentionPopup(users,textarea){
  hideMentionPopup();
  var rect=textarea.getBoundingClientRect();
  var popup=document.createElement('div');
  popup.id='mentionPopup';
  popup.style.cssText='position:fixed;left:'+rect.left+'px;bottom:'+(window.innerHeight-rect.top+4)+'px;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.15);z-index:10000;max-width:280px;overflow:hidden';
  users.forEach(function(u){
    var av=u.avatar?'<img src="'+u.avatar+'" style="width:30px;height:30px;border-radius:50%;object-fit:cover">':'<div style="width:30px;height:30px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">'+(u.fullname||'U')[0]+'</div>';
    var div=document.createElement('div');
    div.style.cssText='display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer';
    div.innerHTML=av+'<div><div style="font-weight:600;font-size:13px">'+u.fullname+'</div><div style="font-size:11px;color:#999">@'+u.username+'</div></div>';
    div.onclick=function(){insertMention(u,textarea);};
    popup.appendChild(div);
  });
  document.body.appendChild(popup);
  _mentionEl=popup;
}
function hideMentionPopup(){if(_mentionEl){_mentionEl.remove();_mentionEl=null;}}
function insertMention(user,textarea){
  var val=textarea.value;
  var pos=textarea.selectionStart;
  var before=val.substring(0,pos);
  var after=val.substring(pos);
  var newBefore=before.replace(/@[a-zA-ZÀ-ỹ0-9_]*$/u,'@'+user.username+' ');
  textarea.value=newBefore+after;
  textarea.selectionStart=textarea.selectionEnd=newBefore.length;
  textarea.focus();
  hideMentionPopup();
}


// Poll creation in post modal
var _spmPollEnabled=false,_spmPollOptions=[];
function togglePoll(){
  _spmPollEnabled=!_spmPollEnabled;
  var area=document.getElementById('spmPollArea');
  if(!area){
    area=document.createElement('div');
    area.id='spmPollArea';
    area.style.cssText='padding:8px 16px;display:none';
    var ta=document.getElementById('spmText');
    if(ta)ta.parentNode.insertBefore(area,ta.nextSibling);
  }
  if(_spmPollEnabled){
    _spmPollOptions=['',''];
    renderPollInputs();
    area.style.display='block';
  }else{
    area.style.display='none';
    _spmPollOptions=[];
  }
}
function renderPollInputs(){
  var area=document.getElementById('spmPollArea');
  if(!area)return;
  var html='<div style="font-weight:600;font-size:14px;margin-bottom:8px">Khảo sát</div>';
  _spmPollOptions.forEach(function(opt,i){
    html+='<input type="text" value="'+opt.replace(/"/g,'&quot;')+'" placeholder="Lựa chọn '+(i+1)+'" oninput="_spmPollOptions['+i+']=this.value" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px;margin-bottom:6px;box-sizing:border-box">';
  });
  if(_spmPollOptions.length<6){
    html+='<button onclick="addPollOption()" type="button" style="padding:6px 12px;border:1px dashed #7C3AED;border-radius:8px;background:none;color:#7C3AED;font-size:13px;cursor:pointer;width:100%">+ Thêm lựa chọn</button>';
  }
  area.innerHTML=html;
}
function addPollOption(){
  if(_spmPollOptions.length>=6)return;
  _spmPollOptions.push('');
  renderPollInputs();
}


// Schedule post
var _spmScheduled=null;
function toggleSchedule(){
  var area=document.getElementById('spmScheduleArea');
  if(!area){
    area=document.createElement('div');
    area.id='spmScheduleArea';
    area.style.cssText='padding:8px 16px;display:none';
    var pollArea=document.getElementById('spmPollArea');
    var ref=pollArea||document.getElementById('spmText');
    if(ref)ref.parentNode.insertBefore(area,ref.nextSibling);
  }
  if(area.style.display==='none'){
    var now=new Date();
    now.setHours(now.getHours()+1);
    var iso=now.toISOString().slice(0,16);
    area.innerHTML='<div style="font-weight:600;font-size:14px;margin-bottom:8px">⏰ Hẹn giờ đăng</div><input type="datetime-local" id="spmSchedInput" value="'+iso+'" min="'+new Date().toISOString().slice(0,16)+'" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box"><div style="font-size:11px;color:#999;margin-top:4px">Bài viết sẽ tự động đăng vào thời gian bạn chọn</div>';
    area.style.display='block';
    _spmScheduled=iso;
  }else{
    area.style.display='none';
    _spmScheduled=null;
  }
}


// Text formatting toolbar
function insertFormat(before, after){
  var ta=document.getElementById('spmText');
  if(!ta)return;
  var start=ta.selectionStart, end=ta.selectionEnd;
  var selected=ta.value.substring(start, end);
  var text=ta.value;
  ta.value=text.substring(0,start)+before+selected+after+text.substring(end);
  ta.selectionStart=start+before.length;
  ta.selectionEnd=start+before.length+selected.length;
  ta.focus();
}
function createFormatToolbar(){
  var bar=document.getElementById('spmFormatBar');
  if(bar)return;
  bar=document.createElement('div');
  bar.id='spmFormatBar';
  bar.style.cssText='display:flex;gap:4px;padding:4px 16px';
  bar.innerHTML='<button type="button" onclick="insertFormat(\'**\',\'**\')" style="width:28px;height:28px;border:1px solid #ddd;border-radius:6px;background:#fff;font-weight:900;cursor:pointer;font-size:13px">B</button>'
    +'<button type="button" onclick="insertFormat(\'*\',\'*\')" style="width:28px;height:28px;border:1px solid #ddd;border-radius:6px;background:#fff;font-style:italic;cursor:pointer;font-size:13px">I</button>'
    +'<button type="button" onclick="insertFormat(\'#\',\' \')" style="width:28px;height:28px;border:1px solid #ddd;border-radius:6px;background:#fff;cursor:pointer;font-size:13px">#</button>'
    +'<button type="button" onclick="togglePoll()" style="width:28px;height:28px;border:1px solid #ddd;border-radius:6px;background:#fff;cursor:pointer;font-size:13px" title="Khảo sát">📊</button>'
    +'<button type="button" onclick="toggleSchedule()" style="width:28px;height:28px;border:1px solid #ddd;border-radius:6px;background:#fff;cursor:pointer;font-size:13px" title="Hẹn giờ">⏰</button>';
  var ta=document.getElementById('spmText');
  if(ta)ta.parentNode.insertBefore(bar,ta);
}


// Focus trap for modal
function trapFocus(modal){
  var focusable=modal.querySelectorAll('button,input,textarea,select,a,[tabindex]');
  if(!focusable.length)return;
  var first=focusable[0],last=focusable[focusable.length-1];
  modal.addEventListener('keydown',function(e){
    if(e.key==='Tab'){
      if(e.shiftKey){if(document.activeElement===first){e.preventDefault();last.focus();}}
      else{if(document.activeElement===last){e.preventDefault();first.focus();}}
    }
    if(e.key==='Escape'){closeSPM();}
  });
  first.focus();
}

})();
