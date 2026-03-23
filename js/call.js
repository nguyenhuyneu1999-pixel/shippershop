// ShipperShop Call System v2
(function(){
function _esc(s){if(!s)return '';var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML;}
var FB_CFG={apiKey:"AIzaSyDNwf6FKPX10szjFJ2Ei6YoKJWRA6NAkKs",authDomain:"shippershop-5f8d9.firebaseapp.com",databaseURL:"https://shippershop-5f8d9-default-rtdb.asia-southeast1.firebasedatabase.app",projectId:"shippershop-5f8d9"};
var ICE={iceServers:[{urls:"stun:stun.l.google.com:19302"},{urls:"stun:stun1.l.google.com:19302"}]};
var db,pc,localStream,remoteStream,callDocId,myId,callInterval,ringAudio;
var isCallActive=false,isCaller=false;
function initFB(){
if(typeof firebase==="undefined")return setTimeout(initFB,500);
if(!firebase.apps.length)firebase.initializeApp(FB_CFG);
db=firebase.database();
try{var t=localStorage.getItem("token");if(t){var p=JSON.parse(atob(t.split(".")[1]));myId=p.user_id||p.id||p.sub;}
if(!myId){var u=localStorage.getItem("user");if(u){var uo=JSON.parse(u);myId=uo.id||uo.user_id;}}}catch(e){}
// console.log("[SSCall] myId=",myId);
if(myId)listenIncoming();
}
function getMyName(){try{var u=JSON.parse(localStorage.getItem("user"));return u.fullname||u.name||"Nguoi dung";}catch(e){return "Nguoi dung";}}
function getMyAvatar(){try{var u=JSON.parse(localStorage.getItem("user"));return u.avatar||"";}catch(e){return "";}}
function createCallUI(){
if(document.getElementById("callScreen"))return;
var h="<div id=\"callScreen\" style=\"display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:linear-gradient(135deg,#1a1a2e,#16213e);z-index:99999;flex-direction:column;align-items:center;justify-content:center;color:#fff;font-family:-apple-system,sans-serif\">";
h+="<div id=\"callAvatar\" style=\"width:100px;height:100px;border-radius:50%;background:#e4e6eb;display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:700;margin-bottom:16px;overflow:hidden\"></div>";
h+="<div id=\"callName\" style=\"font-size:22px;font-weight:600;margin-bottom:4px\"></div>";
h+="<div id=\"callStatus\" style=\"font-size:14px;color:#aaa;margin-bottom:32px\"></div>";
h+="<div id=\"callTime\" style=\"font-size:16px;color:#ccc;margin-bottom:40px;display:none\">00:00</div>";
h+="<video id=\"remoteVideo\" style=\"display:none;position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover\" autoplay playsinline></video>";
h+="<video id=\"localVideo\" style=\"display:none;position:absolute;bottom:80px;right:16px;width:120px;height:160px;border-radius:12px;object-fit:cover;z-index:2;border:2px solid #fff\" autoplay playsinline muted></video>";
h+="<div id=\"callActions\" style=\"position:absolute;bottom:40px;left:0;right:0;display:flex;justify-content:center;gap:24px;z-index:3\">";
h+="<button id=\"btnMute\" onclick=\"SSCall.toggleMute()\" style=\"width:56px;height:56px;border-radius:50%;border:none;background:rgba(255,255,255,.2);color:#fff;font-size:20px;cursor:pointer\"><i class=\"fas fa-microphone\"></i></button>";
h+="<button onclick=\"SSCall.hangup()\" style=\"width:64px;height:64px;border-radius:50%;border:none;background:#e74c3c;color:#fff;font-size:24px;cursor:pointer\"><i class=\"fas fa-phone-slash\"></i></button>";
h+="<button id=\"btnCamera\" onclick=\"SSCall.toggleCamera()\" style=\"width:56px;height:56px;border-radius:50%;border:none;background:rgba(255,255,255,.2);color:#fff;font-size:20px;cursor:pointer;display:none\"><i class=\"fas fa-video\"></i></button>";
h+="</div>";
h+="<div id=\"incomingActions\" style=\"position:absolute;bottom:40px;left:0;right:0;display:none;justify-content:center;gap:48px;z-index:3\">";
h+="<button onclick=\"SSCall.rejectCall()\" style=\"width:64px;height:64px;border-radius:50%;border:none;background:#e74c3c;color:#fff;font-size:24px;cursor:pointer\"><i class=\"fas fa-phone-slash\"></i></button>";
h+="<button onclick=\"SSCall.acceptCall()\" style=\"width:64px;height:64px;border-radius:50%;border:none;background:#2ecc71;color:#fff;font-size:24px;cursor:pointer\"><i class=\"fas fa-phone\"></i></button>";
h+="</div></div>";
var d=document.createElement("div");d.innerHTML=h;document.body.insertBefore(d.firstChild,document.body.firstChild);
// console.log("[SSCall] UI created");
}
function showCall(name,avatar,status,incoming){
createCallUI();
var s=document.getElementById("callScreen");if(!s){console.error("[SSCall] callScreen NOT FOUND!");return;}s.style.cssText="display:flex!important;position:fixed;top:0;left:0;right:0;bottom:0;background:linear-gradient(135deg,#1a1a2e,#16213e);z-index:99999;flex-direction:column;align-items:center;justify-content:center;color:#fff;font-family:-apple-system,sans-serif";console.log("[SSCall] callScreen shown");
document.getElementById("callName").textContent=name;
document.getElementById("callStatus").textContent=status;
document.getElementById("callTime").style.display="none";
var av=document.getElementById("callAvatar");
if(avatar)av.innerHTML="<img src=\""+_esc(avatar)+"\" style=\"width:100%;height:100%;object-fit:cover\">";
else av.textContent=(name||"?")[0];
document.getElementById("callActions").style.display=incoming?"none":"flex";
document.getElementById("incomingActions").style.display=incoming?"flex":"none";
}
function hideCall(){
var s=document.getElementById("callScreen");if(s)s.style.display="none";
var rv=document.getElementById("remoteVideo");if(rv)rv.style.display="none";
var lv=document.getElementById("localVideo");if(lv)lv.style.display="none";
}
async function startCall(userId,userName,userAvatar,isVideo){
// console.log("[SSCall] startCall",userId,userName,isVideo);
if(isCallActive)return;
if(!myId){alert("Dang nhap de goi!");return;}
isCallActive=true;isCaller=true;
showCall(userName,userAvatar,isVideo?"Dang goi video...":"Dang goi...","");
if(isVideo)document.getElementById("btnCamera").style.display="block";
try{
localStream=await navigator.mediaDevices.getUserMedia({audio:true,video:isVideo});
if(isVideo){document.getElementById("localVideo").srcObject=localStream;document.getElementById("localVideo").style.display="block";}
callDocId=myId+"_"+userId+"_"+Date.now();
await db.ref("calls/"+userId).set({from:myId,fromName:getMyName(),fromAvatar:getMyAvatar(),type:isVideo?"video":"audio",status:"ringing",callId:callDocId,timestamp:firebase.database.ServerValue.TIMESTAMP});
pc=new RTCPeerConnection(ICE);
localStream.getTracks().forEach(function(t){pc.addTrack(t,localStream);});
pc.ontrack=function(e){remoteStream=e.streams[0];var rv=document.getElementById("remoteVideo");rv.srcObject=remoteStream;if(isVideo)rv.style.display="block";document.getElementById("callStatus").textContent="Da ket noi";startTimer();};
pc.onicecandidate=function(e){if(e.candidate)db.ref("signaling/"+callDocId+"/callerICE").push(JSON.stringify(e.candidate));};
var offer=await pc.createOffer();await pc.setLocalDescription(offer);
await db.ref("signaling/"+callDocId+"/offer").set(JSON.stringify(offer));
db.ref("signaling/"+callDocId+"/answer").on("value",async function(snap){var a=snap.val();if(a&&pc&&pc.signalingState!=="stable")await pc.setRemoteDescription(JSON.parse(a));});
db.ref("signaling/"+callDocId+"/calleeICE").on("child_added",async function(snap){if(pc)try{await pc.addIceCandidate(JSON.parse(snap.val()));}catch(x){}});
db.ref("calls/"+userId+"/status").on("value",function(snap){var st=snap.val();if(st==="rejected"||st==="ended"){document.getElementById("callStatus").textContent=st==="rejected"?"Da tu choi":"Da ket thuc";setTimeout(function(){hangup(true);},1500);}});
setTimeout(function(){if(isCallActive&&!callInterval){document.getElementById("callStatus").textContent="Khong tra loi";setTimeout(function(){hangup();},1500);}},30000);
}catch(e){console.error("Call error:",e);alert("Loi: "+e.message);hangup();}
}
function listenIncoming(){
db.ref("calls/"+myId).on("value",function(snap){
var d=snap.val();
if(d&&d.status==="ringing"&&d.from!=myId&&!isCallActive){
isCallActive=true;isCaller=false;callDocId=d.callId;
showCall(d.fromName,d.fromAvatar,d.type==="video"?"Cuoc goi video den":"Cuoc goi den",true);
if(d.type==="video")document.getElementById("btnCamera").style.display="block";
}});
}
async function acceptCall(){
document.getElementById("callActions").style.display="flex";
document.getElementById("incomingActions").style.display="none";
document.getElementById("callStatus").textContent="Dang ket noi...";
var cd=(await db.ref("calls/"+myId).get()).val();var isVideo=cd&&cd.type==="video";
try{
localStream=await navigator.mediaDevices.getUserMedia({audio:true,video:isVideo});
if(isVideo){document.getElementById("localVideo").srcObject=localStream;document.getElementById("localVideo").style.display="block";}
pc=new RTCPeerConnection(ICE);
localStream.getTracks().forEach(function(t){pc.addTrack(t,localStream);});
pc.ontrack=function(e){remoteStream=e.streams[0];var rv=document.getElementById("remoteVideo");rv.srcObject=remoteStream;if(isVideo)rv.style.display="block";document.getElementById("callStatus").textContent="Da ket noi";startTimer();};
pc.onicecandidate=function(e){if(e.candidate)db.ref("signaling/"+callDocId+"/calleeICE").push(JSON.stringify(e.candidate));};
var os=await db.ref("signaling/"+callDocId+"/offer").get();
await pc.setRemoteDescription(JSON.parse(os.val()));
var answer=await pc.createAnswer();await pc.setLocalDescription(answer);
await db.ref("signaling/"+callDocId+"/answer").set(JSON.stringify(answer));
db.ref("signaling/"+callDocId+"/callerICE").on("child_added",async function(snap){if(pc)try{await pc.addIceCandidate(JSON.parse(snap.val()));}catch(x){}});
await db.ref("calls/"+myId+"/status").set("connected");
}catch(e){console.error("Accept error:",e);hangup();}
}
function rejectCall(){db.ref("calls/"+myId+"/status").set("rejected");setTimeout(function(){db.ref("calls/"+myId).remove();},2000);cleanup();}
function hangup(skip){
if(!skip&&callDocId){var ref=isCaller?db.ref("calls/"+callDocId.split("_")[1]):db.ref("calls/"+myId);ref.update({status:"ended"});setTimeout(function(){if(callDocId){db.ref("signaling/"+callDocId).remove();ref.remove();}},2000);}
cleanup();
}
function cleanup(){isCallActive=false;if(pc){pc.close();pc=null;}if(localStream){localStream.getTracks().forEach(function(t){t.stop();});localStream=null;}if(callInterval){clearInterval(callInterval);callInterval=null;}hideCall();callDocId=null;}
function toggleMute(){if(!localStream)return;var a=localStream.getAudioTracks()[0];if(a){a.enabled=!a.enabled;document.getElementById("btnMute").style.background=a.enabled?"rgba(255,255,255,.2)":"#e74c3c";}}
function toggleCamera(){if(!localStream)return;var v=localStream.getVideoTracks()[0];if(v){v.enabled=!v.enabled;document.getElementById("btnCamera").style.background=v.enabled?"rgba(255,255,255,.2)":"#e74c3c";}}
function startTimer(){var s=0;document.getElementById("callTime").style.display="block";callInterval=setInterval(function(){s++;var m=Math.floor(s/60),ss=s%60;document.getElementById("callTime").textContent=(m<10?"0":"")+m+":"+(ss<10?"0":"")+ss;},1000);}
window.SSCall={startCall:startCall,acceptCall:acceptCall,rejectCall:rejectCall,hangup:hangup,toggleMute:toggleMute,toggleCamera:toggleCamera};
if(document.readyState==="complete")initFB();else window.addEventListener("load",initFB);
})();