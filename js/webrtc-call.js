/* ShipperShop WebRTC Call - Firebase Signaling */
(function(){
var FB_CFG={apiKey:"AIzaSyDNwf6FKPX10szjFJ2Ei6YoKJWRA6NAkKs",authDomain:"shippershop-5f8d9.firebaseapp.com",databaseURL:"https://shippershop-5f8d9-default-rtdb.asia-southeast1.firebasedatabase.app",projectId:"shippershop-5f8d9",storageBucket:"shippershop-5f8d9.firebasestorage.app",messagingSenderId:"718681388880",appId:"1:718681388880:web:806e552f7fbc70feb0de55"};
var fbApp,fbDb,pc,localStream,remoteStream,callRef,ansRef,iceCandRef;
var myUid=0,peerUid=0,peerName="",isVideo=false,isCaller=false,callActive=false;
var ringtone;

function initFB(){
  if(fbApp)return;
  fbApp=firebase.apps.length?firebase.app():firebase.initializeApp(FB_CFG);
  fbDb=firebase.database();
}

function getUid(){
  try{var t=localStorage.getItem('token');if(!t)return 0;if(t.indexOf('.')>0){var p=JSON.parse(atob(t.split('.')[1]));return p.user_id||p.sub||p.id||0;}return 0;}catch(e){return 0;}
}

/* ===== UI ===== */
function createCallUI(){
  if(document.getElementById('callOverlay'))return;
  var d=document.createElement('div');
  d.id='callOverlay';
  d.innerHTML='<div id="callScreen" style="position:fixed;top:0;left:0;width:100%;height:100%;background:#1a1a2e;z-index:99999;display:none;flex-direction:column;align-items:center;justify-content:center;color:#fff">'
    +'<div id="callRemoteVideo" style="position:absolute;top:0;left:0;width:100%;height:100%;display:none"><video id="remoteVid" autoplay playsinline style="width:100%;height:100%;object-fit:cover"></video></div>'
    +'<div id="callLocalVideo" style="position:absolute;bottom:100px;right:16px;width:120px;height:160px;border-radius:12px;overflow:hidden;display:none;z-index:2;border:2px solid #fff"><video id="localVid" autoplay playsinline muted style="width:100%;height:100%;object-fit:cover"></video></div>'
    +'<div id="callInfo" style="position:relative;z-index:1;text-align:center">'
    +'<div id="callAvatar" style="width:80px;height:80px;border-radius:50%;background:#e4e6eb;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;overflow:hidden"></div>'
    +'<div id="callName" style="font-size:20px;font-weight:600;margin-bottom:8px"></div>'
    +'<div id="callStatus" style="font-size:14px;color:#aaa;margin-bottom:40px">Đang gọi...</div>'
    +'<div id="callTimer" style="font-size:16px;display:none;margin-bottom:20px">00:00</div>'
    +'</div>'
    +'<div id="callActions" style="position:absolute;bottom:40px;left:0;width:100%;display:flex;justify-content:center;gap:24px;z-index:2">'
    +'<button id="btnMute" onclick="window.SSCall.toggleMute()" style="width:56px;height:56px;border-radius:50%;border:none;background:rgba(255,255,255,0.2);color:#fff;font-size:20px;cursor:pointer"><i class="fas fa-microphone"></i></button>'
    +'<button id="btnEndCall" onclick="window.SSCall.endCall()" style="width:56px;height:56px;border-radius:50%;border:none;background:#e74c3c;color:#fff;font-size:20px;cursor:pointer"><i class="fas fa-phone-slash"></i></button>'
    +'<button id="btnCamToggle" onclick="window.SSCall.toggleCam()" style="width:56px;height:56px;border-radius:50%;border:none;background:rgba(255,255,255,0.2);color:#fff;font-size:20px;cursor:pointer;display:none"><i class="fas fa-video"></i></button>'
    +'</div>'
    +'</div>'
    +'<div id="incomingCall" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:99998;display:none;flex-direction:column;align-items:center;justify-content:center;color:#fff">'
    +'<div id="incAvatar" style="width:80px;height:80px;border-radius:50%;background:#e4e6eb;margin-bottom:16px;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;overflow:hidden"></div>'
    +'<div id="incName" style="font-size:20px;font-weight:600;margin-bottom:8px"></div>'
    +'<div id="incType" style="font-size:14px;color:#aaa;margin-bottom:40px">Cuộc gọi đến...</div>'
    +'<div style="display:flex;gap:40px">'
    +'<button onclick="window.SSCall.rejectCall()" style="width:64px;height:64px;border-radius:50%;border:none;background:#e74c3c;color:#fff;font-size:24px;cursor:pointer"><i class="fas fa-phone-slash"></i></button>'
    +'<button onclick="window.SSCall.acceptCall()" style="width:64px;height:64px;border-radius:50%;border:none;background:#2ecc71;color:#fff;font-size:24px;cursor:pointer"><i class="fas fa-phone"></i></button>'
    +'</div>'
    +'</div>';
  document.body.appendChild(d);
}

/* ===== CALL FLOW ===== */
var STUN={iceServers:[{urls:'stun:stun.l.google.com:19302'},{urls:'stun:stun1.l.google.com:19302'}]};

async function startCall(uid,name,avatar,video){
  initFB();createCallUI();
  myUid=getUid();if(!myUid){alert('Đăng nhập để gọi!');return;}
  peerUid=uid;peerName=name;isVideo=video;isCaller=true;
  showCallScreen(name,avatar,video);
  document.getElementById('callStatus').textContent=video?'Đang gọi video...':'Đang gọi...';
  try{
    localStream=await navigator.mediaDevices.getUserMedia({audio:true,video:video});
    if(video){
      document.getElementById('localVid').srcObject=localStream;
      document.getElementById('callLocalVideo').style.display='block';
      document.getElementById('btnCamToggle').style.display='block';
    }
  }catch(e){alert('Không thể truy cập mic/camera!');hideCallScreen();return;}
  var roomId=myUid<peerUid?myUid+'_'+peerUid:peerUid+'_'+myUid;
  callRef=fbDb.ref('calls/'+roomId);
  await callRef.set({caller:myUid,callee:peerUid,callerName:getCurName(),type:video?'video':'audio',status:'ringing',ts:Date.now()});
  callRef.child('status').on('value',function(snap){
    var s=snap.val();
    if(s==='accepted')onCallAccepted();
    if(s==='rejected'||s==='ended'){endCallCleanup();showToast(s==='rejected'?'Cuộc gọi bị từ chối':'Cuộc gọi kết thúc');}
  });
  setTimeout(function(){if(!callActive){callRef.child('status').off();callRef.update({status:'missed'});endCallCleanup();showToast('Không có phản hồi');}},30000);
}

function getCurName(){try{return document.querySelector('.chat-header-name')?.textContent||'User';}catch(e){return 'User';}}

async function onCallAccepted(){
  callActive=true;
  document.getElementById('callStatus').style.display='none';
  document.getElementById('callTimer').style.display='block';
  startTimer();
  pc=new RTCPeerConnection(STUN);
  localStream.getTracks().forEach(function(t){pc.addTrack(t,localStream);});
  remoteStream=new MediaStream();
  pc.ontrack=function(e){e.streams[0].getTracks().forEach(function(t){remoteStream.addTrack(t);});
    if(isVideo){document.getElementById('remoteVid').srcObject=remoteStream;document.getElementById('callRemoteVideo').style.display='block';}
    else{var a=new Audio();a.srcObject=remoteStream;a.play();}
  };
  var roomId=myUid<peerUid?myUid+'_'+peerUid:peerUid+'_'+myUid;
  pc.onicecandidate=function(e){if(e.candidate)fbDb.ref('calls/'+roomId+'/callerIce').push(JSON.stringify(e.candidate));};
  var offer=await pc.createOffer();await pc.setLocalDescription(offer);
  await fbDb.ref('calls/'+roomId+'/offer').set(JSON.stringify(offer));
  fbDb.ref('calls/'+roomId+'/answer').on('value',async function(snap){
    if(snap.val()&&!pc.currentRemoteDescription){await pc.setRemoteDescription(JSON.parse(snap.val()));}
  });
  fbDb.ref('calls/'+roomId+'/calleeIce').on('child_added',async function(snap){
    if(snap.val())await pc.addIceCandidate(JSON.parse(snap.val()));
  });
}

async function acceptCall(){
  initFB();callActive=true;isCaller=false;
  document.getElementById('incomingCall').style.display='none';
  var roomId=myUid<peerUid?myUid+'_'+peerUid:peerUid+'_'+myUid;
  callRef=fbDb.ref('calls/'+roomId);
  await callRef.update({status:'accepted'});
  showCallScreen(peerName,'',isVideo);
  document.getElementById('callStatus').style.display='none';
  document.getElementById('callTimer').style.display='block';
  startTimer();
  try{localStream=await navigator.mediaDevices.getUserMedia({audio:true,video:isVideo});}catch(e){alert('Lỗi mic/camera!');endCall();return;}
  if(isVideo){document.getElementById('localVid').srcObject=localStream;document.getElementById('callLocalVideo').style.display='block';document.getElementById('btnCamToggle').style.display='block';}
  pc=new RTCPeerConnection(STUN);
  localStream.getTracks().forEach(function(t){pc.addTrack(t,localStream);});
  remoteStream=new MediaStream();
  pc.ontrack=function(e){e.streams[0].getTracks().forEach(function(t){remoteStream.addTrack(t);});
    if(isVideo){document.getElementById('remoteVid').srcObject=remoteStream;document.getElementById('callRemoteVideo').style.display='block';}
    else{var a=new Audio();a.srcObject=remoteStream;a.play();}
  };
  pc.onicecandidate=function(e){if(e.candidate)fbDb.ref('calls/'+roomId+'/calleeIce').push(JSON.stringify(e.candidate));};
  fbDb.ref('calls/'+roomId+'/offer').once('value',async function(snap){
    if(snap.val()){
      await pc.setRemoteDescription(JSON.parse(snap.val()));
      var answer=await pc.createAnswer();await pc.setLocalDescription(answer);
      await fbDb.ref('calls/'+roomId+'/answer').set(JSON.stringify(answer));
    }
  });
  fbDb.ref('calls/'+roomId+'/callerIce').on('child_added',async function(snap){
    if(snap.val())await pc.addIceCandidate(JSON.parse(snap.val()));
  });
  callRef.child('status').on('value',function(snap){if(snap.val()==='ended')endCallCleanup();});
}

function rejectCall(){
  var roomId=myUid<peerUid?myUid+'_'+peerUid:peerUid+'_'+myUid;
  fbDb.ref('calls/'+roomId).update({status:'rejected'});
  document.getElementById('incomingCall').style.display='none';
}

function endCall(){
  var roomId=myUid<peerUid?myUid+'_'+peerUid:peerUid+'_'+myUid;
  if(fbDb)fbDb.ref('calls/'+roomId).update({status:'ended'});
  endCallCleanup();
}

function endCallCleanup(){
  callActive=false;stopTimer();
  if(pc){pc.close();pc=null;}
  if(localStream){localStream.getTracks().forEach(function(t){t.stop();});localStream=null;}
  var roomId=myUid<peerUid?myUid+'_'+peerUid:peerUid+'_'+myUid;
  if(fbDb){
    fbDb.ref('calls/'+roomId).off();
    fbDb.ref('calls/'+roomId+'/offer').off();
    fbDb.ref('calls/'+roomId+'/answer').off();
    fbDb.ref('calls/'+roomId+'/callerIce').off();
    fbDb.ref('calls/'+roomId+'/calleeIce').off();
    fbDb.ref('calls/'+roomId).remove();
  }
  hideCallScreen();
}

/* ===== CONTROLS ===== */
function toggleMute(){
  if(!localStream)return;
  var a=localStream.getAudioTracks()[0];if(!a)return;
  a.enabled=!a.enabled;
  document.getElementById('btnMute').innerHTML=a.enabled?'<i class="fas fa-microphone"></i>':'<i class="fas fa-microphone-slash"></i>';
  document.getElementById('btnMute').style.background=a.enabled?'rgba(255,255,255,0.2)':'#e74c3c';
}
function toggleCam(){
  if(!localStream)return;
  var v=localStream.getVideoTracks()[0];if(!v)return;
  v.enabled=!v.enabled;
  document.getElementById('btnCamToggle').innerHTML=v.enabled?'<i class="fas fa-video"></i>':'<i class="fas fa-video-slash"></i>';
  document.getElementById('btnCamToggle').style.background=v.enabled?'rgba(255,255,255,0.2)':'#e74c3c';
}

/* ===== TIMER ===== */
var timerInt,timerSec=0;
function startTimer(){timerSec=0;timerInt=setInterval(function(){timerSec++;var m=Math.floor(timerSec/60),s=timerSec%60;document.getElementById('callTimer').textContent=(m<10?'0':'')+m+':'+(s<10?'0':'')+s;},1000);}
function stopTimer(){clearInterval(timerInt);timerSec=0;}

/* ===== SHOW/HIDE ===== */
function showCallScreen(name,avatar,video){
  createCallUI();
  document.getElementById('callName').textContent=name||'';
  var av=document.getElementById('callAvatar');
  if(avatar)av.innerHTML='<img src="'+avatar+'" style="width:100%;height:100%;object-fit:cover">';
  else av.textContent=(name||'U')[0];
  document.getElementById('callScreen').style.display='flex';
  if(video)document.getElementById('btnCamToggle').style.display='block';
}
function hideCallScreen(){
  var cs=document.getElementById('callScreen');if(cs)cs.style.display='none';
  var ic=document.getElementById('incomingCall');if(ic)ic.style.display='none';
  var lv=document.getElementById('callLocalVideo');if(lv)lv.style.display='none';
  var rv=document.getElementById('callRemoteVideo');if(rv)rv.style.display='none';
}
function showToast(m){if(window.toast)window.toast(m);else alert(m);}

/* ===== LISTEN FOR INCOMING ===== */
function listenCalls(){
  initFB();createCallUI();
  myUid=getUid();if(!myUid)return;
  fbDb.ref('calls').orderByChild('callee').equalTo(myUid).on('child_added',function(snap){
    var d=snap.val();if(!d||d.status!=='ringing')return;
    peerUid=d.caller;peerName=d.callerName||'Người gọi';isVideo=d.type==='video';
    document.getElementById('incName').textContent=peerName;
    document.getElementById('incType').textContent=isVideo?'Cuộc gọi video đến...':'Cuộc gọi thoại đến...';
    document.getElementById('incAvatar').textContent=(peerName||'U')[0];
    document.getElementById('incomingCall').style.display='flex';
    try{navigator.vibrate([500,200,500,200,500]);}catch(e){}
  });
}

/* ===== PUBLIC API ===== */
window.SSCall={startCall:startCall,acceptCall:acceptCall,rejectCall:rejectCall,endCall:endCall,toggleMute:toggleMute,toggleCam:toggleCam,listenCalls:listenCalls};

/* Auto-listen on page load */
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',listenCalls);
else listenCalls();
})();
