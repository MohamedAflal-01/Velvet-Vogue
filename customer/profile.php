<?php
$page_title = 'My Profile';
require_once '../includes/header.php';

if (!is_logged_in()) {
    set_flash_message('error', 'Please login to view your profile.');
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = :id");
$stmt->bindParam(':id', $_SESSION['customer_id']);
$stmt->execute();
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $city = sanitize_input($_POST['city']);
    $state = sanitize_input($_POST['state']);
    $zip_code = sanitize_input($_POST['zip_code']);
    $country = sanitize_input($_POST['country']);

    $update_stmt = $conn->prepare("UPDATE customers SET first_name = :first_name, last_name = :last_name, phone = :phone, address = :address, city = :city, state = :state, zip_code = :zip_code, country = :country WHERE id = :id");
    
    $update_stmt->bindParam(':first_name', $first_name);
    $update_stmt->bindParam(':last_name', $last_name);
    $update_stmt->bindParam(':phone', $phone);
    $update_stmt->bindParam(':address', $address);
    $update_stmt->bindParam(':city', $city);
    $update_stmt->bindParam(':state', $state);
    $update_stmt->bindParam(':zip_code', $zip_code);
    $update_stmt->bindParam(':country', $country);
    $update_stmt->bindParam(':id', $_SESSION['customer_id']);
    
    if ($update_stmt->execute()) {
        $_SESSION['customer_name'] = $first_name;
        set_flash_message('success', 'Profile updated successfully.');
        redirect('profile.php');
    } else {
        set_flash_message('error', 'Failed to update profile.');
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
                    <a href="profile.php" class="list-group-item list-group-item-action active bg-gold border-0">Edit Profile</a>
                    <a href="orders.php" class="list-group-item list-group-item-action">Order History</a>
                    <a href="wishlist.php" class="list-group-item list-group-item-action">Wishlist</a>
                    <a href="change-password.php" class="list-group-item list-group-item-action">Change Password</a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card glass-card p-4">
                <h3 class="mb-4 text-gold">Edit Profile</h3>
                <form action="profile.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address (Cannot be changed)</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="zip_code" class="form-label">Zip Code</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($user['country'] ?? 'US'); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-luxury mt-3">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>
