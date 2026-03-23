
function formatVND(amount){
  return Number(amount||0).toLocaleString('vi-VN')+'đ';
}
// ShipperShop wallet.html — extracted for browser caching

// --- block 11401B ---

var CU=JSON.parse(localStorage.getItem("user")||"null");
var TK=localStorage.getItem("token")||"";
var hasPin=false,selPlanId=0;
if(!CU)location.href="login.html";
else{loadWallet();loadPlans();loadUsage();var pp=new URLSearchParams(location.search);if(pp.get("payment")==="success"){setTimeout(function(){alert("✅ Thanh toán thành công! Gói đã được kích hoạt.");},500);}if(pp.get("payment")==="cancel"){setTimeout(function(){alert("Đã hủy thanh toán.");},500);}}

async function api(u,m,b){var h={"Content-Type":"application/json"};if(TK)h["Authorization"]="Bearer "+TK;var o={method:m||"GET",headers:h,credentials:"include"};if(b)o.body=JSON.stringify(b);var r=await fetch(u,o);return await r.json();}
async function loadWallet(){try{var d=await api("/api/wallet-api.php?action=info");if(!d.success)return;hasPin=d.data.has_pin;document.getElementById("balAmount").textContent=Number(d.data.balance).toLocaleString("vi-VN")+" ₫";document.getElementById("pinBtnText").textContent=hasPin?"Đổi PIN":"Tạo PIN";if(hasPin){document.getElementById("secPin").innerHTML='<i class="fas fa-check"></i> PIN: Đã bật';document.getElementById("secPin").className="sec-badge on";document.getElementById("pinOldRow").style.display="block";}if(d.data.subscription){var s=d.data.subscription;document.getElementById("balSub").style.display="flex";document.getElementById("balBadge").textContent=s.badge||s.plan;document.getElementById("balSubText").textContent="Hết hạn: "+fmtD(s.expires_at);}renderTxns(d.data.transactions);}catch(e){}}
function renderTxns(t){var el=document.getElementById("txnList");if(!t||!t.length){el.innerHTML='<div style="text-align:center;padding:20px;color:var(--muted);font-size:13px">Chưa có giao dịch</div>';return;}el.innerHTML=t.map(function(x){var ic=x.type==="deposit"?"dep":"pay";var icn=x.type==="deposit"?"fa-arrow-down":"fa-arrow-up";var plus=x.type==="deposit"||x.type==="refund"||x.type==="bonus";var st=x.status==="completed"?"Xong":(x.status==="pending"?"Đang xử lý":"Từ chối");var sc=x.status==="completed"?"completed":"pending";return '<div class="txn"><div class="txn-icon '+ic+'"><i class="fas '+icn+'"></i></div><div class="txn-info"><div class="txn-desc">'+esc(x.description||x.type)+'</div><div class="txn-date">'+fmtD(x.created_at)+(x.status!=="completed"?' <span class="txn-status '+sc+'">'+st+'</span>':'')+'</div></div><div class="txn-amount '+(plus?"plus":"minus")+'">'+(plus?"+":"-")+Number(x.amount).toLocaleString("vi-VN")+'đ</div></div>';}).join("");}
async function loadPlans(){try{var d=await api("/api/wallet-api.php?action=plans");if(!d.success)return;var html='';d.data.forEach(function(p){var isPlus=p.slug==='plus';var isFree=p.slug==='free';if(!isFree&&!isPlus)return;var feat=(p.features||[]).map(function(f){return '<li><i class="fas fa-check" style="color:'+(isPlus?'#7C3AED':'#65676B')+';margin-right:6px;font-size:11px"></i>'+esc(f)+'</li>';}).join("");var pr=p.price>0?Number(p.price).toLocaleString("vi-VN")+'\u0111<small>/th\u00e1ng</small>':"0\u0111";var yrPr=p.yearly_price>0?'<div style="font-size:12px;color:var(--muted);margin-top:2px">ho\u1eb7c '+Number(p.yearly_price).toLocaleString("vi-VN")+'\u0111/n\u0103m <span style="color:#4CAF50;font-weight:700">(ti\u1ebft ki\u1ec7m 29%)</span></div>':"";var cls=isPlus?" popular":"";var badge=isPlus?'<div class="plan-badge">Khuy\u00ean d\u00f9ng</div>':"";var btnCls=isPlus?"primary":"outline";var btnTxt=isPlus?"\u0110\u0103ng k\u00fd ngay":"Ch\u1ecdn g\u00f3i";html+='<div class="plan'+cls+'">'+badge+'<div class="plan-name">'+(p.badge?p.badge+" ":"")+p.name+'</div><div class="plan-price">'+pr+'</div>'+yrPr+'<ul class="plan-features">'+feat+'</ul><button class="plan-buy '+btnCls+'" onclick="startSub('+p.id+',\''+esc(p.name).replace(/'/g,"\\'")+'\',' +p.price+','+p.duration_days+')">'+btnTxt+'</button></div>';});document.getElementById("plansList").innerHTML=html;}catch(e){}}

function openDep(){document.getElementById("depOvl").classList.add("open");}
function closeDep(){document.getElementById("depOvl").classList.remove("open");}
function selAmt(el,v){document.getElementById("depAmt").value=v;document.querySelectorAll(".w-amt").forEach(function(a){a.classList.remove("sel");});el.classList.add("sel");}
async function submitDep(){var a=parseInt(document.getElementById("depAmt").value)||0;if(a<10000){alert("Tối thiểu 10.000đ");return;}closeDep();startDeposit(a);}

function openPin(){document.getElementById("pinOvl").classList.add("open");}
function closePin(){document.getElementById("pinOvl").classList.remove("open");}
async function submitPin(){var n=document.getElementById("pinNew").value,c=document.getElementById("pinCfm").value;if(n.length<4){alert("PIN 4-6 số");return;}if(n!==c){alert("PIN ko khớp");return;}var b={pin:n,confirm_pin:c};if(hasPin)b.old_pin=document.getElementById("pinOld").value;try{var d=await api("/api/wallet-api.php?action=set_pin","POST",b);if(d.success){alert("✅ "+d.message);closePin();loadWallet();}else alert("❌ "+d.message);}catch(e){alert("Lỗi");}}

async function loadUsage(){try{var d=await api("/api/wallet-api.php?action=usage");if(!d.success)return;var u=d.data.usage;var l=d.data.limits;var isPlus=d.data.is_plus;var sec=document.getElementById("usageSection");sec.style.display="block";var items=[{label:"Bài đăng hôm nay",used:u.posts_today,max:l.posts_per_day,icon:"fas fa-pen"},{label:"Tin nhắn tháng này",used:u.messages_month,max:l.messages_per_month,icon:"fas fa-comment"},{label:"Nhóm đã tham gia",used:u.groups_joined,max:l.groups_max,icon:"fas fa-users"},{label:"Sản phẩm Marketplace",used:u.marketplace_active,max:l.marketplace_max,icon:"fas fa-store"}];var html=isPlus?'<div style="background:#EDE9FE;border-radius:10px;padding:10px 14px;margin-bottom:10px;display:flex;align-items:center;gap:8px"><span style="font-size:16px">\uD83D\uDC9C</span><span style="font-size:13px;color:#5B21B6;font-weight:600">Shipper Plus \u2014 T\u1ea5t c\u1ea3 kh\u00f4ng gi\u1edbi h\u1ea1n</span></div>':'';items.forEach(function(it){var pct=it.max>=9999?100:Math.min(Math.round(it.used/it.max*100),100);var clr=pct>=90?"#e53935":pct>=70?"#f5a623":"#7C3AED";var maxTxt=it.max>=9999?"\u221e":it.max;html+='<div style="margin-bottom:10px"><div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px"><span style="font-size:12px;color:#65676B"><i class="'+it.icon+'" style="width:16px;margin-right:6px;color:'+clr+'"></i>'+it.label+'</span><span style="font-size:12px;font-weight:600;color:'+clr+'">'+it.used+'/'+maxTxt+'</span></div><div style="height:5px;background:#eee;border-radius:3px"><div style="height:5px;border-radius:3px;background:'+clr+';width:'+pct+'%;transition:width .3s"></div></div></div>';});document.getElementById("usageContent").innerHTML=html;}catch(e){}}
var payOrderCode=0,payPollTimer=null,payType='subscription';
function startSub(id,name,price,days){
  if(price<=0){api("/api/wallet-api.php?action=subscribe","POST",{plan_id:id}).then(function(d){if(d.success){alert("✅ "+d.message);loadWallet();loadPlans();loadUsage();}else alert("❌ "+d.message);});return;}
  selPlanId=id;payType='subscription';
  document.getElementById("subName").textContent=name;
  document.getElementById("subPrice").textContent=Number(price).toLocaleString("vi-VN")+"đ";
  document.getElementById("subDur").textContent=days+" ngày";
  document.getElementById("qrWrap").style.display="none";
  document.getElementById("payLoading").style.display="none";
  document.getElementById("paySuccess").style.display="none";
  document.getElementById("payFoot").style.display="flex";
  document.getElementById("subBtn").disabled=false;
  document.getElementById("subBtn").innerHTML='<i class="fas fa-qrcode" style="margin-right:6px"></i>Tạo mã thanh toán';
  document.getElementById("subOvl").classList.add("open");
}
function startDeposit(amount){
  payType='deposit';selPlanId=0;
  document.getElementById("subName").textContent="Nạp tiền vào ví";
  document.getElementById("subPrice").textContent=Number(amount).toLocaleString("vi-VN")+"đ";
  document.getElementById("subDur").textContent="Cộng ngay vào số dư";
  document.getElementById("qrWrap").style.display="none";
  document.getElementById("payLoading").style.display="none";
  document.getElementById("paySuccess").style.display="none";
  document.getElementById("payFoot").style.display="flex";
  document.getElementById("subBtn").disabled=false;
  document.getElementById("subBtn").innerHTML='<i class="fas fa-qrcode" style="margin-right:6px"></i>Tạo mã thanh toán';
  window._depAmount=amount;
  document.getElementById("subOvl").classList.add("open");
}
function closeSub(){document.getElementById("subOvl").classList.remove("open");if(payPollTimer)clearInterval(payPollTimer);payPollTimer=null;}
async function startPayOS(){
  var btn=document.getElementById("subBtn");btn.disabled=true;
  document.getElementById("payLoading").style.display="block";
  document.getElementById("payFoot").style.display="none";
  try{
    var body=payType==='subscription'?{type:'subscription',plan_id:selPlanId}:{type:'deposit',amount:window._depAmount||0};
    var d=await api("/api/wallet-api.php?action=payos_pay","POST",body);
    document.getElementById("payLoading").style.display="none";
    if(!d.success){alert("❌ "+d.message);document.getElementById("payFoot").style.display="flex";btn.disabled=false;btn.innerHTML='<i class="fas fa-qrcode" style="margin-right:6px"></i>Thử lại';return;}
    payOrderCode=d.data.orderCode;
    // Show QR - use payOS checkout URL as QR source (works with all banks)
    var qrImg=document.getElementById("qrImg");
    qrImg.src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data="+encodeURIComponent(d.data.checkoutUrl);
    document.getElementById("payAccInfo").textContent=(d.data.accountName||"")+(d.data.accountNumber?" · "+d.data.accountNumber:"");
    document.getElementById("qrWrap").style.display="block";
    // Also show checkout link
    document.getElementById("payFoot").style.display="flex";
    btn.disabled=false;
    btn.innerHTML='<i class="fas fa-external-link-alt" style="margin-right:6px"></i>Mở trang thanh toán';
    btn.onclick=function(){window.open(d.data.checkoutUrl,"_blank");};
    // Poll for payment status
    payPollTimer=setInterval(async function(){
      try{var c=await api("/api/wallet-api.php?action=payos_check&order_code="+payOrderCode);
      if(c.success&&c.data.status==="PAID"){
        clearInterval(payPollTimer);payPollTimer=null;
        document.getElementById("qrWrap").style.display="none";
        document.getElementById("payFoot").style.display="none";
        document.getElementById("paySuccess").style.display="block";
        document.getElementById("paySuccessMsg").textContent=payType==='subscription'?"Gói đã được kích hoạt!":"Tiền đã cộng vào ví!";
        setTimeout(function(){closeSub();loadWallet();loadPlans();loadUsage();},2500);
      }}catch(e){}
    },3000);
  }catch(e){alert("Lỗi kết nối");document.getElementById("payLoading").style.display="none";document.getElementById("payFoot").style.display="flex";btn.disabled=false;}
}

function esc(t){return t?String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"):"";}
function fmtD(d){if(!d)return"";try{return new Date(d.replace(" ","T")).toLocaleDateString("vi-VN",{day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit"});}catch(e){return d;}}

function showQR(amount){
  var token=localStorage.getItem('token');
  if(!token){toast('Đăng nhập!');return;}
  var modal=document.createElement('div');
  modal.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
  modal.innerHTML='<div style="background:#fff;border-radius:16px;padding:24px;max-width:320px;width:90%;text-align:center"><div style="font-size:16px;font-weight:700;margin-bottom:12px">Quét mã QR để nạp tiền</div><div id="qrLoading"><i class="fas fa-spinner fa-spin" style="font-size:24px;color:#7C3AED"></i></div><div id="qrContent" style="display:none"></div><button onclick="this.closest(\x22[style]\x22).remove()" style="margin-top:16px;padding:8px 24px;border:none;border-radius:8px;background:#f0f0f0;cursor:pointer">Đóng</button></div>';
  modal.onclick=function(e){if(e.target===modal)modal.remove();};
  document.body.appendChild(modal);
  
  fetch('/api/wallet-api.php?action=qr_code&amount='+(amount||0),{headers:{'Authorization':'Bearer '+token}})
    .then(function(r){return r.json()})
    .then(function(d){
      document.getElementById('qrLoading').style.display='none';
      var el=document.getElementById('qrContent');
      if(d.success&&d.data&&d.data.qr_url){
        el.innerHTML='<img src="'+d.data.qr_url+'" style="width:240px;height:240px;border-radius:8px;margin:8px auto;display:block"><div style="font-size:13px;color:#333;margin-top:8px"><b>'+d.data.bank+'</b></div><div style="font-size:12px;color:#666">STK: '+d.data.account+'</div><div style="font-size:12px;color:#666">Nội dung: <b>'+d.data.description+'</b></div>'+(d.data.amount?'<div style="font-size:18px;font-weight:700;color:#7C3AED;margin-top:8px">'+Number(d.data.amount).toLocaleString("vi-VN")+'đ</div>':'');
        el.style.display='block';
      }else{
        el.innerHTML='<div style="color:#e74c3c">Không thể tạo mã QR</div>';
        el.style.display='block';
      }
    }).catch(function(){document.getElementById('qrLoading').innerHTML='<div style="color:#e74c3c">Lỗi kết nối</div>';});
}

// Quick deposit amounts
function renderQuickAmounts(){
  var el=document.getElementById('quickAmounts');
  if(!el)return;
  var amounts=[20000,50000,100000,200000,500000,1000000];
  var html='<div style="display:flex;flex-wrap:wrap;gap:6px;margin:8px 0">';
  amounts.forEach(function(a){
    html+='<button onclick="setDepositAmount('+a+')" style="padding:6px 12px;border:1px solid #e4e6eb;border-radius:8px;background:#fff;font-size:12px;cursor:pointer;font-weight:500">'+Number(a).toLocaleString("vi-VN")+'đ</button>';
  });
  html+='</div>';
  el.innerHTML=html;
}
function setDepositAmount(amount){
  var input=document.getElementById('depositAmount');
  if(input)input.value=amount;
  // Update QR if visible
  var qrBtn=document.querySelector('[onclick*="showQR"]');
  if(qrBtn)qrBtn.onclick=function(){showQR(amount);};
}

function showDepositGuide(){
  var ov=document.createElement('div');
  ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
  ov.innerHTML='<div style="background:#fff;border-radius:16px;padding:20px;max-width:360px;width:90%;max-height:80vh;overflow-y:auto"><h3 style="margin:0 0 12px;font-size:17px;text-align:center">💰 Hướng dẫn nạp tiền</h3>'
    +'<div style="padding:10px;background:#f5f3ff;border-radius:8px;margin-bottom:12px"><div style="font-weight:600;font-size:14px;color:#7C3AED;margin-bottom:6px">Bước 1: Chuyển khoản</div><div style="font-size:13px;color:#333">Chuyển tiền đến tài khoản ngân hàng bên dưới với nội dung: <b>SS[Mã user]NAP</b></div></div>'
    +'<div style="padding:10px;background:#f0f0f0;border-radius:8px;margin-bottom:8px"><div style="font-weight:600">Vietcombank</div><div style="font-size:13px">STK: 1234567890 · SHIPPERSHOP</div></div>'
    +'<div style="padding:10px;background:#f0f0f0;border-radius:8px;margin-bottom:8px"><div style="font-weight:600">Techcombank</div><div style="font-size:13px">STK: 0987654321 · SHIPPERSHOP</div></div>'
    +'<div style="padding:10px;background:#f0f0f0;border-radius:8px;margin-bottom:12px"><div style="font-weight:600">MBBank</div><div style="font-size:13px">STK: 1122334455 · SHIPPERSHOP</div></div>'
    +'<div style="padding:10px;background:#f5f3ff;border-radius:8px;margin-bottom:12px"><div style="font-weight:600;font-size:14px;color:#7C3AED;margin-bottom:6px">Bước 2: Xác nhận</div><div style="font-size:13px">Bấm "Yêu cầu nạp tiền" và nhập số tiền. Admin sẽ duyệt trong 1-24h.</div></div>'
    +'<div style="text-align:center"><button onclick="showQR()" style="padding:10px 24px;background:#7C3AED;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;margin-right:8px"><i class="fas fa-qrcode"></i> Quét QR</button><button onclick="this.closest(\'[style]\').remove()" style="padding:10px 24px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer">Đóng</button></div></div>';
  ov.onclick=function(e){if(e.target===ov)ov.remove();};
  document.body.appendChild(ov);
}
