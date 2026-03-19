/* ShipperShop Analytics Tracker - lightweight, privacy-friendly */
(function(){
var sid=localStorage.getItem("ss_sid");
if(!sid){sid="s"+Math.random().toString(36).substr(2,12)+Date.now().toString(36);localStorage.setItem("ss_sid",sid);}
var u=JSON.parse(localStorage.getItem("user")||"null");
var uid=u?u.id:0;
var data={page:location.pathname+location.search,referrer:document.referrer,sid:sid,uid:uid};
setTimeout(function(){
  try{
    var x=new XMLHttpRequest();
    x.open("POST","/api/analytics.php?action=view",true);
    x.setRequestHeader("Content-Type","application/json");
    x.send(JSON.stringify(data));
  }catch(e){}
},1000);
})();
