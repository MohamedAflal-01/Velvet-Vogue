<?php
$page_title = 'Product Details';
require_once 'includes/header.php';

$db = new Database();
$conn = $db->getConnection();

$slug = isset($_GET['slug']) ? sanitize_input($_GET['slug']) : '';

// Fetch Product Details
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.slug = :slug LIMIT 1");
$stmt->bindParam(':slug', $slug);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    // Redirect to 404 page if product not found
    redirect('404.php');
}

// Fetch Product Images
$images_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC");
$images_stmt->execute([':product_id' => $product['id']]);
$product_images = $images_stmt->fetchAll();

// Fetch Related Products (same category, excluding current product)
$related_stmt = $conn->prepare("SELECT p.*, pi.image_url FROM products p LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1 WHERE p.category_id = :category_id AND p.id != :id AND p.status = 'active' LIMIT 4");
$related_stmt->bindParam(':category_id', $product['category_id']);
$related_stmt->bindParam(':id', $product['id']);
$related_stmt->execute();
$related_products = $related_stmt->fetchAll();

// Fetch Sizes
$sizes_stmt = $conn->query("SELECT * FROM sizes");
$sizes = $sizes_stmt->fetchAll();

// Fetch Colors
$colors_stmt = $conn->query("SELECT * FROM colors");
$colors = $colors_stmt->fetchAll();

// Fetch Approved Reviews
$reviews_stmt = $conn->prepare("
    SELECT r.*, c.first_name, c.last_name 
    FROM reviews r 
    JOIN customers c ON r.customer_id = c.id 
    WHERE r.product_id = :product_id AND r.status = 'approved' 
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([':product_id' => $product['id']]);
$approved_reviews = $reviews_stmt->fetchAll();

// Calculate Average Rating
$avg_rating = 0;
if (count($approved_reviews) > 0) {
    $total_rating = 0;
    foreach ($approved_reviews as $rev) {
        $total_rating += $rev['rating'];
    }
    $avg_rating = $total_rating / count($approved_reviews);
}
?>

<div class="container my-5">
    <div class="row">
        <!-- Image Gallery -->
        <div class="col-md-6 mb-4">
            <div class="card glass-card p-3 shadow-sm text-center">
                <div class="main-image-wrap mb-3" style="height: 500px; overflow: hidden;">
                    <img src="<?php echo !empty($product_images) ? htmlspecialchars($product_images[0]['image_url']) : 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=800&auto=format&fit=crop'; ?>" id="main-product-img" class="w-100 h-100 object-fit-cover rounded" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="d-flex justify-content-center gap-2">
                    <?php if (empty($product_images)): ?>
                        <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=150&auto=format&fit=crop" class="thumbnail img-thumbnail" style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;" onclick="document.getElementById('main-product-img').src=this.src">
                        <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?q=80&w=150&auto=format&fit=crop" class="thumbnail img-thumbnail" style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;" onclick="document.getElementById('main-product-img').src=this.src">
                        <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=150&auto=format&fit=crop" class="thumbnail img-thumbnail" style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;" onclick="document.getElementById('main-product-img').src=this.src">
                    <?php else: ?>
                        <?php foreach ($product_images as $img): ?>
                            <img src="<?php echo htmlspecialchars($img['image_url']); ?>" class="thumbnail img-thumbnail" style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;" onclick="document.getElementById('main-product-img').src=this.src">
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Product Details Content -->
        <div class="col-md-6">
            <div class="card glass-card p-4 shadow-sm">
                <span class="text-gold text-uppercase fw-bold mb-2 d-block"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <h1 class="h2 mb-3 text-dark"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="text-warning me-2">
                        <?php 
                        if (count($approved_reviews) > 0) {
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= round($avg_rating)) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                        } else {
                            for ($i = 1; $i <= 5; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <?php if (count($approved_reviews) > 0): ?>
                        <span class="text-muted">(<?php echo number_format($avg_rating, 1); ?> / 5.0 Rating based on <?php echo count($approved_reviews); ?> <?php echo count($approved_reviews) === 1 ? 'review' : 'reviews'; ?>)</span>
                    <?php else: ?>
                        <span class="text-muted">(No reviews yet)</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                    <h3 class="mb-4">
                        <span class="text-danger fw-bold me-2"><?php echo format_price($product['sale_price']); ?></span>
                        <del class="text-muted fs-5"><?php echo format_price($product['price']); ?></del>
                        <span class="badge bg-danger ms-2 fs-6">SALE</span>
                    </h3>
                <?php else: ?>
                    <h3 class="text-gold mb-4"><?php echo format_price($product['price']); ?></h3>
                <?php endif; ?>
                
                <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <form action="customer/cart.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <!-- Color Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Select Color</label>
                        <div class="d-flex gap-2">
                            <?php foreach ($colors as $color): ?>
                                <label class="btn btn-outline-dark rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; background-color: <?php echo $color['hex_code']; ?>; border-color: #ddd;">
                                    <input type="radio" name="color" value="<?php echo $color['id']; ?>" class="btn-check" required>
                                    <span class="visually-hidden"><?php echo $color['name']; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Size Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Select Size</label>
                        <div class="d-flex gap-2">
                            <?php foreach ($sizes as $size): ?>
                                <label class="btn btn-outline-dark px-3 py-1">
                                    <input type="radio" name="size" value="<?php echo $size['id']; ?>" class="btn-check" required>
                                    <?php echo $size['name']; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Quantity & Call To Actions -->
                    <div class="row align-items-center mb-4">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="number" name="quantity" class="form-control text-center" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-luxury flex-grow-1 py-3"><i class="fas fa-shopping-cart me-2"></i> Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </form>
                <form action="customer/wishlist.php" method="POST" class="mt-n2 mb-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <button type="submit" class="btn btn-outline-dark w-100 py-3 text-danger"><i class="far fa-heart me-2"></i> Add to Wishlist</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Product Reviews Section -->
    <div class="card glass-card p-4 shadow-sm mb-5">
        <h3 class="text-gold mb-4"><i class="fas fa-comments me-2"></i> Customer Reviews</h3>
        
        <?php if (empty($approved_reviews)): ?>
            <div class="text-center py-5 text-muted">
                <i class="far fa-comments fs-1 mb-3 d-block text-gold"></i>
                <p class="mb-0">No reviews yet for this product.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Left column: Summary -->
                <div class="col-md-4 mb-4 text-center border-end">
                    <h1 class="display-4 fw-bold text-gold"><?php echo number_format($avg_rating, 1); ?></h1>
                    <div class="text-warning fs-4 mb-2">
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= round($avg_rating)) {
                                echo '<i class="fas fa-star"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <p class="text-muted">Based on <?php echo count($approved_reviews); ?> <?php echo count($approved_reviews) === 1 ? 'review' : 'reviews'; ?></p>
                </div>
                
                <!-- Right column: List of reviews -->
                <div class="col-md-8">
                    <div class="reviews-list pe-2" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($approved_reviews as $rev): ?>
                            <div class="review-item border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']); ?></h6>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></small>
                                </div>
                                <div class="text-warning small mb-2">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rev['rating']) {
                                            echo '<i class="fas fa-star"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <p class="text-muted mb-0" style="white-space: pre-line;"><?php echo htmlspecialchars($rev['comment']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Related Products -->
    <div class="my-5 py-4">
        <h3 class="text-gold mb-4">You May Also Like</h3>
        <div class="row">
            <?php if (empty($related_products)): ?>
                <p class="text-muted">No related products found.</p>
            <?php else: ?>
                <?php foreach ($related_products as $rel): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card product-card h-100">
                            <div class="product-img-wrap">
                                <img src="<?php echo !empty($rel['image_url']) ? htmlspecialchars($rel['image_url']) : 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=600&auto=format&fit=crop'; ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>">
                                <div class="product-actions">
                                    <form action="customer/cart.php" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo $rel['id']; ?>">
                                        <button type="submit" class="btn btn-luxury btn-sm" title="Add to Cart"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                                    </form>
                                    <form action="customer/wishlist.php" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo $rel['id']; ?>">
                                        <button type="submit" class="btn btn-luxury btn-sm text-danger" title="Add to Wishlist"><i class="far fa-heart"></i></button>
                                    </form>
                                    <a href="product-details.php?slug=<?php echo $rel['slug']; ?>" class="btn btn-dark btn-sm"><i class="fas fa-eye"></i> View</a>
                                </div>
                            </div>
                            <div class="card-body text-center">
                                <h6 class="card-title mb-2 text-truncate"><?php echo htmlspecialchars($rel['name']); ?></h6>
                                <p class="text-gold fw-bold"><?php echo format_price($rel['price']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'components/footer.php'; ?>
