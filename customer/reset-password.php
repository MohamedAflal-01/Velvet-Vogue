<?php
$page_title = 'Reset Password';
require_once '../includes/header.php';

if (is_logged_in()) {
    redirect('profile.php');
}

$db = new Database();
$conn = $db->getConnection();

$token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';
$valid_token = false;
$email = '';

if (!empty($token)) {
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = :token AND expires_at > :now LIMIT 1");
    $stmt->execute([':token' => $token, ':now' => $now]);
    $reset = $stmt->fetch();

    if ($reset) {
        $valid_token = true;
        $email = $reset['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        set_flash_message('error', 'All fields are required.');
    } elseif ($new_password !== $confirm_password) {
        set_flash_message('error', 'Passwords do not match.');
    } elseif (strlen($new_password) < 6) {
        set_flash_message('error', 'Password must be at least 6 characters.');
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in customers table
        $update_stmt = $conn->prepare("UPDATE customers SET password = :password WHERE email = :email");
        if ($update_stmt->execute([':password' => $hashed_password, ':email' => $email])) {
            // Delete the token
            $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = :email");
            $delete_stmt->execute([':email' => $email]);

            set_flash_message('success', 'Your password has been reset successfully. You can now sign in.');
            redirect('login.php');
        } else {
            set_flash_message('error', 'Failed to reset password. Please try again.');
        }
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <?php if ($valid_token): ?>
                <div class="card glass-card p-4">
                    <h2 class="text-center mb-4 text-gold">Reset Password</h2>
                    <p class="text-muted text-center mb-4">Please enter and confirm your new password below.</p>
                    
                    <form action="reset-password.php?token=<?php echo urlencode($token); ?>" method="POST">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" placeholder="At least 6 characters">
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Confirm your new password">
                        </div>
                        
                        <button type="submit" class="btn btn-luxury w-100 py-3 fw-bold text-uppercase">Reset Password</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="card glass-card p-4 text-center">
                    <div class="text-danger mb-3" style="font-size: 48px;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="text-gold mb-3">Invalid Link</h2>
                    <p class="text-muted">This password reset link is invalid, has already been used, or has expired.</p>
                    <a href="forgot-password.php" class="btn btn-luxury py-2 px-4 mt-3">Request New Link</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>
