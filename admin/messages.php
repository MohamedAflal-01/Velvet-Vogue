<?php
$active_page = 'messages';
$page_title = 'Customer Messages';
require_once 'includes/header.php';

// Handle Mark as Read / View Message
$view_message = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    
    // Fetch message
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $view_id]);
    $view_message = $stmt->fetch();
    
    if ($view_message) {
        if (!$view_message['is_read']) {
            // Update to read status
            $up_stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = :id");
            $up_stmt->execute([':id' => $view_id]);
            
            // Log activity
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
            $log_stmt->execute([
                ':admin_id' => $_SESSION['admin_id'],
                ':action' => "Read customer message ID {$view_id} (Subject: {$view_message['subject']})"
            ]);
        }
    }
}

// Handle Delete Message
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = :id");
    $stmt->execute([':id' => $delete_id]);
    
    // Log activity
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
    $log_stmt->execute([
        ':admin_id' => $_SESSION['admin_id'],
        ':action' => "Deleted customer message ID {$delete_id}"
    ]);

    set_flash_message('success', 'Message deleted successfully.');
    redirect('messages.php');
}

// Search and filters
$filter = isset($_GET['filter']) ? sanitize_input($_GET['filter']) : '';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build SQL query
$sql = "SELECT * FROM contact_messages";
$where_clauses = [];
$params = [];

if ($filter === 'unread') {
    $where_clauses[] = "is_read = 0";
} elseif ($filter === 'read') {
    $where_clauses[] = "is_read = 1";
}

if (!empty($search_query)) {
    $where_clauses[] = "(name LIKE :search OR email LIKE :search OR subject LIKE :search OR message LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();
?>

<!-- Active Message Detail (If Viewing) -->
<?php if ($view_message): ?>
    <div class="card shadow-sm border-0 border-start border-4 border-warning mb-4 animate-fade-in">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-envelope-open me-2 text-warning"></i> <?php echo htmlspecialchars($view_message['subject']); ?></h5>
            <div>
                <a href="messages.php?delete=<?php echo $view_message['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this message?')"><i class="fas fa-trash-alt me-1"></i> Delete</a>
                <a href="messages.php" class="btn btn-sm btn-outline-secondary ms-1"><i class="fas fa-times me-1"></i> Close</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3 pb-3 border-bottom text-muted">
                <div class="col-md-6">
                    <span class="d-block">From: <strong><?php echo htmlspecialchars($view_message['name']); ?></strong> &lt;<?php echo htmlspecialchars($view_message['email']); ?>&gt;</span>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="d-block">Received: <?php echo date('F d, Y h:i A', strtotime($view_message['created_at'])); ?></span>
                </div>
            </div>
            <p class="card-text text-secondary mb-0 p-3 bg-light rounded" style="white-space: pre-line; line-height: 1.6;">
                <?php echo htmlspecialchars($view_message['message']); ?>
            </p>
            <div class="mt-3">
                <a href="mailto:<?php echo htmlspecialchars($view_message['email']); ?>?subject=Re: <?php echo rawurlencode($view_message['subject']); ?>" class="btn btn-luxury"><i class="fas fa-reply me-1"></i> Reply via Email</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Filters Control Panel -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="messages.php" class="row g-3 align-items-center mb-0">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search message text..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="filter" class="form-select">
                    <option value="">All Messages</option>
                    <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Unread Only</option>
                    <option value="read" <?php echo $filter === 'read' ? 'selected' : ''; ?>>Read Only</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <button type="submit" class="btn btn-luxury w-100">Apply Filters</button>
            </div>
            
            <div class="col-md-2">
                <a href="messages.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Messages Table -->
<div class="card shadow-sm border-0">
    <div class="card-header card-header-custom">
        <span class="fs-5"><i class="fas fa-envelope me-2"></i> Messages List (<?php echo count($messages); ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">Status</th>
                        <th>Sender Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Received Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fs-2 mb-2 d-block"></i>
                                No messages found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="<?php echo !$msg['is_read'] ? 'table-warning fw-semibold' : 'text-secondary'; ?>" style="cursor: pointer;" onclick="window.location='messages.php?view=<?php echo $msg['id']; ?>'">
                                <td class="ps-3 text-center">
                                    <?php if (!$msg['is_read']): ?>
                                        <i class="fas fa-envelope text-warning" title="Unread"></i>
                                    <?php else: ?>
                                        <i class="fas fa-envelope-open text-muted" title="Read"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($msg['name']); ?></td>
                                <td><?php echo htmlspecialchars($msg['email']); ?></td>
                                <td>
                                    <span class="d-inline-block text-truncate" style="max-width: 300px;">
                                        <?php echo htmlspecialchars($msg['subject']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                <td class="text-center" onclick="event.stopPropagation();">
                                    <div class="btn-group" role="group">
                                        <a href="messages.php?view=<?php echo $msg['id']; ?>" class="btn btn-sm btn-outline-dark" title="View Message">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="messages.php?delete=<?php echo $msg['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete Message" onclick="return confirm('Are you sure you want to delete this message?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
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
