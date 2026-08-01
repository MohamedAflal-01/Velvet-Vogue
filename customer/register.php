<?php
$page_title = 'Register';
require_once '../includes/header.php';

if (is_logged_in()) {
    redirect('profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        set_flash_message('error', 'Passwords do not match.');
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM customers WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            set_flash_message('error', 'Email address is already registered.');
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, email, password) VALUES (:first_name, :last_name, :email, :password)");
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['customer_id'] = $conn->lastInsertId();
                $_SESSION['customer_name'] = $first_name;
                set_flash_message('success', 'Registration successful! Welcome to Velvet Vogue.');
                redirect('profile.php');
            } else {
                set_flash_message('error', 'Registration failed. Please try again.');
            }
        }
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card glass-card p-4">
                <h2 class="text-center mb-4 text-gold">Create an Account</h2>
                <form action="register.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-luxury w-100 py-2">Register</button>
                </form>
                <div class="text-center mt-4">
                    <p>Already have an account? <a href="login.php" class="text-gold text-decoration-none">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>
