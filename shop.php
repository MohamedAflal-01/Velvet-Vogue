<?php
$page_title = 'Shop Products';
require_once 'includes/header.php';

$db = new Database();
$conn = $db->getConnection();

// Filtering inputs
$category_slug = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$gender = isset($_GET['gender']) ? sanitize_input($_GET['gender']) : '';
$brand = isset($_GET['brand']) ? sanitize_input($_GET['brand']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 50000;
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest';

// Base query construction
$query = "SELECT p.*, c.name as category_name, pi.image_url FROM products p JOIN categories c ON p.category_id = c.id LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1 WHERE p.status = 'active'";
$params = [];

if (!empty($category_slug)) {
    $query .= " AND c.slug = :category";
    $params[':category'] = $category_slug;
}
if (!empty($gender)) {
    $query .= " AND p.gender = :gender";
    $params[':gender'] = $gender;
}
if (!empty($brand)) {
    $query .= " AND p.brand = :brand";
    $params[':brand'] = $brand;
}
if (!empty($search)) {
    $query .= " AND (p.name LIKE :search_name OR p.description LIKE :search_desc)";
    $params[':search_name'] = '%' . $search . '%';
    $params[':search_desc'] = '%' . $search . '%';
}
if ($min_price >= 0) {
    $query .= " AND p.price >= :min_price";
    $params[':min_price'] = $min_price;
}
if ($max_price > 0) {
    $query .= " AND p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

// Sorting logic
if ($sort === 'oldest') {
    $query .= " ORDER BY p.created_at ASC";
} elseif ($sort === 'price_high') {
    $query .= " ORDER BY p.price DESC";
} elseif ($sort === 'price_low') {
    $query .= " ORDER BY p.price ASC";
} else {
    $query .= " ORDER BY p.created_at DESC"; // newest
}

$stmt = $conn->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$products = $stmt->fetchAll();

// Fetch categories for filter dropdown
$cat_stmt = $conn->query("SELECT * FROM categories WHERE status = 1");
$categories = $cat_stmt->fetchAll();
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-md-3 mb-4">
            <div class="card glass-card p-4">
                <h4 class="text-gold mb-4">Filters</h4>
                <form action="shop.php" method="GET">
                    <!-- Search -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search product..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <!-- Category -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Category</label>
                        <select name="category" class="form-select form-control" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['slug']; ?>" <?php echo $category_slug === $cat['slug'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Gender -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Gender</label>
                        <select name="gender" class="form-select form-control" onchange="this.form.submit()">
                            <option value="">All Genders</option>
                            <option value="Men" <?php echo $gender === 'Men' ? 'selected' : ''; ?>>Men</option>
                            <option value="Women" <?php echo $gender === 'Women' ? 'selected' : ''; ?>>Women</option>
                            <option value="Kids" <?php echo $gender === 'Kids' ? 'selected' : ''; ?>>Kids</option>
                        </select>
                    </div>

                    <!-- Price range -->
                    <div class="mb-4">
                        <label class="form-label fw-bold" id="price-label">Max Price (LKR <?php echo $max_price; ?>)</label>
                        <?php $price_percent = ($max_price / 50000) * 100; ?>
                        <input type="range" name="max_price" class="form-range" min="0" max="50000" step="500" value="<?php echo $max_price; ?>" style="--percent: <?php echo $price_percent; ?>%" oninput="document.getElementById('price-label').innerText = 'Max Price (LKR ' + this.value + ')'; this.style.setProperty('--percent', (this.value / 500) + '%')" onchange="this.form.submit()">
                    </div>

                    <!-- Sort -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Sort By</label>
                        <select name="sort" class="form-select form-control" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-luxury w-100">Apply Filters</button>
                </form>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-md-9">
            <div class="row">
                <?php if (empty($products)): ?>
                    <div class="col-12 text-center py-5">
                        <h4 class="text-muted">No products found matching your filters.</h4>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card product-card h-100">
                                <div class="product-img-wrap">
                                    <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=600&auto=format&fit=crop'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="product-actions">
                                        <form action="customer/cart.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-luxury btn-sm" title="Add to Cart"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                                        </form>
                                        <form action="customer/wishlist.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-luxury btn-sm text-danger" title="Add to Wishlist"><i class="far fa-heart"></i></button>
                                        </form>
                                        <a href="product-details.php?slug=<?php echo $product['slug']; ?>" class="btn btn-dark btn-sm"><i class="fas fa-eye"></i> View</a>
                                    </div>
                                </div>
                                <div class="card-body text-center">
                                    <h6 class="card-title mb-2 text-truncate"><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <?php if ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                                        <p class="mb-0 text-muted text-decoration-line-through small"><?php echo format_price($product['price']); ?></p>
                                        <p class="text-danger fw-bold mb-0"><?php echo format_price($product['sale_price']); ?></p>
                                    <?php else: ?>
                                        <p class="text-gold fw-bold mb-0"><?php echo format_price($product['price']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'components/footer.php'; ?>
