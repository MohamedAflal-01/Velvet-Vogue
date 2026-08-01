<?php
$page_title = 'Change Password';
require_once '../includes/header.php';

// Verify login
if (!is_logged_in()) {
    set_flash_message('error', 'Please log in to change your password.');
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Fetch customer details
$cust_stmt = $conn->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
$cust_stmt->execute([':id' => $_SESSION['customer_id']]);
$user = $cust_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        set_flash_message('error', 'All fields are required.');
    } elseif ($new_password !== $confirm_password) {
        set_flash_message('error', 'New passwords do not match.');
    } elseif (strlen($new_password) < 6) {
        set_flash_message('error', 'Password must be at least 6 characters.');
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Update to new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE customers SET password = :password WHERE id = :id");
            if ($update_stmt->execute([':password' => $hashed_password, ':id' => $_SESSION['customer_id']])) {
                set_flash_message('success', 'Password updated successfully.');
                redirect('profile.php');
            } else {
                set_flash_message('error', 'Failed to update password.');
            }
        } else {
            set_flash_message('error', 'Incorrect current password.');
        }
    }
}
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar -->
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
                    <a href="orders.php" class="list-group-item list-group-item-action">Order History</a>
                    <a href="wishlist.php" class="list-group-item list-group-item-action">Wishlist</a>
                    <a href="change-password.php" class="list-group-item list-group-item-action active bg-gold border-0">Change Password</a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                </div>
            </div>
        </div>

        <!-- Change Password Area -->
        <div class="col-md-9">
            <div class="card glass-card p-4">
                <h3 class="mb-4 text-gold"><i class="fas fa-key me-2"></i> Change Password</h3>
                
                <form action="change-password.php" method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-luxury py-3 px-5 fw-bold text-uppercase mt-2">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>
