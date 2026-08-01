<?php
require_once '../config/database.php';
require_once '../functions/helpers.php';

if (isset($_SESSION['admin_id'])) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id, password, full_name, role FROM admins WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        redirect('index.php');
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Velvet Vogue</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at 50% 0%, #171520 0%, #07070a 75%);
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin: 0;
            overflow: hidden;
            color: #f1f5f9;
        }
        
        .ambient-dot-1 {
            position: absolute;
            top: 20%;
            left: 15%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.06) 0%, rgba(212, 175, 55, 0) 70%);
            z-index: 1;
            pointer-events: none;
        }
        
        .ambient-dot-2 {
            position: absolute;
            bottom: 15%;
            right: 10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(147, 51, 234, 0.04) 0%, rgba(147, 51, 234, 0) 70%);
            z-index: 1;
            pointer-events: none;
        }

        .login-card {
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 20px !important;
            background: rgba(255, 255, 255, 0.03) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5) !important;
            overflow: hidden;
            width: 90%;
            max-width: 440px;
            z-index: 3;
            animation: loginFadeInUp 1s cubic-bezier(0.25, 1, 0.5, 1) forwards;
        }

        @keyframes loginFadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: rgba(255, 255, 255, 0.01) !important;
            color: #d4af37;
            padding: 35px 30px 15px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .login-header h2 {
            letter-spacing: 4px;
            font-size: 1.8rem;
        }

        .login-body {
            padding: 35px 40px 40px 40px;
            background: transparent !important;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-right: none !important;
            color: rgba(255, 255, 255, 0.5) !important;
            border-top-left-radius: 8px !important;
            border-bottom-left-radius: 8px !important;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-left: none !important;
            color: #ffffff !important;
            border-top-right-radius: 8px !important;
            border-bottom-right-radius: 8px !important;
            padding: 10px 15px !important;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.05) !important;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.25) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #fff !important;
        }

        .btn-luxury {
            background-color: #d4af37 !important;
            color: #000000 !important;
            border: 1px solid #d4af37 !important;
            border-radius: 8px !important;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-luxury:hover {
            background-color: transparent !important;
            color: #d4af37 !important;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.3) !important;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.15) !important;
            border: 1px solid rgba(220, 53, 69, 0.25) !important;
            color: #ff6b7b !important;
            border-radius: 8px !important;
        }
    </style>
</head>
<body>

<div class="ambient-dot-1"></div>
<div class="ambient-dot-2"></div>

<div class="login-card">
    <div class="login-header">
        <h2 class="mb-0 text-uppercase fw-bold">Velvet Vogue</h2>
        <p class="mb-0 text-white-50 mt-2">Admin Portal</p>
    </div>
    <div class="login-body">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" required autocomplete="off">
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-luxury w-100 py-2">Login to Dashboard</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
