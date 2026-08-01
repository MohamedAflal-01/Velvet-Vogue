<?php
$active_page = 'customers';
$page_title = 'Manage Customers';
require_once 'includes/header.php';

// Handle Status Change Action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = sanitize_input($_GET['action']);
    
    if (in_array($status, ['active', 'inactive', 'banned'])) {
        $stmt = $conn->prepare("UPDATE customers SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
        
        // Log activity
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
        $log_stmt->execute([
            ':admin_id' => $_SESSION['admin_id'],
            ':action' => "Changed customer (ID: {$id}) status to '{$status}'"
        ]);
        
        set_flash_message('success', 'Customer status updated successfully.');
    }
    redirect('customers.php');
}

// Search and Filter parameters
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Build SQL query
$sql = "SELECT * FROM customers";
$where_clauses = [];
$params = [];

if (!empty($search_query)) {
    $where_clauses[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

if (!empty($status_filter)) {
    $where_clauses[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<!-- Customers Search & Filters Control Panel -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="customers.php" class="row g-3 align-items-center mb-0">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by Name, Email, Phone..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>Banned</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-luxury w-100">Apply</button>
            </div>
            
            <div class="col-md-2">
                <a href="customers.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Customers Table -->
<div class="card shadow-sm border-0">
    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
        <span class="fs-5"><i class="fas fa-users me-2"></i> Customers List (<?php echo count($customers); ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Joined Date</th>
                        <th>Status</th>
                        <th class="text-center">Action / Status Toggle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-users-slash fs-2 mb-2 d-block"></i>
                                No customers found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $cust): ?>
                            <tr>
                                <td class="ps-3 text-secondary"><?php echo $cust['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?></strong></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($cust['email']); ?>"><?php echo htmlspecialchars($cust['email']); ?></a></td>
                                <td><?php echo htmlspecialchars($cust['phone'] ?: '-'); ?></td>
                                <td>
                                    <?php 
                                    $loc = array_filter([$cust['city'], $cust['country']]);
                                    echo htmlspecialchars(!empty($loc) ? implode(', ', $loc) : 'Not Provided'); 
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($cust['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-inactive';
                                    if ($cust['status'] === 'active') $badge_class = 'badge-active';
                                    elseif ($cust['status'] === 'banned') $badge_class = 'badge-banned';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> text-uppercase"><?php echo $cust['status']; ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-luxury dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage Status
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item text-success <?php echo $cust['status'] === 'active' ? 'disabled fw-bold' : ''; ?>" href="customers.php?id=<?php echo $cust['id']; ?>&action=active"><i class="fas fa-check-circle me-2"></i> Set Active</a></li>
                                            <li><a class="dropdown-item text-secondary <?php echo $cust['status'] === 'inactive' ? 'disabled fw-bold' : ''; ?>" href="customers.php?id=<?php echo $cust['id']; ?>&action=inactive"><i class="fas fa-pause-circle me-2"></i> Set Inactive</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger <?php echo $cust['status'] === 'banned' ? 'disabled fw-bold' : ''; ?>" href="customers.php?id=<?php echo $cust['id']; ?>&action=banned"><i class="fas fa-ban me-2"></i> Ban Customer</a></li>
                                        </ul>
                                    </div>
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
