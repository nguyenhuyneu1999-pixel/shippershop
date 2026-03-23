// ShipperShop listing.html

var CU=JSON.parse(localStorage.getItem("user")||"null");
var itemId=new URLSearchParams(location.search).get("id");
var itemData=null;
var condLabels={new:"Mới 100%",like_new:"Như mới",good:"Đã qua sử dụng",fair:"Còn dùng được"};
var condClass={new:"cond-new",like_new:"cond-like_new",good:"cond-good",fair:"cond-fair"};
var curImg=0;
var imgs=[];

if(!itemId){document.getElementById("content").innerHTML='<div class="loading">Không tìm thấy sản phẩm</div>';}
else{loadItem();}

async function loadItem(){
  try{
    var r=await fetch("/api/marketplace.php?id="+itemId);
    var d=await r.json();
    if(!d.success||!d.data){document.getElementById("content").innerHTML='<div class="loading">Sản phẩm không tồn tại</div>';return;}
    itemData=d.data;
    try{imgs=JSON.parse(itemData.images||"[]");}catch(e){imgs=[];}
    renderItem();
    loadSimilar();
    document.getElementById("bottomBar").style.display="flex";
  }catch(e){document.getElementById("content").innerHTML='<div class="loading">Lỗi kết nối</div>';}
}

function renderItem(){
  var p=itemData;
  var priceStr=p.price>0?Number(p.price).toLocaleString("vi-VN")+" ₫":"Miễn phí";
  document.getElementById("hdTitle").textContent=p.title;
  document.title=p.title+" - ShipperShop";

  var h="";

  // === IMAGE + VIDEO CAROUSEL ===
  if(imgs.length>0 || p.video_url){
    h+='<div class="carousel"><div class="carousel-track" id="carouselTrack" onscroll="onCarouselScroll()">';
    imgs.forEach(function(img){h+='<div class="carousel-slide"><img src="'+img+'" onclick="openLB(this.src)" loading=lazy></div>';});
    if(p.video_url){
      h+='<div class="carousel-slide" style="display:flex;align-items:center;justify-content:center;background:#000">';
      if(p.video_url.indexOf('/uploads/')!==-1){
        h+='<video controls playsinline preload="metadata" style="width:100%;max-height:400px;object-fit:contain" poster="'+(imgs[0]||'')+'"><source src="'+p.video_url+'" type="video/mp4">Video</video>';
      }else{
        h+='<iframe src="'+p.video_url+'" style="width:100%;height:100%;min-height:300px;border:0" allowfullscreen></iframe>';
      }
      h+='</div>';
    }
    var totalSlides=imgs.length+(p.video_url?1:0);
    h+='</div>';
    h+='<div class="carousel-counter" id="imgCounter">1/'+totalSlides+'</div>';
    h+='<div class="carousel-dots" id="carouselDots">';
    for(var di=0;di<totalSlides;di++){h+='<span'+(di===0?' class="active"':'')+'>'+(di>=imgs.length?'▶':'')+'</span>';}
    h+='</div>';
    h+='<div class="carousel-actions"><button onclick="toggleWish()"><i class="far fa-heart" id="wishIcon"></i></button><button onclick="shareListing()"><i class="fas fa-share-alt"></i></button></div>';
    h+='</div>';
  }

  // === PRICE SECTION ===
  h+='<div class="price-section">';
  h+='<div class="price-row"><span class="price-main">'+priceStr+'</span></div>';
  h+='<div class="product-title">'+esc(p.title)+'</div>';
  h+='<span class="cond-badge '+(condClass[p.condition_type]||"cond-good")+'">'+esc(condLabels[p.condition_type]||p.condition_type||"")+'</span>';
  h+='<div class="product-meta"><span><i class="fas fa-eye"></i> '+(p.views_count||0)+' lượt xem</span>';
  if(p.location) h+='<span><i class="fas fa-map-marker-alt"></i> '+esc(p.location)+'</span>';
  h+='</div></div>';

  // === SELLER ===
  h+='<div class="seller-section">';
  if(p.seller_avatar) h+='<img class="seller-av" src="'+p.seller_avatar+'" loading=lazy>';
  else h+='<div class="seller-av-ph">'+esc((p.seller_name||"U")[0])+'</div>';
  h+='<div class="seller-info"><div class="seller-name">'+esc(p.seller_name||"")+'</div>';
  if(p.shipping_company) h+='<div class="seller-sub"><span style="color:'+({"GHTK":"#00b14f","J&T":"#d32f2f","GHN":"#ff6600","Viettel Post":"#e21a1a","SPX":"#7C3AED"}[p.shipping_company]||"#999")+'">'+esc(p.shipping_company)+'</span></div>';
  h+='</div><button class="seller-btn" onclick="location.href=\'user.html?id='+p.user_id+'\'">Xem shop</button></div>';

  // === DESCRIPTION (Collapsible) ===
  // === SHOWCASE IMAGES (Amazon A+ Content) ===
  // Full-width images - user just scrolls to see all product details
  var allShowcase=[];
  try{allShowcase=JSON.parse(p.showcase_images||"[]");}catch(e){}
  if((!allShowcase||!allShowcase.length)&&p.description_images){try{allShowcase=JSON.parse(p.description_images);}catch(e){}}
  if(allShowcase&&allShowcase.length>0){
    h+='<div style="background:#fff;margin-top:8px">';
    h+='<div style="padding:14px 16px 8px;font-size:15px;font-weight:700"><i class="fas fa-images" style="color:var(--primary);margin-right:6px"></i>Chi tiết sản phẩm</div>';
    allShowcase.forEach(function(si){
      h+='<img src="'+si+'" style="width:100%;display:block;cursor:pointer" onclick="openLB(this.src)" loading=lazy>';
    });
    h+='</div>';
  }

  // === DESCRIPTION TEXT ===
  if(p.description){
    h+='<div class="section"><div class="section-header open" onclick="toggleSection(this)"><h3><i class="fas fa-file-text" style="color:var(--primary);margin-right:8px"></i>Mô tả sản phẩm</h3><i class="fas fa-chevron-down"></i></div>';
    h+='<div class="section-body open" style="font-size:14px;line-height:1.8">';
    h+='<div style="white-space:pre-wrap;word-break:break-word">'+esc(p.description).replace(/\n/g,"<br>")+'</div>';
    h+='</div></div>';
  }

  // === PRODUCT DETAILS (Collapsible) ===
  h+='<div class="section"><div class="section-header" onclick="toggleSection(this)"><h3><i class="fas fa-list-ul" style="color:#1976d2;margin-right:8px"></i>Chi tiết sản phẩm</h3><i class="fas fa-chevron-down"></i></div>';
  h+='<div class="section-body"><table class="specs-table">';
  h+='<tr><td>Danh mục</td><td>'+esc(catName(p.category))+'</td></tr>';
  h+='<tr><td>Tình trạng</td><td>'+esc(condLabels[p.condition_type]||"")+'</td></tr>';
  if(p.location) h+='<tr><td>Khu vực</td><td>'+esc(p.location)+'</td></tr>';
  if(p.phone) h+='<tr><td>Liên hệ</td><td>'+esc(p.phone)+'</td></tr>';
  h+='<tr><td>Đăng ngày</td><td>'+formatDate(p.created_at)+'</td></tr>';
  h+='<tr><td>Lượt xem</td><td>'+(p.views_count||0)+'</td></tr>';
  h+='</table></div></div>';

  // === SAFETY TIPS ===
  h+='<div class="section"><div class="section-header" onclick="toggleSection(this)"><h3><i class="fas fa-shield-alt" style="color:#2e7d32;margin-right:8px"></i>Lưu ý an toàn</h3><i class="fas fa-chevron-down"></i></div>';
  h+='<div class="section-body"><ul>';
  h+='<li>Kiểm tra hàng trước khi thanh toán</li>';
  h+='<li>Gặp mặt trực tiếp tại nơi đông người</li>';
  h+='<li>Không chuyển tiền trước cho người lạ</li>';
  h+='<li>Báo cáo nếu phát hiện lừa đảo</li>';
  h+='</ul></div></div>';

  // === SIMILAR PRODUCTS ===
  h+='<div class="similar-section"><h3>Sản phẩm tương tự</h3><div class="similar-scroll" id="similarList"><div class="loading" style="padding:20px"><i class="fas fa-spinner spin"></i></div></div></div>';

  // === REVIEWS ===
  h+='<div class="reviews-section"><h3 style="font-size:16px;font-weight:700;margin-bottom:12px"><i class="fas fa-star" style="color:#f5a623;margin-right:6px"></i>Đánh giá từ người mua</h3>';
  h+='<div class="review-empty"><i class="far fa-comment-dots" style="font-size:32px;margin-bottom:8px"></i><p>Chưa có đánh giá nào</p><p style="font-size:12px;margin-top:4px">Hãy là người đầu tiên đánh giá sản phẩm này</p></div>';
  h+='</div>';

  document.getElementById("content").innerHTML=h;
}

// === CAROUSEL ===
function onCarouselScroll(){
  var track=document.getElementById("carouselTrack");
  if(!track)return;
  var idx=Math.round(track.scrollLeft/track.offsetWidth);
  if(idx!==curImg){
    curImg=idx;
    var dots=document.querySelectorAll("#carouselDots span");
    dots.forEach(function(d,i){d.className=i===idx?"active":"";});
    var counter=document.getElementById("imgCounter");
    if(counter) counter.textContent=(idx+1)+"/"+imgs.length;
  }
}

// === SECTIONS ===
function toggleSection(el){
  el.classList.toggle("open");
  var body=el.nextElementSibling;
  body.classList.toggle("open");
}

// === SIMILAR ===
async function loadSimilar(){
  try{
    var r=await fetch("/api/marketplace.php?category="+(itemData.category||"")+"&limit=10");
    var d=await r.json();
    if(!d.success||!d.data.items)return;
    var items=d.data.items.filter(function(x){return x.id!=itemId;}).slice(0,8);
    if(!items.length){document.getElementById("similarList").innerHTML='<span style="color:var(--muted);font-size:13px">Không có sản phẩm tương tự</span>';return;}
    document.getElementById("similarList").innerHTML=items.map(function(item){
      var simImgs=[];try{simImgs=JSON.parse(item.images||"[]");}catch(e){}
      var img=simImgs.length>0?simImgs[0]:"";
      var pr=item.price>0?Number(item.price).toLocaleString("vi-VN")+" ₫":"Miễn phí";
      return '<div class="similar-card" onclick="location.href=\'listing.html?id='+item.id+'\'">'
        +(img?'<img src="'+img+'" loading=lazy>':'<div style="width:100%;aspect-ratio:1;background:var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#ccc"><i class="fas fa-image"></i></div>')
        +'<div class="s-price">'+pr+'</div>'
        +'<div class="s-title">'+esc(item.title)+'</div></div>';
    }).join("");
  }catch(e){}
}

// === ACTIONS ===
function toggleWish(){
  var icon=document.querySelectorAll("#wishIcon, #bbWish i");
  icon.forEach(function(i){
    if(i.classList.contains("far")){i.className="fas fa-heart";i.style.color="var(--primary)";}
    else{i.className="far fa-heart";i.style.color="";}
  });
}

function chatSeller(){
  if(!CU){location.href="login.html";return;}
  if(itemData) location.href="messages.html?to="+itemData.user_id;
}

function buyNow(){
  if(!CU){location.href="login.html";return;}
  chatSeller();
}

function shareListing(){
  var url=location.href;
  if(navigator.share) navigator.share({title:itemData?itemData.title:"",url:url});
  else{navigator.clipboard.writeText(url);alert("Đã copy link!");}
}

function openLB(src){
  document.getElementById("lbImg").src=src;
  document.getElementById("lightbox").classList.add("open");
}

// === UTILS ===
function esc(t){return t?String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"):"";}
function catName(c){var m={"xe_co":"Xe cộ","dien_thoai":"Điện thoại","do_dien_tu":"Đồ điện tử","thoi_trang":"Thời trang","do_gia_dung":"Đồ gia dụng","khac":"Khác"};return m[c]||c||"Khác";}
function formatDate(dt){if(!dt)return"";try{var d=new Date(dt.replace(" ","T"));return d.toLocaleDateString("vi-VN");}catch(e){return dt;}}

function contactSeller(sellerId, listingTitle){
  var token=localStorage.getItem('token');
  if(!token){toast('Đăng nhập để liên hệ!');setTimeout(function(){location='login.html'},1000);return;}
  // Create or open conversation with seller
  fetch('/api/messages-api.php?action=create_conversation',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({user_id:sellerId,initial_message:'Xin chào! Tôi quan tâm đến: '+(listingTitle||'sản phẩm của bạn')})})
    .then(function(r){return r.json()})
    .then(function(d){
      if(d.success&&d.data&&d.data.conversation_id){
        location.href='messages.html?conv='+d.data.conversation_id;
      }else{toast(d.message||'Lỗi','error');}
    }).catch(function(){toast('Lỗi kết nối','error');});
}
