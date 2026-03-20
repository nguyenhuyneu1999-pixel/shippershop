<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cộng đồng - ShipperShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .community-page {
            padding: 40px 0;
        }

        .community-container {
            display: grid;
            grid-template-columns: 280px 1fr 300px;
            gap: 30px;
        }

        /* LEFT SIDEBAR */
        .left-sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .sidebar-card {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .sidebar-title {
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 8px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            color: var(--gray-dark);
            transition: var(--transition-fast);
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(238, 77, 45, 0.1);
            color: var(--primary);
        }

        .sidebar-menu i {
            width: 20px;
        }

        /* MAIN FEED */
        .main-feed {
            max-width: 600px;
        }

        .create-post-card {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .create-post-header {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .user-avatar-small {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        .post-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid var(--gray-light);
            border-radius: 25px;
            cursor: pointer;
            background: var(--light);
        }

        .post-input:hover {
            background: var(--gray-light);
        }

        .post-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-light);
        }

        .post-action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: none;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            color: var(--gray);
            font-weight: 500;
        }

        .post-action-btn:hover {
            background: var(--light);
        }

        .post-action-btn i {
            font-size: 20px;
        }

        /* POST CARD */
        .post-card {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .post-user-info {
            display: flex;
            gap: 12px;
        }

        .post-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        .post-user-details {
            flex: 1;
        }

        .post-username {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 3px;
        }

        .post-time {
            font-size: 13px;
            color: var(--gray);
        }

        .post-menu-btn {
            background: none;
            border: none;
            color: var(--gray);
            font-size: 20px;
            cursor: pointer;
            padding: 5px 10px;
        }

        .post-content {
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .post-image {
            width: 100%;
            border-radius: 12px;
            margin-bottom: 15px;
            cursor: pointer;
        }

        .post-stats {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-top: 1px solid var(--gray-light);
            border-bottom: 1px solid var(--gray-light);
            margin-bottom: 12px;
            font-size: 14px;
            color: var(--gray);
        }

        .post-stat {
            cursor: pointer;
        }

        .post-stat:hover {
            text-decoration: underline;
        }

        .post-interactions {
            display: flex;
            gap: 8px;
        }

        .interact-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: none;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            color: var(--gray);
            font-weight: 500;
            transition: var(--transition-fast);
        }

        .interact-btn:hover {
            background: var(--light);
        }

        .interact-btn.liked {
            color: var(--danger);
        }

        .interact-btn i {
            font-size: 18px;
        }

        .comments-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-light);
            display: none;
        }

        .comments-section.show {
            display: block;
        }

        .comment-item {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .comment-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--gray-light);
            color: var(--gray-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        .comment-content {
            flex: 1;
            background: var(--light);
            padding: 10px 14px;
            border-radius: 12px;
        }

        .comment-username {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .comment-text {
            font-size: 14px;
            line-height: 1.5;
        }

        .comment-time {
            font-size: 12px;
            color: var(--gray);
            margin-top: 5px;
        }

        .comment-input-wrapper {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .comment-input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid var(--gray-light);
            border-radius: 20px;
            background: var(--light);
        }

        .comment-submit {
            padding: 10px 20px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
        }

        /* RIGHT SIDEBAR */
        .right-sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .trending-card {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .trending-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-light);
            cursor: pointer;
        }

        .trending-item:last-child {
            border-bottom: none;
        }

        .trending-item:hover {
            opacity: 0.8;
        }

        .trending-tag {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 3px;
        }

        .trending-count {
            font-size: 13px;
            color: var(--gray);
        }

        .suggested-users {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
        }

        .suggested-user {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .suggested-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .suggested-info {
            flex: 1;
        }

        .suggested-name {
            font-weight: 600;
            font-size: 14px;
        }

        .suggested-bio {
            font-size: 12px;
            color: var(--gray);
        }

        .follow-btn {
            padding: 6px 16px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
        }

        .follow-btn.following {
            background: var(--gray-light);
            color: var(--gray-dark);
        }

        /* CREATE POST MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--white);
            border-radius: 15px;
            width: 90%;
            max-width: 550px;
            max-height: 90vh;
            display: flex;
            flex-direction: column; /* Flex column để footer luôn hiển thị */
            overflow: hidden;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0; /* Không co lại */
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray);
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto; /* Chỉ body scroll */
            flex: 1; /* Chiếm hết không gian còn lại */
        }

        .post-textarea {
            width: 100%;
            border: none;
            font-size: 16px;
            min-height: 120px;
            resize: vertical;
            padding: 10px;
        }

        .post-textarea:focus {
            outline: none;
        }

        .image-preview {
            margin-top: 15px;
            position: relative;
        }

        .image-preview img {
            width: 100%;
            border-radius: 10px;
        }

        .remove-image {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.6);
            color: var(--white);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0; /* Không co lại - luôn hiển thị */
            background: var(--white);
        }

        .submit-post-btn {
            padding: 10px 30px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            min-height: 44px; /* Touch-friendly */
        }

        .submit-post-btn:disabled {
            background: var(--gray-light);
            cursor: not-allowed;
        }

        /* MOBILE FIXES */
        @media (max-width: 768px) {
            .modal {
                align-items: flex-end; /* Modal xuất hiện từ dưới lên trên mobile */
            }

            .modal-content {
                width: 100%;
                max-width: 100%;
                max-height: 85vh;
                border-radius: 20px 20px 0 0; /* Bo góc trên */
            }

            .modal-footer {
                padding: 12px 16px;
                padding-bottom: max(12px, env(safe-area-inset-bottom)); /* Safe area cho iPhone */
            }

            .submit-post-btn {
                padding: 12px 24px;
                font-size: 15px;
            }
        }

        @media (max-width: 992px) {
            .community-container {
                grid-template-columns: 1fr;
            }

            .left-sidebar,
            .right-sidebar {
                display: none;
            }

            .main-feed {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="header-top">
            <div class="container">
                <div class="d-flex justify-between align-center">
                    <div class="header-top-left">
                        <span>📞 1900-6868</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-main">
            <div class="container">
                <div class="header-content">
                    <a href="index.html" class="logo">🛒 SHIPPER<span>SHOP</span></a>
                    <div class="search-bar">
                        <input type="text" placeholder="Tìm kiếm...">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                    <div class="header-actions">
                        <a href="cart.html" class="header-btn">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-badge" id="cartCount">0</span>
                        </a>
                        <a href="profile.html" class="header-btn" id="authBtn">
                            <i class="fas fa-user"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <nav>
            <div class="container">
                <ul class="nav-menu">
                    <li><a href="index.html">Trang chủ</a></li>
                    <li><a href="shop.html">Sản phẩm</a></li>
                    <li><a href="community.html" class="active">Cộng đồng</a></li>
                    <li><a href="wallet.html">Ví tiền</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- COMMUNITY CONTENT -->
    <div class="container community-page">
        <div class="community-container">
            <!-- LEFT SIDEBAR -->
            <aside class="left-sidebar">
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Menu</h3>
                    <ul class="sidebar-menu">
                        <li><a href="#" class="active">
                            <i class="fas fa-home"></i> Bảng tin
                        </a></li>
                        <li><a href="#" onclick="filterPosts('trending'); return false;">
                            <i class="fas fa-fire"></i> Thịnh hành
                        </a></li>
                        <li><a href="#" onclick="filterPosts('following'); return false;">
                            <i class="fas fa-users"></i> Đang theo dõi
                        </a></li>
                        <li><a href="#" onclick="filterPosts('saved'); return false;">
                            <i class="fas fa-bookmark"></i> Đã lưu
                        </a></li>
                    </ul>
                </div>

                <div class="sidebar-card">
                    <h3 class="sidebar-title">Chủ đề</h3>
                    <ul class="sidebar-menu">
                        <li><a href="#" onclick="filterByTag('review'); return false;">
                            <i class="fas fa-star"></i> Review sản phẩm
                        </a></li>
                        <li><a href="#" onclick="filterByTag('tips'); return false;">
                            <i class="fas fa-lightbulb"></i> Mẹo mua sắm
                        </a></li>
                        <li><a href="#" onclick="filterByTag('sale'); return false;">
                            <i class="fas fa-tag"></i> Săn sale
                        </a></li>
                        <li><a href="#" onclick="filterByTag('qa'); return false;">
                            <i class="fas fa-question-circle"></i> Hỏi đáp
                        </a></li>
                    </ul>
                </div>
            </aside>

            <!-- MAIN FEED -->
            <main class="main-feed">
                <!-- Create Post -->
                <div class="create-post-card">
                    <div class="create-post-header">
                        <div class="user-avatar-small" id="createPostAvatar">K</div>
                        <div class="post-input" onclick="openCreatePostModal()">
                            Bạn đang nghĩ gì?
                        </div>
                    </div>
                    <div class="post-actions">
                        <button class="post-action-btn" onclick="openCreatePostModal()">
                            <i class="fas fa-image" style="color: #45bd62;"></i>
                            <span>Ảnh</span>
                        </button>
                        <button class="post-action-btn" onclick="openCreatePostModal()">
                            <i class="fas fa-star" style="color: #f7b928;"></i>
                            <span>Review</span>
                        </button>
                        <button class="post-action-btn" onclick="openCreatePostModal()">
                            <i class="fas fa-question" style="color: #1877f2;"></i>
                            <span>Hỏi đáp</span>
                        </button>
                    </div>
                </div>

                <!-- Posts Feed -->
                <div id="postsFeed">
                    <!-- Posts will be loaded here -->
                </div>

                <div id="loadingPosts" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i>
                    <p style="margin-top: 15px; color: var(--gray);">Đang tải bài viết...</p>
                </div>
            </main>

            <!-- RIGHT SIDEBAR -->
            <aside class="right-sidebar">
                <!-- Trending Topics -->
                <div class="trending-card">
                    <h3 class="sidebar-title">Xu hướng cho bạn</h3>
                    <div class="trending-item">
                        <div class="trending-tag">#SănSaleBlackFriday</div>
                        <div class="trending-count">2,458 bài viết</div>
                    </div>
                    <div class="trending-item">
                        <div class="trending-tag">#ReviewIPhone15</div>
                        <div class="trending-count">1,823 bài viết</div>
                    </div>
                    <div class="trending-item">
                        <div class="trending-tag">#MẹoMuaSắm</div>
                        <div class="trending-count">1,234 bài viết</div>
                    </div>
                    <div class="trending-item">
                        <div class="trending-tag">#BeautyTips</div>
                        <div class="trending-count">987 bài viết</div>
                    </div>
                </div>

                <!-- Suggested Users -->
                <div class="suggested-users">
                    <h3 class="sidebar-title">Gợi ý theo dõi</h3>
                    
                    <div class="suggested-user">
                        <div class="suggested-avatar">T</div>
                        <div class="suggested-info">
                            <div class="suggested-name">Tech Reviewer</div>
                            <div class="suggested-bio">Review công nghệ</div>
                        </div>
                        <button class="follow-btn" onclick="toggleFollow(this)">Theo dõi</button>
                    </div>

                    <div class="suggested-user">
                        <div class="suggested-avatar">B</div>
                        <div class="suggested-info">
                            <div class="suggested-name">Beauty Blogger</div>
                            <div class="suggested-bio">Làm đẹp & skincare</div>
                        </div>
                        <button class="follow-btn" onclick="toggleFollow(this)">Theo dõi</button>
                    </div>

                    <div class="suggested-user">
                        <div class="suggested-avatar">S</div>
                        <div class="suggested-info">
                            <div class="suggested-name">Sale Hunter</div>
                            <div class="suggested-bio">Săn sale chuyên nghiệp</div>
                        </div>
                        <button class="follow-btn" onclick="toggleFollow(this)">Theo dõi</button>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- CREATE POST MODAL -->
    <div class="modal" id="createPostModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Tạo bài viết</h3>
                <button class="modal-close" onclick="closeCreatePostModal()">×</button>
            </div>
            <div class="modal-body">
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <div class="user-avatar-small" id="modalAvatar">K</div>
                    <div>
                        <div style="font-weight: 600;" id="modalUsername">User</div>
                        <div style="font-size: 13px; color: var(--gray);">Công khai</div>
                    </div>
                </div>
                <textarea 
                    class="post-textarea" 
                    id="postContent" 
                    placeholder="Bạn đang nghĩ gì?"
                ></textarea>
                <div class="image-preview" id="imagePreview" style="display: none;">
                    <img id="previewImg" src="" alt="Preview">
                    <button class="remove-image" onclick="removeImage()">×</button>
                </div>
            </div>
            <div class="modal-footer">
                <div>
                    <input type="file" id="imageInput" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    <button class="post-action-btn" onclick="document.getElementById('imageInput').click()">
                        <i class="fas fa-image"></i> Thêm ảnh
                    </button>
                </div>
                <button class="submit-post-btn" id="submitPostBtn" onclick="submitPost()">
                    Đăng bài
                </button>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer style="background: var(--dark); color: white; padding: 40px 0 20px; margin-top: 60px;">
        <div class="container" style="text-align: center;">
            <p style="color: rgba(255,255,255,0.5);">&copy; 2024 ShipperShop</p>
        </div>
    </footer>

    <script src="js/shop.js"></script>
    <script>
        // ==========================================
        // COMMUNITY PAGE JAVASCRIPT
        // ==========================================

        let currentUser = null;
        let posts = [];
        let currentFilter = 'all';

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadCurrentUser();
            loadPosts();
            cart.updateUI();
        });

        // Load current user
        function loadCurrentUser() {
            const user = JSON.parse(localStorage.getItem('user') || 'null');
            if (user) {
                currentUser = user;
                const avatar = user.fullname ? user.fullname.charAt(0).toUpperCase() : 'U';
                document.getElementById('createPostAvatar').textContent = avatar;
                document.getElementById('modalAvatar').textContent = avatar;
                document.getElementById('modalUsername').textContent = user.fullname;
            }
        }

        // Load posts
        async function loadPosts() {
            const feedContainer = document.getElementById('postsFeed');
            const loadingDiv = document.getElementById('loadingPosts');

            try {
                const response = await fetch('/api/posts.php');
                const data = await response.json();

                loadingDiv.style.display = 'none';

                if (data.success && data.data.length > 0) {
                    posts = data.data;
                    renderPosts(posts);
                } else {
                    feedContainer.innerHTML = `
                        <div style="text-align: center; padding: 60px 20px; background: var(--white); border-radius: 15px;">
                            <i class="fas fa-comments" style="font-size: 64px; color: var(--gray-light); margin-bottom: 20px;"></i>
                            <h3 style="margin-bottom: 10px;">Chưa có bài viết nào</h3>
                            <p style="color: var(--gray); margin-bottom: 20px;">Hãy là người đầu tiên chia sẻ!</p>
                            <button class="btn-save" onclick="openCreatePostModal()">Tạo bài viết</button>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Load posts error:', error);
                loadingDiv.innerHTML = '<p style="color: var(--danger);">Lỗi tải bài viết</p>';
            }
        }

        // Render posts
        function renderPosts(postsToRender) {
            const container = document.getElementById('postsFeed');
            
            container.innerHTML = postsToRender.map(post => createPostHTML(post)).join('');
        }

        // Create post HTML
        function createPostHTML(post) {
            const avatar = post.user_name ? post.user_name.charAt(0).toUpperCase() : 'U';
            const isLiked = post.user_liked || false;
            
            return `
                <div class="post-card" data-post-id="${post.id}">
                    <div class="post-header">
                        <div class="post-user-info">
                            <div class="post-avatar">${avatar}</div>
                            <div class="post-user-details">
                                <div class="post-username">${post.user_name || 'Anonymous'}</div>
                                <div class="post-time">${timeAgo(post.created_at)}</div>
                            </div>
                        </div>
                        <button class="post-menu-btn" onclick="showPostMenu(${post.id})">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                    </div>

                    <div class="post-content">${escapeHtml(post.content)}</div>

                    ${post.image_url ? `
                        <img src="${post.image_url}" alt="Post image" class="post-image" onclick="viewImage('${post.image_url}')">
                    ` : ''}

                    <div class="post-stats">
                        <div class="post-stat" onclick="showLikes(${post.id})">
                            <i class="fas fa-heart" style="color: var(--danger);"></i>
                            ${post.likes_count || 0} lượt thích
                        </div>
                        <div class="post-stat" onclick="toggleComments(${post.id})">
                            ${post.comments_count || 0} bình luận
                        </div>
                    </div>

                    <div class="post-interactions">
                        <button class="interact-btn ${isLiked ? 'liked' : ''}" onclick="toggleLike(${post.id}, this)">
                            <i class="${isLiked ? 'fas' : 'far'} fa-heart"></i>
                            <span>Thích</span>
                        </button>
                        <button class="interact-btn" onclick="toggleComments(${post.id})">
                            <i class="far fa-comment"></i>
                            <span>Bình luận</span>
                        </button>
                        <button class="interact-btn" onclick="sharePost(${post.id})">
                            <i class="far fa-share-square"></i>
                            <span>Chia sẻ</span>
                        </button>
                    </div>

                    <div class="comments-section" id="comments-${post.id}">
                        <div id="comments-list-${post.id}">
                            <!-- Comments will be loaded here -->
                        </div>
                        ${currentUser ? `
                            <div class="comment-input-wrapper">
                                <input type="text" class="comment-input" id="comment-input-${post.id}" placeholder="Viết ghi chú...">
                                <button class="comment-submit" onclick="submitComment(${post.id})">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        // Open create post modal
        function openCreatePostModal() {
            if (!currentUser) {
                alert('Vui lòng đăng nhập để tạo bài viết');
                window.location.href = 'login.html';
                return;
            }
            document.getElementById('createPostModal').classList.add('show');
        }

        // Close modal
        function closeCreatePostModal() {
            document.getElementById('createPostModal').classList.remove('show');
            document.getElementById('postContent').value = '';
            document.getElementById('imagePreview').style.display = 'none';
        }

        // Preview image
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Remove image
        function removeImage() {
            document.getElementById('imageInput').value = '';
            document.getElementById('imagePreview').style.display = 'none';
        }

        // Submit post
        async function submitPost() {
            const content = document.getElementById('postContent').value.trim();
            const imageFile = document.getElementById('imageInput').files[0];

            if (!content && !imageFile) {
                alert('Vui lòng nhập nội dung hoặc chọn ảnh');
                return;
            }

            const formData = new FormData();
            formData.append('content', content);
            if (imageFile) {
                formData.append('image', imageFile);
            }

            const submitBtn = document.getElementById('submitPostBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang đăng...';

            try {
                const response = await fetch('/api/posts.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('token')
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    closeCreatePostModal();
                    loadPosts();
                    alert('✅ Đăng bài thành công!');
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                console.error('Submit post error:', error);
                alert('Lỗi kết nối. Vui lòng thử lại');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Đăng bài';
            }
        }

        // Toggle like
        async function toggleLike(postId, button) {
            if (!currentUser) {
                alert('Vui lòng đăng nhập');
                return;
            }

            try {
                const response = await fetch('/api/social.php?action=like', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('token')
                    },
                    body: JSON.stringify({ post_id: postId })
                });

                const data = await response.json();

                if (data.success) {
                    button.classList.toggle('liked');
                    const icon = button.querySelector('i');
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');

                    const postCard = button.closest('.post-card');
                    const statsDiv = postCard.querySelector('.post-stats .post-stat');
                    const currentCount = parseInt(statsDiv.textContent.match(/\d+/)[0]);
                    const newCount = data.data.is_liked ? currentCount + 1 : currentCount - 1;
                    statsDiv.innerHTML = `<i class="fas fa-heart" style="color: var(--danger);"></i> ${newCount} lượt thích`;
                }
            } catch (error) {
                console.error('Like error:', error);
            }
        }

        // Toggle comments section
        function toggleComments(postId) {
            const commentsSection = document.getElementById(`comments-${postId}`);
            
            if (commentsSection.classList.contains('show')) {
                commentsSection.classList.remove('show');
            } else {
                commentsSection.classList.add('show');
                loadComments(postId);
            }
        }

        // Load comments
        async function loadComments(postId) {
            const container = document.getElementById(`comments-list-${postId}`);
            container.innerHTML = '<p style="text-align: center; color: var(--gray); padding: 10px;">Đang tải...</p>';

            try {
                const response = await fetch(`/api/social.php?action=comments&post_id=${postId}`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    container.innerHTML = data.data.map(comment => {
                        const avatar = comment.user_name ? comment.user_name.charAt(0).toUpperCase() : 'U';
                        return `
                            <div class="comment-item">
                                <div class="comment-avatar">${avatar}</div>
                                <div class="comment-content">
                                    <div class="comment-username">${comment.user_name}</div>
                                    <div class="comment-text">${escapeHtml(comment.content)}</div>
                                    <div class="comment-time">${timeAgo(comment.created_at)}</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = '<p style="text-align: center; color: var(--gray); padding: 10px;">Chưa có ghi chú</p>';
                }
            } catch (error) {
                console.error('Load comments error:', error);
                container.innerHTML = '<p style="color: var(--danger);">Lỗi tải bình luận</p>';
            }
        }

        // Submit comment
        async function submitComment(postId) {
            if (!currentUser) {
                alert('Vui lòng đăng nhập');
                return;
            }

            const input = document.getElementById(`comment-input-${postId}`);
            const content = input.value.trim();

            if (!content) return;

            try {
                const response = await fetch('/api/social.php?action=comment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('token')
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        content: content
                    })
                });

                const data = await response.json();

                if (data.success) {
                    input.value = '';
                    loadComments(postId);

                    const postCard = document.querySelector(`[data-post-id="${postId}"]`);
                    const statsDiv = postCard.querySelectorAll('.post-stat')[1];
                    const currentCount = parseInt(statsDiv.textContent.match(/\d+/)[0]);
                    statsDiv.textContent = `${currentCount + 1} bình luận`;
                }
            } catch (error) {
                console.error('Comment error:', error);
            }
        }

        // Filter posts
        function filterPosts(filter) {
            currentFilter = filter;
            alert('Tính năng lọc "' + filter + '" đang được phát triển');
        }

        // Filter by tag
        function filterByTag(tag) {
            alert('Lọc theo tag "' + tag + '" đang được phát triển');
        }

        // Toggle follow
        function toggleFollow(button) {
            if (!currentUser) {
                alert('Vui lòng đăng nhập');
                return;
            }

            if (button.classList.contains('following')) {
                button.classList.remove('following');
                button.textContent = 'Theo dõi';
            } else {
                button.classList.add('following');
                button.textContent = 'Đang theo dõi';
            }
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showPostMenu(postId) {
            alert('Menu bài viết (Sửa/Xóa) đang được phát triển');
        }

        function sharePost(postId) {
            alert('Tính năng chia sẻ đang được phát triển');
        }

        function viewImage(url) {
            window.open(url, '_blank');
        }

        function showLikes(postId) {
            alert('Danh sách người thích đang được phát triển');
        }
    </script>
</body>
</html>
