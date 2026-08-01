<?php
$page_title = 'Order Confirmation';
require_once '../includes/header.php';

// Verify login
if (!is_logged_in()) {
    set_flash_message('error', 'Please log in to view order details.');
    redirect('login.php');
}

$order_number = isset($_GET['order_number']) ? sanitize_input($_GET['order_number']) : '';

if (empty($order_number)) {
    set_flash_message('error', 'Invalid order confirmation request.');
    redirect('../index.php');
}

$db = new Database();
$conn = $db->getConnection();

// Fetch order
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = :order_number AND customer_id = :customer_id LIMIT 1");
$order_stmt->execute([
    ':order_number' => $order_number,
    ':customer_id' => $_SESSION['customer_id']
]);
$order = $order_stmt->fetch();

if (!$order) {
    set_flash_message('error', 'Order not found.');
    redirect('../index.php');
}

// POST Request Handler for submitting reviews
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $comment = sanitize_input($_POST['comment']);
    $customer_id = $_SESSION['customer_id'];
    
    // Check if customer is authorized to review (order is shipped/delivered and contains this product)
    $auth_stmt = $conn->prepare("
        SELECT oi.id 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.customer_id = :customer_id AND oi.product_id = :product_id AND o.status IN ('shipped', 'delivered')
        LIMIT 1
    ");
    $auth_stmt->execute([
        ':customer_id' => $customer_id,
        ':product_id' => $product_id
    ]);
    
    if (!$auth_stmt->fetch()) {
        set_flash_message('error', 'You can only review products from shipped or delivered orders.');
    } else {
        // Check if review already exists
        $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE product_id = :product_id AND customer_id = :customer_id LIMIT 1");
        $check_stmt->execute([
            ':product_id' => $product_id,
            ':customer_id' => $customer_id
        ]);
        
        if ($check_stmt->fetch()) {
            set_flash_message('error', 'You have already reviewed this product.');
        } else {
            // Insert review
            $insert_stmt = $conn->prepare("
                INSERT INTO reviews (product_id, customer_id, rating, comment, status) 
                VALUES (:product_id, :customer_id, :rating, :comment, 'pending')
            ");
            $success = $insert_stmt->execute([
                ':product_id' => $product_id,
                ':customer_id' => $customer_id,
                ':rating' => $rating,
                ':comment' => $comment
            ]);
            
            if ($success) {
                // Log activity
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:customer_id, 'customer', :action)");
                $log_stmt->execute([
                    ':customer_id' => $customer_id,
                    ':action' => "Submitted review for product ID {$product_id}"
                ]);
                
                set_flash_message('success', 'Review submitted successfully. It will be visible once approved.');
            } else {
                set_flash_message('error', 'Failed to submit review. Please try again.');
            }
        }
    }
    redirect('order-confirmation.php?order_number=' . urlencode($order_number));
}

// Fetch order items and check if they have been reviewed by this customer
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.slug as product_slug, r.rating as reviewed_rating, r.status as review_status
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    LEFT JOIN reviews r ON r.product_id = p.id AND r.customer_id = :customer_id
    WHERE oi.order_id = :order_id
");
$items_stmt->execute([
    ':order_id' => $order['id'],
    ':customer_id' => $_SESSION['customer_id']
]);
$order_items = $items_stmt->fetchAll();
?>

<div class="container my-5 py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center mb-5 fade-in-up">
            <div class="text-gold mb-3" style="font-size: 4rem;">
                <i class="far fa-check-circle"></i>
            </div>
            <h2 class="text-dark fw-bold mb-2">Order Confirmed</h2>
            <p class="lead text-muted">Thank you for your order. Your transaction has been completed successfully.</p>
            <span class="badge bg-gold text-dark py-2 px-3 fs-6">Order Number: <?php echo htmlspecialchars($order['order_number']); ?></span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Order details card -->
        <div class="col-md-6 fade-in-up">
            <div class="card glass-card h-100 p-4 border-0 shadow-sm" style="border-radius: 15px;">
                <h5 class="text-gold text-uppercase fw-bold mb-3 border-bottom pb-2"><i class="fas fa-info-circle me-2"></i> Transaction Details</h5>
                
                <table class="table table-borderless align-middle mb-0 text-muted">
                    <tr>
                        <td class="fw-bold text-dark text-uppercase small" style="width: 150px;">Order Date</td>
                        <td><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-dark text-uppercase small">Order Status</td>
                        <td><span class="text-uppercase fw-semibold text-warning"><?php echo htmlspecialchars($order['status']); ?></span></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-dark text-uppercase small">Payment Status</td>
                        <td><span class="text-uppercase fw-semibold text-success"><?php echo htmlspecialchars($order['payment_status']); ?></span></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-dark text-uppercase small">Payment Method</td>
                        <td><span class="text-uppercase"><?php echo htmlspecialchars($order['payment_method'] === 'cod' ? 'Cash on Delivery (COD)' : $order['payment_method']); ?></span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Address details card -->
        <div class="col-md-6 fade-in-up">
            <div class="card glass-card h-100 p-4 border-0 shadow-sm" style="border-radius: 15px;">
                <h5 class="text-gold text-uppercase fw-bold mb-3 border-bottom pb-2"><i class="fas fa-map-marker-alt me-2"></i> Shipping Address</h5>
                <p class="text-muted mb-0" style="white-space: pre-line; line-height: 1.6;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
            </div>
        </div>

        <!-- Order Items receipt table -->
        <div class="col-12 mt-4 fade-in-up">
            <div class="card glass-card p-4 border-0 shadow-sm" style="border-radius: 15px;">
                <h5 class="text-gold text-uppercase fw-bold mb-4 border-bottom pb-2"><i class="fas fa-receipt me-2"></i> Order Items</h5>
                
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted text-uppercase small">
                                <th>Product Item</th>
                                <th class="text-center">Price</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Total</th>
                                <?php if (in_array($order['status'], ['shipped', 'delivered'])): ?>
                                    <th class="text-center">Review</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <a href="../product-details.php?slug=<?php echo $item['product_slug']; ?>" class="text-decoration-none text-dark fw-semibold">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </a>
                                    </td>
                                    <td class="text-center text-muted"><?php echo format_price($item['unit_price']); ?></td>
                                    <td class="text-center text-muted"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end fw-semibold text-secondary"><?php echo format_price($item['subtotal']); ?></td>
                                    <?php if (in_array($order['status'], ['shipped', 'delivered'])): ?>
                                        <td class="text-center">
                                            <?php if ($item['reviewed_rating'] !== null): ?>
                                                <span class="badge bg-success text-white py-2 px-3 rounded-pill small">
                                                    Reviewed: <?php echo $item['reviewed_rating']; ?>★
                                                    <?php if ($item['review_status'] === 'pending'): ?>
                                                        <br><span style="font-size: 0.65rem; opacity: 0.85;">(Pending)</span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-luxury px-3 write-review-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#reviewModal" 
                                                        data-product-id="<?php echo $item['product_id']; ?>" 
                                                        data-product-name="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                    Review
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totals Summary breakdown -->
                <div class="row justify-content-end mt-4 text-muted">
                    <div class="col-md-5">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span class="fw-semibold text-dark"><?php echo format_price($order['total_amount']); ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-danger">
                                <span>Discount:</span>
                                <span class="fw-semibold">-<?php echo format_price($order['discount_amount']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (8%):</span>
                            <span class="fw-semibold text-dark"><?php echo format_price($order['tax_amount']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping Cost:</span>
                            <span class="fw-semibold text-dark"><?php echo ($order['shipping_amount'] == 0) ? '<span class="text-success text-uppercase">Free</span>' : format_price($order['shipping_amount']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-3 fs-5 text-dark">
                            <span class="fw-bold">Grand Total:</span>
                            <span class="fw-bold text-gold"><?php echo format_price($order['grand_total']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Call to Action -->
        <div class="col-12 text-center mt-5 fade-in-up">
            <a href="orders.php" class="btn btn-outline-dark px-4 py-3 me-3 text-uppercase fw-bold mb-2 mb-sm-0" style="letter-spacing: 1px;">View Order History</a>
            <a href="../shop.php" class="btn btn-luxury px-5 py-3 text-uppercase fw-bold" style="letter-spacing: 1px;">Continue Shopping</a>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0" style="backdrop-filter: blur(20px); background: rgba(255, 255, 255, 0.9);">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title text-gold" id="reviewModalLabel">Write Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="submit_review">
                <input type="hidden" name="product_id" id="modal-product-id">
                
                <div class="modal-body">
                    <!-- Star Rating Selector -->
                    <div class="mb-3 text-center">
                        <label class="form-label d-block fw-bold text-secondary mb-2">Your Rating</label>
                        <div class="star-rating fs-2 text-warning d-flex justify-content-center gap-2">
                            <i class="far fa-star star-option" data-value="1" style="cursor: pointer;"></i>
                            <i class="far fa-star star-option" data-value="2" style="cursor: pointer;"></i>
                            <i class="far fa-star star-option" data-value="3" style="cursor: pointer;"></i>
                            <i class="far fa-star star-option" data-value="4" style="cursor: pointer;"></i>
                            <i class="far fa-star star-option" data-value="5" style="cursor: pointer;"></i>
                        </div>
                        <input type="hidden" name="rating" id="review-rating-value" required>
                    </div>
                    
                    <!-- Comment Input -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">Your Comment</label>
                        <textarea name="comment" class="form-control" rows="4" placeholder="Tell us about your experience with this product..." required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-luxury">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const starOptions = document.querySelectorAll('.star-option');
    const ratingValue = document.getElementById('review-rating-value');
    
    starOptions.forEach(star => {
        star.addEventListener('mouseover', function() {
            const val = parseInt(this.getAttribute('data-value'));
            highlightStars(val);
        });
        
        star.addEventListener('mouseout', function() {
            const val = parseInt(ratingValue.value) || 0;
            highlightStars(val);
        });
        
        star.addEventListener('click', function() {
            const val = parseInt(this.getAttribute('data-value'));
            ratingValue.value = val;
            highlightStars(val);
        });
    });
    
    function highlightStars(val) {
        starOptions.forEach(star => {
            const starVal = parseInt(star.getAttribute('data-value'));
            if (starVal <= val) {
                star.classList.remove('far');
                star.classList.add('fas');
            } else {
                star.classList.remove('fas');
                star.classList.add('far');
            }
        });
    }
    
    const reviewModal = document.getElementById('reviewModal');
    if (reviewModal) {
        reviewModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const productId = button.getAttribute('data-product-id');
            const productName = button.getAttribute('data-product-name');
            
            const modalTitle = reviewModal.querySelector('.modal-title');
            const productIdInput = reviewModal.querySelector('#modal-product-id');
            
            modalTitle.textContent = 'Write Review for ' + productName;
            productIdInput.value = productId;
            
            // Reset rating and comment
            ratingValue.value = '';
            highlightStars(0);
            reviewModal.querySelector('textarea[name="comment"]').value = '';
        });
    }
});
</script>

<?php require_once '../components/footer.php'; ?>
