<?php
$active_page = 'dashboard';
$page_title = 'Dashboard Overview';
require_once 'includes/header.php';

// Fetch Dashboard Statistics
// 1. Total Sales
$stmt = $conn->query("SELECT SUM(grand_total) as total_sales FROM orders WHERE status != 'cancelled'");
$sales = $stmt->fetch()['total_sales'] ?? 0;

// 2. Total Orders
$stmt = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
$orders_count = $stmt->fetch()['total_orders'];

// 3. Total Customers
$stmt = $conn->query("SELECT COUNT(*) as total_customers FROM customers");
$customers_count = $stmt->fetch()['total_customers'];

// 4. Total Products
$stmt = $conn->query("SELECT COUNT(*) as total_products FROM products");
$products_count = $stmt->fetch()['total_products'];

// 5. Recent Orders
$stmt = $conn->query("
    SELECT o.id, o.order_number, o.grand_total, o.status, o.created_at, c.first_name, c.last_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    ORDER BY o.created_at DESC LIMIT 5
");
$recent_orders = $stmt->fetchAll();

// 6. Low Stock Alerts
$stmt = $conn->query("SELECT id, name, stock_quantity, sku FROM products WHERE stock_quantity <= 10 ORDER BY stock_quantity ASC LIMIT 5");
$low_stock = $stmt->fetchAll();
?>


        <!-- Stat Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small">Total Sales</p>
                        <h3 class="mb-0"><?php echo format_price($sales); ?></h3>
                    </div>
                    <i class="fas fa-money-bill-wave stat-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small">Total Orders</p>
                        <h3 class="mb-0"><?php echo $orders_count; ?></h3>
                    </div>
                    <i class="fas fa-shopping-cart stat-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small">Total Customers</p>
                        <h3 class="mb-0"><?php echo $customers_count; ?></h3>
                    </div>
                    <i class="fas fa-users stat-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small">Total Products</p>
                        <h3 class="mb-0"><?php echo $products_count; ?></h3>
                    </div>
                    <i class="fas fa-box stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Orders -->
            <div class="col-md-8 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header card-header-custom">
                        <i class="fas fa-list me-2"></i> Recent Orders
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($recent_orders)): ?>
                                        <tr><td colspan="6" class="text-center py-3">No orders found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo format_price($order['grand_total']); ?></td>
                                                <td>
                                                    <?php 
                                                        $badge = 'bg-secondary';
                                                        if($order['status'] == 'pending') $badge = 'bg-warning text-dark';
                                                        elseif($order['status'] == 'processing') $badge = 'bg-info text-dark';
                                                        elseif($order['status'] == 'shipped') $badge = 'bg-primary';
                                                        elseif($order['status'] == 'delivered') $badge = 'bg-success';
                                                        elseif($order['status'] == 'cancelled') $badge = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $badge; ?> text-uppercase"><?php echo $order['status']; ?></span>
                                                </td>
                                                <td><a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-dark">View</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alerts -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-danger text-white font-weight-bold">
                        <i class="fas fa-exclamation-triangle me-2"></i> Low Stock Alerts
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if(empty($low_stock)): ?>
                                <li class="list-group-item text-center py-3">All products are well stocked.</li>
                            <?php else: ?>
                                <?php foreach($low_stock as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <small class="text-muted">SKU: <?php echo htmlspecialchars($item['sku']); ?></small>
                                        </div>
                                        <span class="badge bg-danger rounded-pill"><?php echo $item['stock_quantity']; ?> left</span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
<?php require_once 'includes/footer.php'; ?>
