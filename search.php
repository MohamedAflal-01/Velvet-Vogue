<?php
$page_title = 'Search Results';
require_once 'includes/header.php';

$db = new Database();
$conn = $db->getConnection();

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$products = [];
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name, pi.image_url FROM products p JOIN categories c ON p.category_id = c.id LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1 WHERE p.status = 'active' AND (p.name LIKE :search_name OR p.description LIKE :search_desc)");
    $search_param = '%' . $search . '%';
    $stmt->bindValue(':search_name', $search_param);
    $stmt->bindValue(':search_desc', $search_param);
    $stmt->execute();
    $products = $stmt->fetchAll();
}
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="text-gold">Search Results</h1>
        <p class="text-muted">Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong></p>
    </div>
    
    <div class="row">
        <?php if(empty($products)): ?>
            <div class="col-12 text-center py-5">
                <h4 class="text-muted">No products found matching your search.</h4>
                <a href="shop.php" class="btn btn-luxury mt-3">Browse Shop</a>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-3 mb-4">
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

<?php require_once 'components/footer.php'; ?>
