<?php
$page_title = 'My Orders';
require_once '../includes/header.php';

// Verify login
if (!is_logged_in()) {
    set_flash_message('error', 'Please log in to view your orders.');
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Fetch customer details
$cust_stmt = $conn->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
$cust_stmt->execute([':id' => $_SESSION['customer_id']]);
$user = $cust_stmt->fetch();

// Fetch customer orders
$orders_stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id = :customer_id ORDER BY created_at DESC");
$orders_stmt->execute([':customer_id' => $_SESSION['customer_id']]);
$orders = $orders_stmt->fetchAll();
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar (Identical layout to profile.php for consistency) -->
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
                    <a href="orders.php" class="list-group-item list-group-item-action active bg-gold border-0">Order History</a>
                    <a href="wishlist.php" class="list-group-item list-group-item-action">Wishlist</a>
                    <a href="change-password.php" class="list-group-item list-group-item-action">Change Password</a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Orders History Area -->
        <div class="col-md-9">
            <div class="card glass-card p-4">
                <h3 class="mb-4 text-gold"><i class="fas fa-history me-2"></i> Order History</h3>
                
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-receipt mb-3" style="font-size: 3rem;"></i>
                        <h5>No orders found.</h5>
                        <p class="mb-0">You have not placed any orders yet. Visit our shop to find something beautiful.</p>
                        <a href="../shop.php" class="btn btn-luxury mt-3">Shop Collections</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr class="text-muted text-uppercase small">
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Payment Status</th>
                                    <th>Order Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($order['order_number']); ?></span>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td class="fw-semibold text-dark">
                                            <?php echo format_price($order['grand_total']); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $pay_status = $order['payment_status'];
                                            $badge_class = 'bg-secondary';
                                            if ($pay_status === 'paid') $badge_class = 'bg-success';
                                            if ($pay_status === 'unpaid') $badge_class = 'bg-warning text-dark';
                                            if ($pay_status === 'failed') $badge_class = 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?> text-uppercase" style="font-size: 0.75rem;">
                                                <?php echo htmlspecialchars($pay_status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $order_status = $order['status'];
                                            $badge_class = 'bg-secondary';
                                            if ($order_status === 'pending') $badge_class = 'bg-warning text-dark';
                                            if ($order_status === 'processing') $badge_class = 'bg-info text-dark';
                                            if ($order_status === 'shipped') $badge_class = 'bg-primary';
                                            if ($order_status === 'delivered') $badge_class = 'bg-success';
                                            if ($order_status === 'cancelled') $badge_class = 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?> text-uppercase" style="font-size: 0.75rem;">
                                                <?php echo htmlspecialchars($order_status); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="order-confirmation.php?order_number=<?php echo urlencode($order['order_number']); ?>" class="btn btn-sm btn-luxury px-3">
                                                <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'Details / Review' : 'Receipt'; ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>
