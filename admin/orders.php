<?php
$active_page = 'orders';
$page_title = 'Manage Orders';
require_once 'includes/header.php';

// Filtering and Search parameters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build SQL query
$sql = "SELECT o.id, o.order_number, o.grand_total, o.status, o.payment_status, o.created_at, c.first_name, c.last_name, c.email 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.id";
$where_clauses = [];
$params = [];

if (!empty($status_filter)) {
    $where_clauses[] = "o.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search_query)) {
    $where_clauses[] = "(o.order_number LIKE :search OR c.first_name LIKE :search OR c.last_name LIKE :search OR c.email LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<!-- Orders Filter & Search Control Panel -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="orders.php" class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by Order # or Customer..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Order Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <button type="submit" class="btn btn-luxury w-100">Apply Filters</button>
            </div>
            
            <div class="col-md-2">
                <a href="orders.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card shadow-sm border-0">
    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
        <span class="fs-5"><i class="fas fa-list me-2"></i> Order List (<?php echo count($orders); ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Order Number</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Order Date</th>
                        <th>Grand Total</th>
                        <th>Payment</th>
                        <th>Order Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-box-open fs-2 mb-2 d-block"></i>
                                No orders found matching the criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="ps-3 fw-bold">#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['email']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                <td class="fw-semibold"><?php echo format_price($order['grand_total']); ?></td>
                                <td>
                                    <?php
                                    $p_badge = 'bg-secondary';
                                    if ($order['payment_status'] === 'paid') $p_badge = 'bg-success';
                                    elseif ($order['payment_status'] === 'failed') $p_badge = 'bg-danger';
                                    elseif ($order['payment_status'] === 'unpaid') $p_badge = 'bg-warning text-dark';
                                    elseif ($order['payment_status'] === 'refunded') $p_badge = 'bg-info text-dark';
                                    ?>
                                    <span class="badge <?php echo $p_badge; ?> text-uppercase"><?php echo $order['payment_status']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $o_badge = 'badge-pending';
                                    if ($order['status'] === 'processing') $o_badge = 'badge-processing';
                                    elseif ($order['status'] === 'shipped') $o_badge = 'badge-shipped';
                                    elseif ($order['status'] === 'delivered') $o_badge = 'badge-delivered';
                                    elseif ($order['status'] === 'cancelled') $o_badge = 'badge-cancelled';
                                    ?>
                                    <span class="badge <?php echo $o_badge; ?> text-uppercase"><?php echo $order['status']; ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-luxury">
                                        <i class="fas fa-eye me-1"></i> View/Update
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
