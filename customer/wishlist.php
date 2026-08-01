<?php
$page_title = 'My Wishlist';
require_once '../includes/header.php';

// Verify login
if (!is_logged_in()) {
    set_flash_message('error', 'Please log in to view your wishlist.');
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle POST actions (Add / Remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize_input($_POST['action']);
    
    if ($action === 'add') {
        $product_id = intval($_POST['product_id']);
        
        // Verify product exists
        $prod_stmt = $conn->prepare("SELECT id, name FROM products WHERE id = :id");
        $prod_stmt->execute([':id' => $product_id]);
        $product = $prod_stmt->fetch();
        
        if ($product) {
            // Check if already in wishlist
            $check_stmt = $conn->prepare("SELECT id FROM wishlist WHERE customer_id = :customer_id AND product_id = :product_id");
            $check_stmt->execute([
                ':customer_id' => $_SESSION['customer_id'],
                ':product_id' => $product_id
            ]);
            
            if ($check_stmt->rowCount() > 0) {
                set_flash_message('success', $product['name'] . ' is already in your wishlist.');
            } else {
                $ins_stmt = $conn->prepare("INSERT INTO wishlist (customer_id, product_id) VALUES (:customer_id, :product_id)");
                if ($ins_stmt->execute([
                    ':customer_id' => $_SESSION['customer_id'],
                    ':product_id' => $product_id
                ])) {
                    set_flash_message('success', $product['name'] . ' added to wishlist.');
                } else {
                    set_flash_message('error', 'Failed to add item to wishlist.');
                }
            }
        }
        
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'wishlist.php';
        redirect($referer);
    } elseif ($action === 'remove') {
        $wishlist_id = intval($_POST['wishlist_id']);
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE id = :id AND customer_id = :customer_id");
        if ($stmt->execute([':id' => $wishlist_id, ':customer_id' => $_SESSION['customer_id']])) {
            set_flash_message('success', 'Item removed from wishlist.');
        } else {
            set_flash_message('error', 'Failed to remove item.');
        }
        redirect('wishlist.php');
    }
}

// Fetch customer details
$cust_stmt = $conn->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
$cust_stmt->execute([':id' => $_SESSION['customer_id']]);
$user = $cust_stmt->fetch();

// Fetch wishlist items
$wish_stmt = $conn->prepare("
    SELECT w.id as wishlist_id, p.*, pi.image_url 
    FROM wishlist w 
    JOIN products p ON w.product_id = p.id 
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE w.customer_id = :customer_id
");
$wish_stmt->execute([':customer_id' => $_SESSION['customer_id']]);
$wishlist_items = $wish_stmt->fetchAll();
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 mb-4">
            <div class="card glass-card p-3">
                <div class="text-center mb-3">
                    <div class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 32px;">
                        <?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?>
                    </div>
                    <h5 class="mt-2 text-gold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="profile.php" class="list-group-item list-group-item-action">Edit Profile</a>
                    <a href="orders.php" class="list-group-item list-group-item-action">Order History</a>
                    <a href="wishlist.php" class="list-group-item list-group-item-action active bg-gold border-0">Wishlist</a>
                    <a href="change-password.php" class="list-group-item list-group-item-action">Change Password</a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                </div>
            </div>
        </div>

        <!-- Wishlist Content -->
        <div class="col-md-9">
            <div class="card glass-card p-4">
                <h3 class="mb-4 text-gold"><i class="fas fa-heart me-2"></i> My Wishlist</h3>
                
                <?php if (empty($wishlist_items)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="far fa-heart mb-3" style="font-size: 3rem;"></i>
                        <h5>Your wishlist is empty.</h5>
                        <p class="mb-0">Explore our boutique and click the heart icon on items you love.</p>
                        <a href="../shop.php" class="btn btn-luxury mt-3">Browse Collections</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($wishlist_items as $item): ?>
                            <div class="col-md-4">
                                <div class="card product-card h-100 position-relative">
                                    <form action="wishlist.php" method="POST" class="position-absolute" style="top: 10px; right: 10px; z-index: 10;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="wishlist_id" value="<?php echo $item['wishlist_id']; ?>">
                                        <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm border-0 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; color: #dc3545;" title="Remove from Wishlist">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    
                                    <div class="product-img-wrap" style="height: 250px;">
                                        <?php 
                                        if (!empty($item['image_url'])) {
                                            $img_src = '../' . $item['image_url'];
                                        } else {
                                            $img_src = 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=600&auto=format&fit=crop';
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <div class="product-actions">
                                            <form action="cart.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-luxury btn-sm"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                                            </form>
                                            <a href="../product-details.php?slug=<?php echo $item['slug']; ?>" class="btn btn-dark btn-sm"><i class="fas fa-eye"></i> View</a>
                                        </div>
                                    </div>
                                    <div class="card-body text-center py-3">
                                        <h6 class="card-title mb-2 text-truncate"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="text-gold fw-bold mb-0"><?php echo format_price($item['price']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>
