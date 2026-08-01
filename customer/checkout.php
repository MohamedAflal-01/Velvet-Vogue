<?php
$page_title = 'Checkout';
require_once '../includes/header.php';

// Verify login
if (!is_logged_in()) {
    set_flash_message('error', 'Please log in to proceed to checkout.');
    redirect('login.php?redirect=checkout.php');
}

// Verify cart is not empty
if (empty($_SESSION['cart'])) {
    set_flash_message('error', 'Your shopping cart is empty.');
    redirect('cart.php');
}

$db = new Database();
$conn = $db->getConnection();

// Fetch customer information to pre-populate billing/shipping address
$cust_stmt = $conn->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
$cust_stmt->execute([':id' => $_SESSION['customer_id']]);
$customer = $cust_stmt->fetch();

// Retrieve cart products
$cart_items = [];
$subtotal = 0;
$regular_subtotal = 0;
$total_discount = 0;
$placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
$stmt = $conn->prepare("SELECT id, name, price, sale_price, sku, stock_quantity FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_keys($_SESSION['cart']));
$products = $stmt->fetchAll();

foreach ($products as $product) {
    $qty = $_SESSION['cart'][$product['id']];
    $is_on_sale = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']);
    $price = $is_on_sale ? $product['sale_price'] : $product['price'];
    $item_subtotal = $price * $qty;
    
    $regular_subtotal += $product['price'] * $qty;
    if ($is_on_sale) {
        $total_discount += ($product['price'] - $product['sale_price']) * $qty;
    }
    $subtotal += $item_subtotal;
    
    $cart_items[] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'sku' => $product['sku'],
        'price' => $price,
        'quantity' => $qty,
        'subtotal' => $item_subtotal,
        'stock_quantity' => $product['stock_quantity']
    ];
}

// Calculations
$shipping = ($subtotal >= 15000) ? 0.00 : 1000.00;
$tax = $subtotal * 0.08; // 8% Tax rate
$grand_total = $subtotal + $shipping + $tax;

$errors = [];

// Handle Checkout processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect shipping details
    $shipping_first_name = sanitize_input($_POST['shipping_first_name']);
    $shipping_last_name = sanitize_input($_POST['shipping_last_name']);
    $shipping_phone = sanitize_input($_POST['shipping_phone']);
    $shipping_address = sanitize_input($_POST['shipping_address']);
    $shipping_city = sanitize_input($_POST['shipping_city']);
    $shipping_state = sanitize_input($_POST['shipping_state']);
    $shipping_zip = sanitize_input($_POST['shipping_zip']);
    $shipping_country = sanitize_input($_POST['shipping_country']);
    
    // Billing details
    $same_as_shipping = isset($_POST['same_as_shipping']) ? 1 : 0;
    if ($same_as_shipping) {
        $billing_first_name = $shipping_first_name;
        $billing_last_name = $shipping_last_name;
        $billing_phone = $shipping_phone;
        $billing_address = $shipping_address;
        $billing_city = $shipping_city;
        $billing_state = $shipping_state;
        $billing_zip = $shipping_zip;
        $billing_country = $shipping_country;
    } else {
        $billing_first_name = sanitize_input($_POST['billing_first_name']);
        $billing_last_name = sanitize_input($_POST['billing_last_name']);
        $billing_phone = sanitize_input($_POST['billing_phone']);
        $billing_address = sanitize_input($_POST['billing_address']);
        $billing_city = sanitize_input($_POST['billing_city']);
        $billing_state = sanitize_input($_POST['billing_state']);
        $billing_zip = sanitize_input($_POST['billing_zip']);
        $billing_country = sanitize_input($_POST['billing_country']);
    }

    $payment_method = sanitize_input($_POST['payment_method']);
    
    // Address validation
    if (empty($shipping_first_name) || empty($shipping_last_name) || empty($shipping_address) || empty($shipping_city) || empty($shipping_state) || empty($shipping_zip) || empty($shipping_country)) {
        $errors[] = "Please fill in all required shipping address fields.";
    }
    
    if (!$same_as_shipping && (empty($billing_first_name) || empty($billing_last_name) || empty($billing_address) || empty($billing_city) || empty($billing_state) || empty($billing_zip) || empty($billing_country))) {
        $errors[] = "Please fill in all required billing address fields.";
    }

    if (empty($errors)) {
        // Format address text blocks for orders table
        $shipping_address_text = $shipping_first_name . " " . $shipping_last_name . "\n" .
                                 $shipping_address . "\n" .
                                 $shipping_city . ", " . $shipping_state . " " . $shipping_zip . "\n" .
                                 $shipping_country . "\n" .
                                 "Phone: " . $shipping_phone;

        $billing_address_text = $billing_first_name . " " . $billing_last_name . "\n" .
                                $billing_address . "\n" .
                                $billing_city . ", " . $billing_state . " " . $billing_zip . "\n" .
                                $billing_country . "\n" .
                                "Phone: " . $billing_phone;

        try {
            $conn->beginTransaction();

            // Double check stock again in transaction
            foreach ($cart_items as $item) {
                $check_stock = $conn->prepare("SELECT stock_quantity, name FROM products WHERE id = :id FOR UPDATE");
                $check_stock->execute([':id' => $item['id']]);
                $prod = $check_stock->fetch();
                if (!$prod || $prod['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Sorry, product '" . ($prod ? $prod['name'] : 'Item') . "' does not have enough stock available.");
                }
            }

            // Generate unique order number
            $order_number = 'VV-' . time() . '-' . rand(100, 999);
            
            // Set payment status
            $payment_status = ($payment_method === 'card') ? 'paid' : 'unpaid';

            // Insert Order
            $ins_order = $conn->prepare("
                INSERT INTO orders (customer_id, order_number, total_amount, tax_amount, shipping_amount, discount_amount, grand_total, status, payment_status, payment_method, shipping_address, billing_address) 
                VALUES (:customer_id, :order_number, :total_amount, :tax_amount, :shipping_amount, :discount_amount, :grand_total, 'pending', :payment_status, :payment_method, :shipping_address, :billing_address)
            ");
            
            $ins_order->execute([
                ':customer_id' => $_SESSION['customer_id'],
                ':order_number' => $order_number,
                ':total_amount' => $regular_subtotal,
                ':tax_amount' => $tax,
                ':shipping_amount' => $shipping,
                ':discount_amount' => $total_discount,
                ':grand_total' => $grand_total,
                ':payment_status' => $payment_status,
                ':payment_method' => $payment_method,
                ':shipping_address' => $shipping_address_text,
                ':billing_address' => $billing_address_text
            ]);

            $order_id = $conn->lastInsertId();

            // Insert order items and update stocks
            foreach ($cart_items as $item) {
                // Insert order item
                $ins_item = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) 
                    VALUES (:order_id, :product_id, :quantity, :unit_price, :subtotal)
                ");
                $ins_item->execute([
                    ':order_id' => $order_id,
                    ':product_id' => $item['id'],
                    ':quantity' => $item['quantity'],
                    ':unit_price' => $item['price'],
                    ':subtotal' => $item['subtotal']
                ]);

                // Decrement stock
                $dec_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id");
                $dec_stock->execute([
                    ':qty' => $item['quantity'],
                    ':id' => $item['id']
                ]);
            }

            // Log activity
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:customer_id, 'customer', :action)");
            $log_stmt->execute([
                ':customer_id' => $_SESSION['customer_id'],
                ':action' => "Placed order #{$order_number}"
            ]);

            $conn->commit();
            unset($_SESSION['cart']); // Clear cart
            
            set_flash_message('success', 'Thank you! Your order has been placed.');
            redirect("order-confirmation.php?order_number=" . $order_number);
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}
?>

<div class="container my-5">
    <h2 class="mb-4 text-uppercase fw-bold text-gold text-center">Checkout</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="checkout.php">
        <div class="row g-4">
            <!-- Left Side: Shipping & Billing Form -->
            <div class="col-lg-7">
                <!-- Shipping Address Card -->
                <div class="card glass-card border-0 mb-4 p-4">
                    <h4 class="mb-3 text-gold fw-semibold border-bottom pb-2"><i class="fas fa-truck me-2"></i> Shipping Address</h4>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="shipping_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="shipping_first_name" id="shipping_first_name" class="form-control" required value="<?php echo htmlspecialchars($customer['first_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="shipping_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="shipping_last_name" id="shipping_last_name" class="form-control" required value="<?php echo htmlspecialchars($customer['last_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="shipping_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="shipping_phone" id="shipping_phone" class="form-control" required value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-12">
                            <label for="shipping_address" class="form-label">Street Address <span class="text-danger">*</span></label>
                            <input type="text" name="shipping_address" id="shipping_address" class="form-control" required value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" placeholder="123 Luxury Lane">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="shipping_city" class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" name="shipping_city" id="shipping_city" class="form-control" required value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="shipping_state" class="form-label">State <span class="text-danger">*</span></label>
                            <input type="text" name="shipping_state" id="shipping_state" class="form-control" required value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="shipping_zip" class="form-label">Zip Code <span class="text-danger">*</span></label>
                            <input type="text" name="shipping_zip" id="shipping_zip" class="form-control" required value="<?php echo htmlspecialchars($customer['zip_code'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="shipping_country" class="form-label">Country <span class="text-danger">*</span></label>
                            <input type="text" name="shipping_country" id="shipping_country" class="form-control" required value="<?php echo htmlspecialchars($customer['country'] ?? 'US'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Billing Address Option -->
                <div class="card glass-card border-0 mb-4 p-4">
                    <h4 class="mb-3 text-gold fw-semibold border-bottom pb-2"><i class="fas fa-file-invoice-dollar me-2"></i> Billing Address</h4>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="same_as_shipping" id="same_as_shipping" value="1" checked onchange="toggleBilling(this)">
                        <label class="form-check-label fw-medium" for="same_as_shipping">
                            Same as shipping address
                        </label>
                    </div>

                    <!-- Separate Billing Address Block (Hidden by default) -->
                    <div id="billing_address_block" style="display: none;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="billing_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="billing_first_name" id="billing_first_name" class="form-control">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="billing_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="billing_last_name" id="billing_last_name" class="form-control">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="billing_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="billing_phone" id="billing_phone" class="form-control">
                            </div>
                            
                            <div class="col-12">
                                <label for="billing_address" class="form-label">Street Address <span class="text-danger">*</span></label>
                                <input type="text" name="billing_address" id="billing_address" class="form-control" placeholder="123 Billing Street">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="billing_city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" name="billing_city" id="billing_city" class="form-control">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="billing_state" class="form-label">State <span class="text-danger">*</span></label>
                                <input type="text" name="billing_state" id="billing_state" class="form-control">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="billing_zip" class="form-label">Zip Code <span class="text-danger">*</span></label>
                                <input type="text" name="billing_zip" id="billing_zip" class="form-control">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="billing_country" class="form-label">Country <span class="text-danger">*</span></label>
                                <input type="text" name="billing_country" id="billing_country" class="form-control" value="US">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Card -->
                <div class="card glass-card border-0 p-4">
                    <h4 class="mb-3 text-gold fw-semibold border-bottom pb-2"><i class="fas fa-credit-card me-2"></i> Payment Method</h4>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" id="payment_cod" value="cod" checked onchange="toggleCardDetails(this)">
                        <label class="form-check-label fw-semibold" for="payment_cod">
                            Cash on Delivery (COD)
                        </label>
                        <p class="text-muted small mb-0">Pay in cash when your luxury parcel is delivered to your door.</p>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" id="payment_card" value="card" onchange="toggleCardDetails(this)">
                        <label class="form-check-label fw-semibold" for="payment_card">
                            Credit/Debit Card (Mock Checkout)
                        </label>
                        <p class="text-muted small mb-1">Pay securely online using credit or debit cards.</p>
                    </div>

                    <!-- Mock Card Fields (Hidden by default) -->
                    <div id="card_details_block" style="display: none;" class="bg-light p-3 rounded">
                        <div class="row g-2">
                            <div class="col-md-12">
                                <label class="form-label small mb-1">Cardholder Name</label>
                                <input type="text" class="form-control form-control-sm" placeholder="John Doe">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small mb-1">Card Number</label>
                                <input type="text" class="form-control form-control-sm" placeholder="1234 5678 1234 5678">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Expiry</label>
                                <input type="text" class="form-control form-control-sm" placeholder="MM/YY">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">CVC</label>
                                <input type="password" class="form-control form-control-sm" placeholder="123">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Order Summary -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4 sticky-top" style="top: 100px; z-index: 10;">
                    <h4 class="mb-3 border-bottom pb-2 fw-semibold"><i class="fas fa-receipt me-2"></i> Order Summary</h4>
                    
                    <div class="mb-3" style="max-height: 300px; overflow-y: auto;">
                        <ul class="list-group list-group-flush mb-0">
                            <?php foreach ($cart_items as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <div>
                                        <h6 class="mb-0 fw-semibold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <span class="fw-semibold text-secondary"><?php echo format_price($item['subtotal']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Totals breakdown -->
                    <div class="border-top pt-3 text-secondary">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span class="fw-semibold"><?php echo format_price($regular_subtotal); ?></span>
                        </div>
                        <?php if ($total_discount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-danger">
                                <span>Discount:</span>
                                <span class="fw-semibold">-<?php echo format_price($total_discount); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (8%):</span>
                            <span class="fw-semibold"><?php echo format_price($tax); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span class="fw-semibold"><?php echo ($shipping == 0) ? '<span class="text-success text-uppercase">Free</span>' : format_price($shipping); ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-3 fs-5 text-dark">
                            <span class="fw-bold">Total:</span>
                            <span class="fw-bold text-gold"><?php echo format_price($grand_total); ?></span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-luxury w-100 py-3 text-uppercase fw-bold letter-spacing-1">
                            Place Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function toggleBilling(checkbox) {
    const block = document.getElementById('billing_address_block');
    const inputs = block.querySelectorAll('input');
    
    if (checkbox.checked) {
        block.style.display = 'none';
        inputs.forEach(input => input.removeAttribute('required'));
    } else {
        block.style.display = 'block';
        inputs.forEach(input => input.setAttribute('required', 'required'));
    }
}

function toggleCardDetails(radio) {
    const block = document.getElementById('card_details_block');
    if (radio.value === 'card') {
        block.style.display = 'block';
    } else {
        block.style.display = 'none';
    }
}
</script>

<?php require_once '../components/footer.php'; ?>
