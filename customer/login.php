<?php
$page_title = 'Login';
require_once '../includes/header.php';

if (is_logged_in()) {
    $redirect_url = isset($_GET['redirect']) ? sanitize_input($_GET['redirect']) : 'profile.php';
    redirect($redirect_url);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id, password, first_name, status FROM customers WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'active') {
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['customer_name'] = $user['first_name'];
            set_flash_message('success', 'Welcome back, ' . $user['first_name'] . '!');
            $redirect_url = isset($_GET['redirect']) ? sanitize_input($_GET['redirect']) : 'profile.php';
            redirect($redirect_url);
        } else {
            set_flash_message('error', 'Your account is ' . $user['status'] . '. Please contact support.');
        }
    } else {
        set_flash_message('error', 'Invalid email or password.');
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card glass-card p-4">
                <h2 class="text-center mb-4 text-gold">Customer Login</h2>
                <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode(sanitize_input($_GET['redirect'])) : ''; ?>" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="text-decoration-none text-muted">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn btn-luxury w-100 py-2">Sign In</button>
                </form>
                <div class="text-center mt-4">
                    <p>Don't have an account? <a href="register.php" class="text-gold text-decoration-none">Create one</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>
