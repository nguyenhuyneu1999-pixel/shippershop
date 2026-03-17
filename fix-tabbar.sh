#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)
echo "Backup done"
sed -i "s|\.tab-bar{display:flex;background:#fff;border-bottom:1px solid var(--border);position:sticky;top:48px;z-index:99;padding:0;box-shadow:0 1px 2px rgba(0,0,0,\.06);}|.tab-bar{display:flex;background:#fff;position:sticky;top:48px;z-index:99;padding:0;}|" index.html
sed -i "s|\.tab-item{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:8px 0;font-size:10px;color:var(--muted);text-decoration:none;border-bottom:2px solid transparent;transition:\.15s;}|.tab-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0;padding:6px 0;font-size:9px;color:var(--muted);text-decoration:none;border-bottom:3px solid transparent;transition:.15s;position:relative;}.tab-item span{display:none;}@media(min-width:769px){.tab-item span{display:block;}}|" index.html
sed -i "s|\.tab-item\.active{color:var(--primary);border-bottom-color:var(--primary);}|.tab-item.active{color:var(--primary);border-bottom-color:var(--primary);}|" index.html
sed -i "s|\.tab-item i{font-size:18px;}|.tab-item i{font-size:22px;}|" index.html
sed -i "s|\.tab-badge{position:absolute;top:4px;right:calc(50% - 16px);background:#EE4D2D;color:#fff;font-size:9px;min-width:14px;height:14px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;}|.tab-badge{position:absolute;top:2px;right:calc(50% - 18px);background:#EE4D2D;color:#fff;font-size:9px;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;padding:0 4px;}|" index.html
sed -i "s|\.top-nav{background:#fff;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;padding:8px 10px;display:flex;align-items:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,\.12);}|.top-nav{background:#fff;position:sticky;top:0;z-index:100;padding:8px 10px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #f0f0f0;}|" index.html
echo "Done! Check https://shippershop.vn"
