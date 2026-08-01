<?php
$page_title = 'Shopping Cart';
require_once '../includes/header.php';

// Initialize session cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$db = new Database();
$conn = $db->getConnection();

// Handle AJAX cart updates or form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = sanitize_input($_POST['action']);
        
        if ($action === 'add') {
            $product_id = intval($_POST['product_id']);
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            
            // Check stock
            $stmt = $conn->prepare("SELECT stock_quantity, name FROM products WHERE id = :id");
            $stmt->bindParam(':id', $product_id);
            $stmt->execute();
            $product = $stmt->fetch();
            
            if ($product) {
                if ($product['stock_quantity'] >= $quantity) {
                    if (isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id] += $quantity;
                    } else {
                        $_SESSION['cart'][$product_id] = $quantity;
                    }
                    set_flash_message('success', $product['name'] . ' added to cart.');
                } else {
                    set_flash_message('error', 'Only ' . $product['stock_quantity'] . ' units left in stock.');
                }
            }
        } elseif ($action === 'update') {
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
            set_flash_message('success', 'Cart updated.');
        } elseif ($action === 'remove') {
            $product_id = intval($_POST['product_id']);
            unset($_SESSION['cart'][$product_id]);
            set_flash_message('success', 'Item removed from cart.');
        }
        redirect('cart.php');
    }
}

// Fetch products currently in the cart
$cart_items = [];
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $stmt = $conn->prepare("SELECT id, name, price, sale_price, slug FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($_SESSION['cart']));
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        $price = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) ? $product['sale_price'] : $product['price'];
        $item_subtotal = $price * $qty;
        $subtotal += $item_subtotal;
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $price,
            'qty' => $qty,
            'subtotal' => $item_subtotal,
            'slug' => $product['slug']
        ];
    }
}
?>

<div class="container my-5">
    <h2 class="mb-4 text-gold text-center">Your Shopping Cart</h2>
    
    <?php if (empty($cart_items)): ?>
        <div class="card glass-card p-5 text-center">
            <i class="fas fa-shopping-bag text-muted mb-3" style="font-size: 4rem;"></i>
            <h4>Your cart is empty.</h4>
            <p class="text-muted">Browse our collection to add items here.</p>
            <a href="../shop.php" class="btn btn-luxury mt-3">Go Shop</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card glass-card p-4">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td>
                                            <a href="../product.php?slug=<?php echo $item['slug']; ?>" class="text-decoration-none text-dark fw-bold">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo format_price($item['price']); ?></td>
                                        <td>
                                            <form action="cart.php" method="POST" class="d-flex align-items-center" style="max-width: 120px;">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <input type="number" name="quantity" class="form-control form-control-sm text-center" value="<?php echo $item['qty']; ?>" min="1" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td><?php echo format_price($item['subtotal']); ?></td>
                                        <td>
                                            <form action="cart.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-link text-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card glass-card p-4">
                    <h4 class="mb-3 text-gold">Order Summary</h4>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span><?php echo format_price($subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Estimated Shipping</span>
                        <span>Free</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4 fw-bold">
                        <span>Grand Total</span>
                        <span><?php echo format_price($subtotal); ?></span>
                    </div>
                    <a href="checkout.php" class="btn btn-luxury w-100 py-3">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../components/footer.php'; ?>
