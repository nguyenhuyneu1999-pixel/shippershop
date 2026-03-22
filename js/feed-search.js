// ShipperShop Search Overlay
function openSearch(){
    document.getElementById('searchOverlay').style.display='block';
    document.body.style.overflow='hidden';
    setTimeout(function(){document.getElementById('sInput').focus();},100);
}
function closeSearch(){
    document.getElementById('searchOverlay').style.display='none';
    document.body.style.overflow='';
}
function filterSearch(t){
    closeSearch();
    if(t==='all'){type='all';}else{type=t;}
    page=1;
    document.querySelectorAll('[id^="t-"]').forEach(function(i){i.classList.remove('active');});
    var el=document.getElementById('t-'+t);if(el)el.classList.add('active');
    loadPosts();
}
function filterShip(c){
    closeSearch();
    company=(company===c)?'':c;
    document.getElementById('q').value=company?c:'';
    page=1;
    loadPosts();
}
function filterProv(pv){
    closeSearch();
    prov=pv;page=1;loadPosts();
}
function doSearch(val){
    company='';
    document.getElementById('q').value=val;
    page=1;search();
}
function liveSearch(val){
    var r=document.getElementById('sResults');
    if(val.length<2){r.innerHTML='';return;}
    r.innerHTML='<div style="padding:16px;text-align:center;color:#999"><i class="fas fa-spinner fa-spin"></i> Đang tìm...</div>';
    fetch('/api/posts.php?search='+encodeURIComponent(val)+'&limit=5')
    .then(function(res){return res.json()})
    .then(function(d){
        if(d.success&&d.data.posts.length){
            r.innerHTML='<div style="padding:10px 16px;font-weight:700;font-size:15px;color:#333">Kết quả</div>'
            +d.data.posts.map(function(p){
                return '<div style="padding:10px 16px;display:flex;gap:10px;cursor:pointer;border-bottom:1px solid #f0f0f0" onclick="closeSearch();scrollTo2('+p.id+')">'
                +'<div style="flex:1;min-width:0"><div style="font-size:13px;font-weight:600;color:#333;margin-bottom:2px">'+esc((p.user_name||"Ẩn danh"))+'</div>'
                +'<div style="font-size:12px;color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+esc((p.content||"").substring(0,80))+'</div></div></div>';
            }).join('');
        }else{
            r.innerHTML='<div style="padding:20px;text-align:center;color:#999">Không tìm thấy kết quả</div>';
        }
    }).catch(function(){r.innerHTML='';});
}
