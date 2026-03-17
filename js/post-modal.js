(function(){
var CU=JSON.parse(localStorage.getItem("user")||"null");
var SC={"GHTK":"#00b14f","J&T":"#d32f2f","GHN":"#ff6600","Viettel Post":"#e21a1a","SPX":"#EE4D2D","Grab":"#00b14f","Be":"#5bc500"};
var spmFiles=[],spmType="post";
var css=document.createElement("style");
css.textContent=".spm-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;display:none;align-items:center;justify-content:center;padding:16px}.spm-overlay.open{display:flex}.spm-box{background:#fff;width:100%;max-width:500px;border-radius:16px;overflow:hidden;max-height:90vh;display:flex;flex-direction:column}.spm-head{display:flex;align-items:center;padding:14px 16px;border-bottom:1px solid #e4e6eb}.spm-head h3{flex:1;font-size:16px;font-weight:700;margin:0}.spm-close{background:#e4e6eb;border:none;width:32px;height:32px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center}.spm-body{padding:16px;overflow-y:auto;flex:1}.spm-user{display:flex;align-items:center;gap:10px;margin-bottom:12px}.spm-av{width:40px;height:40px;border-radius:50%;object-fit:cover}.spm-av-ph{width:40px;height:40px;border-radius:50%;background:#EE4D2D;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700}.spm-ta{width:100%;min-height:120px;border:none;outline:none;font-size:15px;resize:none;font-family:inherit;line-height:1.5}.spm-ta::placeholder{color:#65676B}.spm-media{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}.spm-thumb{position:relative}.spm-thumb img,.spm-thumb video{width:80px;height:80px;object-fit:cover;border-radius:8px}.spm-thumb .spm-rm{position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;background:#000;color:#fff;border:none;font-size:10px;cursor:pointer;display:flex;align-items:center;justify-content:center}.spm-tools{display:flex;gap:8px;padding:12px 16px;border-top:1px solid #e4e6eb;align-items:center}.spm-tool{width:36px;height:36px;border-radius:50%;border:none;background:#f0f2f5;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px}.spm-send{margin-left:auto;background:#EE4D2D;color:#fff;border:none;border-radius:20px;padding:8px 20px;font-size:14px;font-weight:700;cursor:pointer}.spm-send:disabled{opacity:.5}.spm-types{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}.spm-type{padding:4px 10px;border-radius:12px;border:1px solid #ddd;font-size:12px;cursor:pointer;background:#fff}.spm-type.sel{background:#FFF3EF;border-color:#EE4D2D;color:#EE4D2D}";
document.head.appendChild(css);
var d=document.createElement("div");
d.innerHTML='<div class="spm-overlay" id="spmOverlay" onclick="if(event.target===this)closeSPM()"><div class="spm-box"><div class="spm-head"><h3>T\u1ea1o b\u00e0i vi\u1ebft</h3><button class="spm-close" onclick="closeSPM()"><i class="fas fa-times"></i></button></div><div class="spm-body"><div class="spm-user" id="spmUser"></div><textarea class="spm-ta" id="spmText" placeholder="B\u1ea1n \u0111ang ngh\u0129 g\u00ec?"></textarea><div class="spm-types" id="spmTypes"><span class="spm-type sel" data-t="post" onclick="selType(this)">B\u00e0i vi\u1ebft</span><span class="spm-type" data-t="review" onclick="selType(this)">Review</span><span class="spm-type" data-t="question" onclick="selType(this)">H\u1ecfi \u0111\u00e1p</span><span class="spm-type" data-t="tip" onclick="selType(this)">M\u1eb9o</span><span class="spm-type" data-t="discussion" onclick="selType(this)">Th\u1ea3o lu\u1eadn</span></div><div class="spm-media" id="spmMedia"></div></div><div class="spm-tools"><button class="spm-tool" id="spmImgBtn" title="\u1ea2nh"><i class="fas fa-image" style="color:#00C853"></i></button><button class="spm-tool" id="spmVidBtn" title="Video"><i class="fas fa-video" style="color:#E74C3C"></i></button><button class="spm-send" id="spmSend" onclick="submitSPM()">\u0110\u0103ng</button></div></div></div><input type="file" id="spmImg" style="display:none" accept="image/*" multiple><input type="file" id="spmVid" style="display:none" accept="video/*">';
document.body.appendChild(d);
document.getElementById("spmImgBtn").onclick=function(){document.getElementById("spmImg").click();};
document.getElementById("spmVidBtn").onclick=function(){document.getElementById("spmVid").click();};
document.getElementById("spmImg").onchange=function(){addSPMFiles(this);};
document.getElementById("spmVid").onchange=function(){addSPMFiles(this);};
window.openSPM=function(){
if(!CU){alert("Vui l\u00f2ng \u0111\u0103ng nh\u1eadp!");location.href="login.html";return;}
var u=document.getElementById("spmUser");
var av=CU.avatar?"<img class=spm-av src='"+CU.avatar+"'>":"<div class=spm-av-ph>"+CU.fullname[0]+"</div>";
var sh=CU.shipping_company?"<span style='font-size:11px;font-weight:700;color:"+(SC[CU.shipping_company]||"#999")+"'>"+CU.shipping_company+"</span>":"";
u.innerHTML=av+"<div><b style='font-size:14px'>"+CU.fullname+"</b><br>"+sh+"</div>";
document.getElementById("spmOverlay").classList.add("open");
setTimeout(function(){document.getElementById("spmText").focus();},200);
};
window.closeSPM=function(){
document.getElementById("spmOverlay").classList.remove("open");
document.getElementById("spmText").value="";
document.getElementById("spmMedia").innerHTML="";
spmFiles=[];
};
window.selType=function(el){
document.querySelectorAll(".spm-type").forEach(function(t){t.classList.remove("sel");});
el.classList.add("sel");spmType=el.getAttribute("data-t");
};
window.addSPMFiles=function(input){
if(!input.files)return;
for(var i=0;i<input.files.length;i++){
var f=input.files[i];spmFiles.push(f);var idx=spmFiles.length-1;
var el=document.createElement("div");el.className="spm-thumb";
if(f.type.indexOf("image")>-1)el.innerHTML="<img src='"+URL.createObjectURL(f)+"'><button class=spm-rm onclick='rmSPMFile("+idx+",this.parentElement)'>x</button>";
else el.innerHTML="<video src='"+URL.createObjectURL(f)+"'></video><button class=spm-rm onclick='rmSPMFile("+idx+",this.parentElement)'>x</button>";
document.getElementById("spmMedia").appendChild(el);
}input.value="";
};
window.rmSPMFile=function(idx,el){spmFiles[idx]=null;el.remove();};
window.submitSPM=function(){
var ct=document.getElementById("spmText").value.trim();
if(!ct){alert("Nh\u1eadp n\u1ed9i dung!");return;}
var btn=document.getElementById("spmSend");btn.disabled=true;btn.textContent="\u0110ang \u0111\u0103ng...";
var fd=new FormData();fd.append("content",ct);fd.append("type",spmType);
spmFiles.forEach(function(f){if(f){if(f.type.indexOf("video")>-1)fd.append("video",f);else fd.append("images[]",f);}});
var tk=localStorage.getItem("token");var hdrs={};if(tk)hdrs["Authorization"]="Bearer "+tk;
fetch("/api/posts.php",{method:"POST",headers:hdrs,credentials:"include",body:fd})
.then(function(r){return r.json();})
.then(function(d){if(d.success){closeSPM();if(typeof loadPosts==="function")loadPosts();else location.reload();}else alert(d.message||"L\u1ed7i");})
.catch(function(){alert("L\u1ed7i k\u1ebft n\u1ed1i");})
.finally(function(){btn.disabled=false;btn.textContent="\u0110\u0103ng";});
};
})();
