// ShipperShop Search Overlay — uses Global Search API
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
    fetch('/api/search.php?q='+encodeURIComponent(val)+'&limit=5')
    .then(function(res){return res.json()})
    .then(function(d){
        if(!d.success||!d.data){r.innerHTML='<div style="padding:20px;text-align:center;color:#999">Lỗi tìm kiếm</div>';return;}
        var results=d.data.results||{};
        var html='';
        // Users
        var users=results.users||[];
        if(users.length){
            html+='<div style="padding:10px 16px;font-weight:700;font-size:13px;color:#65676B">Người dùng</div>';
            users.forEach(function(u){
                var av=u.avatar?'<img src="'+u.avatar+'" style="width:36px;height:36px;border-radius:50%;object-fit:cover">':'<div style="width:36px;height:36px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px">'+(u.fullname||'U')[0]+'</div>';
                html+='<a href="user.html?id='+u.id+'" style="padding:8px 16px;display:flex;gap:10px;align-items:center;text-decoration:none;color:inherit" onclick="closeSearch()">'+av+'<div><div style="font-weight:600;font-size:14px">'+esc(u.fullname||'')+'</div><div style="font-size:12px;color:#65676B">'+(u.shipping_company||'')+'</div></div></a>';
            });
        }
        // Groups
        var groups=results.groups||[];
        if(groups.length){
            html+='<div style="padding:10px 16px;font-weight:700;font-size:13px;color:#65676B">Hội nhóm</div>';
            groups.forEach(function(g){
                var icon=g.icon_image?'<img src="'+g.icon_image+'" style="width:36px;height:36px;border-radius:8px;object-fit:cover">':'<div style="width:36px;height:36px;border-radius:8px;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px">👥</div>';
                html+='<a href="group.html?id='+g.id+'" style="padding:8px 16px;display:flex;gap:10px;align-items:center;text-decoration:none;color:inherit" onclick="closeSearch()">'+icon+'<div><div style="font-weight:600;font-size:14px">'+esc(g.name||'')+'</div><div style="font-size:12px;color:#65676B">'+(g.member_count||0)+' thành viên</div></div></a>';
            });
        }
        // Posts
        var posts=results.posts||[];
        if(posts.length){
            html+='<div style="padding:10px 16px;font-weight:700;font-size:13px;color:#65676B">Bài viết</div>';
            posts.forEach(function(p){
                html+='<div style="padding:8px 16px;cursor:pointer;border-bottom:1px solid #f0f0f0" onclick="closeSearch();location.href=\'post-detail.html?id='+p.id+'\'"><div style="font-size:13px;font-weight:600;color:#333;margin-bottom:2px">'+esc(p.user_name||'Ẩn danh')+'</div><div style="font-size:12px;color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+esc((p.content||'').substring(0,80))+'</div></div>';
            });
        }
        if(!html){html='<div style="padding:20px;text-align:center;color:#999">Không tìm thấy kết quả</div>';}
        r.innerHTML=html;
    }).catch(function(){r.innerHTML='<div style="padding:20px;text-align:center;color:#999">Lỗi kết nối</div>';});
}
