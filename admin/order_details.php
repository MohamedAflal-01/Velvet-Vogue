<?php
$active_page = 'orders';
$page_title = 'Order Details';
require_once 'includes/header.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission to update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_status = sanitize_input($_POST['status']);
    $payment_status = sanitize_input($_POST['payment_status']);
    
    $update_stmt = $conn->prepare("UPDATE orders SET status = :status, payment_status = :payment_status WHERE id = :id");
    $update_stmt->execute([
        ':status' => $order_status,
        ':payment_status' => $payment_status,
        ':id' => $order_id
    ]);
    
    // Log activity
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
    $log_stmt->execute([
        ':admin_id' => $_SESSION['admin_id'],
        ':action' => "Updated order ID {$order_id} status to '{$order_status}' and payment status to '{$payment_status}'"
    ]);

    set_flash_message('success', 'Order status updated successfully.');
    redirect("order_details.php?id=" . $order_id);
}

// Fetch order information
$stmt = $conn->prepare("
    SELECT o.*, c.first_name, c.last_name, c.email, c.phone 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.id = :id LIMIT 1
");
$stmt->execute([':id' => $order_id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash_message('error', 'Order not found.');
    redirect('orders.php');
}

// Fetch order items
$stmt_items = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.sku, 
           s.name as size_name, col.name as color_name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    LEFT JOIN product_variants pv ON oi.variant_id = pv.id
    LEFT JOIN sizes s ON pv.size_id = s.id 
    LEFT JOIN colors col ON pv.color_id = col.id
    WHERE oi.order_id = :order_id
");
$stmt_items->execute([':order_id' => $order_id]);
$items = $stmt_items->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12">
        <a href="orders.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Orders</a>
    </div>
</div>

<div class="row">
    <!-- Main Order Details -->
    <div class="col-lg-8">
        <!-- Order Items Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-shopping-bag me-2"></i> Order Items - #<?php echo htmlspecialchars($order['order_number']); ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Product SKU / Name</th>
                                <th>Variant</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th class="text-end pe-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="ps-3">
                                        <span class="text-muted small d-block"><?php echo htmlspecialchars($item['sku']); ?></span>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($item['size_name'] || $item['color_name']): ?>
                                            <span class="small text-secondary">
                                                <?php echo htmlspecialchars($item['size_name'] ?? ''); ?>
                                                <?php echo ($item['size_name'] && $item['color_name']) ? ' / ' : ''; ?>
                                                <?php echo htmlspecialchars($item['color_name'] ?? ''); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo format_price($item['unit_price']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td class="text-end pe-3 fw-bold"><?php echo format_price($item['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Calculations -->
                            <tr class="table-light border-top">
                                <td colspan="3"></td>
                                <td class="text-end">Subtotal:</td>
                                <td class="text-end pe-3 font-monospace"><?php echo format_price($order['total_amount']); ?></td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="3"></td>
                                <td class="text-end">Tax:</td>
                                <td class="text-end pe-3 font-monospace"><?php echo format_price($order['tax_amount']); ?></td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="3"></td>
                                <td class="text-end">Shipping:</td>
                                <td class="text-end pe-3 font-monospace"><?php echo format_price($order['shipping_amount']); ?></td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="3"></td>
                                <td class="text-end">Discount:</td>
                                <td class="text-end pe-3 text-danger font-monospace">-<?php echo format_price($order['discount_amount']); ?></td>
                            </tr>
                            <tr class="table-dark fs-5">
                                <td colspan="3" class="bg-dark border-0"></td>
                                <td class="text-end bg-dark text-white border-0 fw-bold">Grand Total:</td>
                                <td class="text-end pe-3 bg-dark text-warning border-0 fw-bold font-monospace"><?php echo format_price($order['grand_total']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Shipping and Billing Addresses -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-truck me-2 text-muted"></i> Shipping Address</div>
                    <div class="card-body">
                        <p class="card-text text-secondary mb-0" style="white-space: pre-line;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-muted"></i> Billing Address</div>
                    <div class="card-body">
                        <p class="card-text text-secondary mb-0" style="white-space: pre-line;"><?php echo htmlspecialchars($order['billing_address']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Info and Control Panels -->
    <div class="col-lg-4">
        <!-- Update Status Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header card-header-custom">
                <i class="fas fa-edit me-2"></i> Update Order Status
            </div>
            <div class="card-body">
                <form method="POST" action="order_details.php?id=<?php echo $order['id']; ?>">
                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold">Order Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_status" class="form-label fw-semibold">Payment Status</label>
                        <select name="payment_status" id="payment_status" class="form-select">
                            <option value="unpaid" <?php echo $order['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-luxury w-100">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Customer Profile Card -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-user-circle me-2 text-muted"></i> Customer Info
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 100px;">Name:</td>
                        <td class="fw-semibold"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email:</td>
                        <td><a href="mailto:<?php echo htmlspecialchars($order['email']); ?>"><?php echo htmlspecialchars($order['email']); ?></a></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Phone:</td>
                        <td><?php echo htmlspecialchars($order['phone'] ?: 'Not Provided'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Order Date:</td>
                        <td class="small"><?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Method:</td>
                        <td class="text-uppercase small"><?php echo htmlspecialchars($order['payment_method']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
