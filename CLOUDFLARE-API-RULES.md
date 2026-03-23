# Cloudflare Cache Rules for ShipperShop API

## Setup (free plan, 5 minutes)

### 1. Add site to Cloudflare
- Go to dash.cloudflare.com → Add Site → shippershop.vn
- Select Free plan
- Update nameservers at your domain registrar

### 2. Cache Rules (Caching → Cache Rules)

**Rule 1: Static Feed (AGGRESSIVE cache)**
- If: URI Path starts with `/api/static/`
- Then: Cache everything, Edge TTL 30s, Browser TTL 15s
- Status: Enabled

**Rule 2: API GET (short cache)**
- If: URI Path starts with `/api/` AND Request Method equals `GET` AND NOT URI Path contains `auth` AND NOT URI Path contains `wallet` AND NOT URI Path contains `messages` AND NOT URI Path contains `admin`
- Then: Cache everything, Edge TTL 10s, Browser TTL 0
- Status: Enabled

**Rule 3: Static Assets (long cache)**
- If: URI Path ends with `.js` OR `.css` OR `.woff2` OR `.jpg` OR `.png` OR `.webp`
- Then: Cache everything, Edge TTL 1 month, Browser TTL 1 week
- Status: Enabled

### 3. Page Rules (Rules → Page Rules)

**Rule 1: API static**
`shippershop.vn/api/static/*`
→ Cache Level: Cache Everything, Edge Cache TTL: 30 seconds

**Rule 2: HTML pages**
`shippershop.vn/*.html`
→ Cache Level: Cache Everything, Edge Cache TTL: 5 minutes

### 4. Speed Settings
- Auto Minify: HTML, CSS, JS → ON
- Brotli: ON
- Early Hints: ON
- Rocket Loader: OFF (conflicts with inline JS)

### 5. Expected Results
| Endpoint | Before (shared hosting) | After (Cloudflare) |
|----------|------------------------|---------------------|
| Static feed | 200-800ms | **<20ms** (edge) |
| PHP API | 200-800ms | **<20ms** (cached GET) |
| HTML pages | 200-500ms | **<10ms** (edge) |
| Images | 200-400ms | **<5ms** (CDN) |
| JS/CSS | 200-400ms | **<5ms** (CDN) |

### 6. Cache Purge After Deploy
Add to deploy script:
```bash
# Purge Cloudflare cache after git push
curl -X POST "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID/purge_cache" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"purge_everything":true}'
```

## Result: 100K+ Users on Shared Hosting
- Cloudflare handles 95% of requests (never reaches PHP)
- Only auth/write requests hit PHP server
- 1M monthly page views → ~50K actual server requests
