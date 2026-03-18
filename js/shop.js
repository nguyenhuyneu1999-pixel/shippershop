// ============================================
// SHIPPERSHOP JAVASCRIPT - COMPLETE
// ============================================

// SAMPLE PRODUCTS DATABASE
const PRODUCTS = [
    {
        id: 1,
        name: 'iPhone 15 Pro Max 256GB',
        slug: 'iphone-15-pro-max',
        category: 'dien-thoai',
        categoryName: 'Điện Thoại',
        price: 29990000,
        salePrice: 25990000,
        image: 'https://via.placeholder.com/600x600/1A1A1A/FFFFFF?text=iPhone+15+Pro',
        gallery: [
            'https://via.placeholder.com/600x600/1A1A1A/FFFFFF?text=iPhone+15+Pro',
            'https://via.placeholder.com/600x600/333333/FFFFFF?text=iPhone+Back',
            'https://via.placeholder.com/600x600/555555/FFFFFF?text=iPhone+Side'
        ],
        rating: 4.8,
        reviews: 1234,
        stock: 50,
        description: 'iPhone 15 Pro Max với chip A17 Pro mạnh mẽ, camera 48MP, màn hình ProMotion 120Hz',
        specs: {
            'Màn hình': '6.7 inch Super Retina XDR',
            'Chip': 'Apple A17 Pro',
            'RAM': '8GB',
            'Bộ nhớ': '256GB',
            'Camera': '48MP + 12MP + 12MP',
            'Pin': '4422mAh'
        },
        badge: '-13%',
        featured: true
    },
    {
        id: 2,
        name: 'Samsung Galaxy S24 Ultra 512GB',
        slug: 'samsung-s24-ultra',
        category: 'dien-thoai',
        categoryName: 'Điện Thoại',
        price: 27990000,
        salePrice: 23990000,
        image: 'https://via.placeholder.com/600x600/1A1A1A/FFFFFF?text=Samsung+S24',
        gallery: [
            'https://via.placeholder.com/600x600/1A1A1A/FFFFFF?text=Samsung+S24',
            'https://via.placeholder.com/600x600/333333/FFFFFF?text=S24+Back',
            'https://via.placeholder.com/600x600/555555/FFFFFF?text=S24+Side'
        ],
        rating: 4.7,
        reviews: 890,
        stock: 30,
        description: 'Galaxy S24 Ultra với S Pen tích hợp, camera 200MP, hiệu năng đỉnh cao',
        specs: {
            'Màn hình': '6.8 inch Dynamic AMOLED 2X',
            'Chip': 'Snapdragon 8 Gen 3',
            'RAM': '12GB',
            'Bộ nhớ': '512GB',
            'Camera': '200MP + 50MP + 12MP + 10MP',
            'Pin': '5000mAh'
        },
        badge: '-14%',
        featured: true
    },
    {
        id: 3,
        name: 'MacBook Pro M3 14 inch 16GB',
        slug: 'macbook-pro-m3',
        category: 'laptop',
        categoryName: 'Laptop',
        price: 45990000,
        salePrice: 42990000,
        image: 'https://via.placeholder.com/600x600/1A1A1A/FFFFFF?text=MacBook+M3',
        gallery: [
            'https://via.placeholder.com/600x600/1A1A1A/FFFFFF?text=MacBook+M3',
            'https://via.placeholder.com/600x600/333333/FFFFFF?text=MacBook+Side',
            'https://via.placeholder.com/600x600/555555/FFFFFF?text=MacBook+Open'
        ],
        rating: 4.9,
        reviews: 567,
        stock: 20,
        description: 'MacBook Pro với chip M3 mạnh mẽ, màn hình Liquid Retina XDR, pin 22 giờ',
        specs: {
            'Màn hình': '14.2 inch Liquid Retina XDR',
            'Chip': 'Apple M3',
            'RAM': '16GB',
            'SSD': '512GB',
            'Card đồ họa': 'GPU 10-core',
            'Pin': '70Wh - 22 giờ'
        },
        badge: '-7%',
        featured: true
    },
    {
        id: 4,
        name: 'Áo Thun Nam Premium Cotton',
        slug: 'ao-thun-nam-premium',
        category: 'thoi-trang',
        categoryName: 'Thời Trang',
        price: 299000,
        salePrice: 199000,
        image: 'https://via.placeholder.com/600x600/7C3AED/FFFFFF?text=Ao+Thun+Nam',
        gallery: [
            'https://via.placeholder.com/600x600/7C3AED/FFFFFF?text=Ao+Thun',
            'https://via.placeholder.com/600x600/A78BFA/FFFFFF?text=Detail',
            'https://via.placeholder.com/600x600/FFB84D/FFFFFF?text=Model'
        ],
        rating: 4.5,
        reviews: 2341,
        stock: 500,
        description: 'Áo thun nam chất cotton 100%, form regular fit thoải mái, nhiều màu',
        specs: {
            'Chất liệu': '100% Cotton',
            'Form': 'Regular Fit',
            'Màu sắc': 'Đen, Trắng, Xám, Navy',
            'Size': 'S, M, L, XL, XXL'
        },
        badge: '-33%',
        featured: false
    },
    {
        id: 5,
        name: 'Son Dior Rouge 999 Matte',
        slug: 'son-dior-rouge-999',
        category: 'my-pham',
        categoryName: 'Mỹ Phẩm',
        price: 1290000,
        salePrice: 990000,
        image: 'https://via.placeholder.com/600x600/A78BFA/FFFFFF?text=Dior+999',
        gallery: [
            'https://via.placeholder.com/600x600/A78BFA/FFFFFF?text=Dior+999',
            'https://via.placeholder.com/600x600/7C3AED/FFFFFF?text=Swatch',
            'https://via.placeholder.com/600x600/7C3AED/FFFFFF?text=Packaging'
        ],
        rating: 4.9,
        reviews: 3456,
        stock: 100,
        description: 'Son Dior màu đỏ huyền thoại, lâu trôi 16h, dưỡng môi tốt',
        specs: {
            'Màu sắc': '999 - Đỏ Dior',
            'Finish': 'Matte',
            'Dung tích': '3.5g',
            'Xuất xứ': 'France'
        },
        badge: '-23%',
        featured: true
    },
    {
        id: 6,
        name: 'Nồi Cơm Điện Panasonic 1.8L',
        slug: 'noi-com-panasonic',
        category: 'gia-dung',
        categoryName: 'Gia Dụng',
        price: 2990000,
        salePrice: 1990000,
        image: 'https://via.placeholder.com/600x600/00C851/FFFFFF?text=Noi+Com',
        gallery: [
            'https://via.placeholder.com/600x600/00C851/FFFFFF?text=Noi+Com',
            'https://via.placeholder.com/600x600/009624/FFFFFF?text=Inside',
            'https://via.placeholder.com/600x600/007E2D/FFFFFF?text=Cooking'
        ],
        rating: 4.6,
        reviews: 789,
        stock: 80,
        description: 'Nồi cơm điện cao cấp, công nghệ IH, giữ ấm 12h',
        specs: {
            'Dung tích': '1.8L',
            'Công suất': '1350W',
            'Công nghệ': 'IH Cao tần',
            'Lòng nồi': 'Chống dính 5 lớp'
        },
        badge: '-33%',
        featured: false
    }
];

// SAMPLE REVIEWS
const REVIEWS = {
    1: [
        { user: 'Nguyễn Văn A', rating: 5, comment: 'Máy quá đỉnh, camera đẹp lắm!', date: '2024-02-10', verified: true },
        { user: 'Trần Thị B', rating: 4, comment: 'Tốt nhưng hơi đắt', date: '2024-02-09', verified: true },
        { user: 'Lê Văn C', rating: 5, comment: 'Giao hàng nhanh, đóng gói cẩn thận', date: '2024-02-08', verified: false }
    ],
    2: [
        { user: 'Phạm Thị D', rating: 5, comment: 'S Pen rất hay, viết mượt', date: '2024-02-10', verified: true },
        { user: 'Hoàng Văn E', rating: 4, comment: 'Pin trâu, hiệu năng mạnh', date: '2024-02-09', verified: true }
    ]
};

// ============================================
// UTILITY FUNCTIONS
// ============================================

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

function calculateDiscount(price, salePrice) {
    return Math.round(((price - salePrice) / price) * 100);
}

function getProductById(id) {
    return PRODUCTS.find(p => p.id === parseInt(id));
}

function getProductsByCategory(category) {
    if (!category) return PRODUCTS;
    return PRODUCTS.filter(p => p.category === category);
}

function searchProducts(query) {
    const q = query.toLowerCase();
    return PRODUCTS.filter(p => 
        p.name.toLowerCase().includes(q) ||
        p.categoryName.toLowerCase().includes(q) ||
        p.description.toLowerCase().includes(q)
    );
}

// ============================================
// CART MANAGEMENT
// ============================================

class Cart {
    constructor() {
        this.items = this.load();
    }

    load() {
        const saved = localStorage.getItem('cart');
        return saved ? JSON.parse(saved) : [];
    }

    save() {
        localStorage.setItem('cart', JSON.stringify(this.items));
        this.updateUI();
    }

    add(productId, quantity = 1) {
        const product = getProductById(productId);
        if (!product) return;

        const existing = this.items.find(item => item.id === productId);
        
        if (existing) {
            existing.quantity += quantity;
        } else {
            this.items.push({
                id: product.id,
                name: product.name,
                image: product.image,
                price: product.salePrice,
                quantity: quantity
            });
        }

        this.save();
        this.showNotification(`Đã thêm ${product.name} vào giỏ hàng!`);
    }

    remove(productId) {
        this.items = this.items.filter(item => item.id !== productId);
        this.save();
    }

    updateQuantity(productId, quantity) {
        const item = this.items.find(item => item.id === productId);
        if (item) {
            item.quantity = Math.max(1, quantity);
            this.save();
        }
    }

    clear() {
        this.items = [];
        this.save();
    }

    getCount() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    }

    getTotal() {
        return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    }

    updateUI() {
        const countEl = document.getElementById('cartCount');
        if (countEl) {
            countEl.textContent = this.getCount();
        }
    }

    showNotification(message) {
        // Simple notification (can be replaced with better UI)
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Global cart instance
const cart = new Cart();

// ============================================
// PRODUCT CARD RENDERING
// ============================================

function createProductCard(product) {
    const discount = calculateDiscount(product.price, product.salePrice);
    
    return `
        <div class="product-card">
            ${product.badge ? `<div class="product-badge">${product.badge}</div>` : ''}
            
            <div class="product-actions">
                <button class="action-btn" onclick="addToWishlist(${product.id})" title="Yêu thích">
                    <i class="far fa-heart"></i>
                </button>
                <button class="action-btn" onclick="quickView(${product.id})" title="Xem nhanh">
                    <i class="far fa-eye"></i>
                </button>
            </div>

            <a href="product.html?id=${product.id}" class="product-image">
                <img src="${product.image}" alt="${product.name}" loading="lazy">
            </a>

            <div class="product-info">
                <div class="product-category">${product.categoryName}</div>
                <h3 class="product-name">
                    <a href="product.html?id=${product.id}">${product.name}</a>
                </h3>
                
                <div class="product-rating">
                    <div class="stars">
                        ${'★'.repeat(Math.floor(product.rating))}${'☆'.repeat(5 - Math.floor(product.rating))}
                    </div>
                    <span class="rating-count">(${product.reviews})</span>
                </div>

                <div class="product-price">
                    <span class="current-price">${formatCurrency(product.salePrice)}</span>
                    ${product.price !== product.salePrice ? `
                        <span class="original-price">${formatCurrency(product.price)}</span>
                        <span class="discount-percent">-${discount}%</span>
                    ` : ''}
                </div>

                <button class="add-to-cart-btn" onclick="addToCart(${product.id})">
                    <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                </button>
            </div>
        </div>
    `;
}

// ============================================
// GLOBAL FUNCTIONS (called from HTML)
// ============================================

function addToCart(productId) {
    cart.add(productId, 1);
}

function addToWishlist(productId) {
    // TODO: Implement wishlist
    if(typeof toast==='function')toast('Đã thêm vào yêu thích');;
}

function quickView(productId) {
    // Redirect to product page (can be modal in future)
    window.location.href = `product.html?id=${productId}`;
}

function searchProducts() {
    const query = document.getElementById('searchInput').value;
    if (query.trim()) {
        window.location.href = `shop.html?search=${encodeURIComponent(query)}`;
    }
}

// ============================================
// PAGE INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Update cart count
    cart.updateUI();

    // Check authentication
    checkAuth();

    // Page-specific initialization
    const page = window.location.pathname.split('/').pop();

    if (page === 'index.html' || page === '') {
        initHomePage();
    } else if (page === 'shop.html') {
        initShopPage();
    } else if (page === 'product.html') {
        initProductPage();
    } else if (page === 'cart.html') {
        initCartPage();
    }

    // Search on Enter key
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }
});

// ============================================
// PAGE-SPECIFIC INITIALIZATION
// ============================================

function initHomePage() {
    loadFlashSaleProducts();
    loadFeaturedProducts();
    startCountdown();
}

function loadFlashSaleProducts() {
    const container = document.getElementById('flashSaleProducts');
    if (!container) return;

    const flashProducts = PRODUCTS.slice(0, 4);
    container.innerHTML = flashProducts.map(createProductCard).join('');
}

function loadFeaturedProducts() {
    const container = document.getElementById('featuredProducts');
    if (!container) return;

    const featured = PRODUCTS.filter(p => p.featured);
    container.innerHTML = featured.map(createProductCard).join('');
}

function startCountdown() {
    const countdownEl = document.getElementById('countdown');
    if (!countdownEl) return;

    let hours = 2, minutes = 45, seconds = 30;

    setInterval(() => {
        seconds--;
        if (seconds < 0) {
            seconds = 59;
            minutes--;
        }
        if (minutes < 0) {
            minutes = 59;
            hours--;
        }
        if (hours < 0) {
            hours = 23;
        }

        countdownEl.textContent = 
            String(hours).padStart(2, '0') + ':' +
            String(minutes).padStart(2, '0') + ':' +
            String(seconds).padStart(2, '0');
    }, 1000);
}

function initShopPage() {
    // Shop page will be implemented in shop.html
}

function initProductPage() {
    // Product page will be implemented in product.html
}

function initCartPage() {
    // Cart page will be implemented in cart.html
}

// ============================================
// AUTHENTICATION
// ============================================

function checkAuth() {
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    const authBtn = document.getElementById('authBtn');
    
    if (authBtn && user) {
        authBtn.innerHTML = `
            <i class="fas fa-user-circle"></i>
            <span>${user.fullname || user.email}</span>
        `;
        authBtn.href = 'profile.html';
    }
}

function logout() {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
    window.location.href = 'index.html';
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { cart, PRODUCTS, formatCurrency };
}
