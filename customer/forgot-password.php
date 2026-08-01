<?php
$page_title = 'Forgot Password';
require_once '../includes/header.php';

if (is_logged_in()) {
    redirect('profile.php');
}

$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('error', 'Please enter a valid email address.');
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        // Check if email exists in customers table
        $stmt = $conn->prepare("SELECT id, first_name, status FROM customers WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] === 'active') {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Clean up previous tokens for this email
                $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = :email");
                $delete_stmt->execute([':email' => $email]);

                // Insert new reset token
                $insert_stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)");
                $insert_stmt->execute([
                    ':email' => $email,
                    ':token' => $token,
                    ':expires_at' => $expires_at
                ]);

                // Generate reset URL
                $reset_link = BASE_URL . 'customer/reset-password.php?token=' . $token;
                
                set_flash_message('success', 'A password reset link has been successfully generated.');
            } else {
                set_flash_message('error', 'Your account is ' . $user['status'] . '. Please contact support.');
            }
        } else {
            // Still show success to prevent email discovery (standard security practice)
            // But do not generate a link since no user exists
            set_flash_message('success', 'If that email address exists in our system, a password reset link has been generated.');
        }
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card glass-card p-4">
                <h2 class="text-center mb-4 text-gold">Forgot Password</h2>
                <p class="text-muted text-center mb-4">Enter your email address below, and we will send you instructions to reset your password.</p>
                
                <form action="forgot-password.php" method="POST">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="e.g. john@example.com">
                    </div>
                    
                    <button type="submit" class="btn btn-luxury w-100 py-3 fw-bold text-uppercase">Send Reset Link</button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="mb-0">Remembered your password? <a href="login.php" class="text-gold text-decoration-none">Sign In</a></p>
                </div>
            </div>
            
            <?php if (!empty($reset_link)): ?>
                <!-- Local development testing helper alert -->
                <div class="alert alert-info mt-4 border-2 border-info shadow-sm" role="alert">
                    <h5 class="alert-heading fw-bold"><i class="fas fa-info-circle me-2"></i>[Local Test Mode] Reset Email Simulator</h5>
                    <p class="mb-2">Since this application is running locally without an active SMTP server, the email was simulated. You can test the password reset flow using the link below:</p>
                    <hr>
                    <a href="<?php echo $reset_link; ?>" class="btn btn-outline-info btn-sm fw-bold text-decoration-none"><i class="fas fa-external-link-alt me-1"></i> Proceed to Reset Password</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>
