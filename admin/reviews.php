<?php
$active_page = 'reviews';
$page_title = 'Manage Reviews';
require_once 'includes/header.php';

// Handle Action (Approve / Reject / Pending)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = sanitize_input($_GET['action']);
    
    if (in_array($status, ['approved', 'rejected', 'pending'])) {
        $stmt = $conn->prepare("UPDATE reviews SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
        
        // Log activity
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
        $log_stmt->execute([
            ':admin_id' => $_SESSION['admin_id'],
            ':action' => "Set review ID {$id} status to '{$status}'"
        ]);
        
        set_flash_message('success', 'Review status updated successfully.');
    }
    redirect('reviews.php');
}

// Search and Filter parameters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Build SQL query
$sql = "SELECT r.*, p.name as product_name, c.first_name, c.last_name, c.email 
        FROM reviews r 
        JOIN products p ON r.product_id = p.id 
        JOIN customers c ON r.customer_id = c.id";

$where_clauses = [];
$params = [];

if (!empty($status_filter)) {
    $where_clauses[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

if ($rating_filter > 0) {
    $where_clauses[] = "r.rating = :rating";
    $params[':rating'] = $rating_filter;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();
?>

<!-- Filters Control Panel -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="reviews.php" class="row g-3 align-items-center mb-0">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Review Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="rating" class="form-select">
                    <option value="">All Ratings</option>
                    <option value="5" <?php echo $rating_filter === 5 ? 'selected' : ''; ?>>5 Stars ★★★★★</option>
                    <option value="4" <?php echo $rating_filter === 4 ? 'selected' : ''; ?>>4 Stars ★★★★☆</option>
                    <option value="3" <?php echo $rating_filter === 3 ? 'selected' : ''; ?>>3 Stars ★★★☆☆</option>
                    <option value="2" <?php echo $rating_filter === 2 ? 'selected' : ''; ?>>2 Stars ★★☆☆☆</option>
                    <option value="1" <?php echo $rating_filter === 1 ? 'selected' : ''; ?>>1 Star ★☆☆☆☆</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <button type="submit" class="btn btn-luxury w-100">Apply Filters</button>
            </div>
            
            <div class="col-md-2">
                <a href="reviews.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Reviews List -->
<div class="card shadow-sm border-0">
    <div class="card-header card-header-custom">
        <span class="fs-5"><i class="fas fa-star me-2 text-warning"></i> Customer Reviews (<?php echo count($reviews); ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Reviewer</th>
                        <th>Product</th>
                        <th>Rating</th>
                        <th style="width: 40%;">Comment</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-comments fs-2 mb-2 d-block"></i>
                                No reviews found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                            <tr>
                                <td class="ps-3">
                                    <strong><?php echo htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']); ?></strong>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($rev['email']); ?></small>
                                </td>
                                <td><span class="fw-semibold text-secondary"><?php echo htmlspecialchars($rev['product_name']); ?></span></td>
                                <td>
                                    <div class="text-warning small">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rev['rating']) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <small class="text-muted"><?php echo $rev['rating']; ?>/5</small>
                                </td>
                                <td>
                                    <p class="mb-0 text-secondary small" style="white-space: pre-line; max-height: 80px; overflow-y: auto;">
                                        <?php echo htmlspecialchars($rev['comment'] ?: 'No comment left.'); ?>
                                    </p>
                                </td>
                                <td class="small text-muted"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $badge = 'bg-secondary';
                                    if ($rev['status'] === 'approved') $badge = 'bg-success';
                                    elseif ($rev['status'] === 'rejected') $badge = 'bg-danger';
                                    elseif ($rev['status'] === 'pending') $badge = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> text-uppercase"><?php echo $rev['status']; ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <?php if ($rev['status'] !== 'approved'): ?>
                                            <a href="reviews.php?id=<?php echo $rev['id']; ?>&action=approved" class="btn btn-sm btn-outline-success" title="Approve Review">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($rev['status'] !== 'rejected'): ?>
                                            <a href="reviews.php?id=<?php echo $rev['id']; ?>&action=rejected" class="btn btn-sm btn-outline-danger" title="Reject Review">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
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
