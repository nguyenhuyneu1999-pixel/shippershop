<?php
/**
 * ============================================
 * PRODUCTS API
 * ============================================
 * 
 * Endpoints:
 * GET /api/products.php - Get all products (with filters)
 * GET /api/products.php?id=1 - Get single product
 * GET /api/products.php?slug=iphone-15 - Get product by slug
 */

define('APP_ACCESS', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Set CORS headers
setCorsHeaders();

// Get request method
$method = getRequestMethod();

// Only allow GET requests for now
if ($method !== 'GET') {
    error('Method not allowed', 405);
}

// Get database instance
$db = db();

// ============================================
// GET SINGLE PRODUCT
// ============================================

if (isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    
    $sql = "SELECT 
                p.*,
                c.name as category_name,
                c.slug as category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ? AND p.status = 'active'";
    
    $product = $db->fetchOne($sql, [$productId]);
    
    if (!$product) {
        error('Sản phẩm không tồn tại', 404);
    }
    
    // Parse JSON fields
    if ($product['gallery']) {
        $product['gallery'] = json_decode($product['gallery'], true);
    }
    if ($product['specifications']) {
        $product['specifications'] = json_decode($product['specifications'], true);
    }
    
    // Update views
    $db->query("UPDATE products SET views = views + 1 WHERE id = ?", [$productId]);
    
    success('Success', $product);
}

// GET BY SLUG
if (isset($_GET['slug'])) {
    $slug = sanitize($_GET['slug']);
    
    $sql = "SELECT 
                p.*,
                c.name as category_name,
                c.slug as category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.slug = ? AND p.status = 'active'";
    
    $product = $db->fetchOne($sql, [$slug]);
    
    if (!$product) {
        error('Sản phẩm không tồn tại', 404);
    }
    
    // Parse JSON fields
    if ($product['gallery']) {
        $product['gallery'] = json_decode($product['gallery'], true);
    }
    if ($product['specifications']) {
        $product['specifications'] = json_decode($product['specifications'], true);
    }
    
    // Update views
    $db->query("UPDATE products SET views = views + 1 WHERE id = ?", [$product['id']]);
    
    success('Success', $product);
}

// ============================================
// GET PRODUCTS LIST (with filters)
// ============================================

// Build WHERE clause
$where = ["p.status = 'active'"];
$params = [];

// Category filter
if (!empty($_GET['category'])) {
    $category = sanitize($_GET['category']);
    $where[] = "c.slug = ?";
    $params[] = $category;
}

// Search filter
if (!empty($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Price range filter
if (!empty($_GET['min_price'])) {
    $minPrice = floatval($_GET['min_price']);
    $where[] = "p.sale_price >= ?";
    $params[] = $minPrice;
}

if (!empty($_GET['max_price'])) {
    $maxPrice = floatval($_GET['max_price']);
    $where[] = "p.sale_price <= ?";
    $params[] = $maxPrice;
}

// Rating filter
if (!empty($_GET['min_rating'])) {
    $minRating = floatval($_GET['min_rating']);
    $where[] = "p.rating_avg >= ?";
    $params[] = $minRating;
}

// On sale filter
if (isset($_GET['on_sale']) && $_GET['on_sale'] === 'true') {
    $where[] = "p.sale_price < p.price";
}

// Featured filter
if (isset($_GET['featured']) && $_GET['featured'] === 'true') {
    $where[] = "p.featured = 1";
}

// In stock filter
if (isset($_GET['in_stock']) && $_GET['in_stock'] === 'true') {
    $where[] = "p.stock > 0";
}

$whereClause = implode(' AND ', $where);

// Count total products
$countSql = "SELECT COUNT(*) 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE $whereClause";

$totalProducts = $db->fetchOne(str_replace('SELECT COUNT(*)', 'SELECT COUNT(*) as c', $countSql), $params)['c'];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(intval($_GET['limit']), API_PAGINATION_LIMIT) : API_DEFAULT_LIMIT;

$pagination = paginate($totalProducts, $page, $limit);
$offset = $pagination['offset'];

// Sorting
$orderBy = 'p.id DESC'; // Default: newest first

if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_asc':
            $orderBy = 'p.sale_price ASC';
            break;
        case 'price_desc':
            $orderBy = 'p.sale_price DESC';
            break;
        case 'name_asc':
            $orderBy = 'p.name ASC';
            break;
        case 'name_desc':
            $orderBy = 'p.name DESC';
            break;
        case 'rating':
            $orderBy = 'p.rating_avg DESC';
            break;
        case 'popular':
            $orderBy = 'p.sales_count DESC';
            break;
        case 'views':
            $orderBy = 'p.views DESC';
            break;
        case 'newest':
            $orderBy = 'p.created_at DESC';
            break;
        case 'featured':
            $orderBy = 'p.featured DESC, p.rating_avg DESC';
            break;
    }
}

// Get products
$sql = "SELECT 
            p.id,
            p.category_id,
            p.name,
            p.slug,
            p.short_description,
            p.price,
            p.sale_price,
            p.main_image,
            p.stock,
            p.sku,
            p.rating_avg,
            p.rating_count,
            p.sales_count,
            p.views,
            p.featured,
            p.status,
            c.name as category_name,
            c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT $limit OFFSET $offset";

$products = $db->fetchAll($sql, $params);

// Calculate discount percentage for each product
foreach ($products as &$product) {
    if ($product['price'] > $product['sale_price']) {
        $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
        $product['discount_percent'] = $discount;
        $product['badge'] = "-{$discount}%";
    } else {
        $product['discount_percent'] = 0;
        $product['badge'] = null;
    }
}

// Response
jsonResponse([
    'success' => true,
    'message' => 'Success',
    'data' => $products,
    'pagination' => $pagination,
    'filters' => [
        'category' => $_GET['category'] ?? null,
        'search' => $_GET['search'] ?? null,
        'min_price' => $_GET['min_price'] ?? null,
        'max_price' => $_GET['max_price'] ?? null,
        'min_rating' => $_GET['min_rating'] ?? null,
        'on_sale' => isset($_GET['on_sale']) ? $_GET['on_sale'] === 'true' : null,
        'featured' => isset($_GET['featured']) ? $_GET['featured'] === 'true' : null,
        'sort' => $_GET['sort'] ?? 'newest'
    ]
]);
