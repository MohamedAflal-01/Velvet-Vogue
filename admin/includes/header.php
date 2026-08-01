<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | Velvet Vogue Admin' : 'Admin Portal | Velvet Vogue'; ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --accent-color: #d4af37;
            --accent-hover: #f1c40f;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-shadow: 0 16px 40px 0 rgba(0, 0, 0, 0.4);
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background: radial-gradient(circle at 50% 0%, #171520 0%, #07070a 75%);
            background-attachment: fixed;
            color: var(--text-main); 
            overflow-x: hidden; 
            min-height: 100vh;
        }

        /* Sidebar Glassmorphism */
        .sidebar { 
            min-height: 100vh; 
            background: rgba(11, 11, 15, 0.7) !important; 
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            width: 250px; 
            position: fixed; 
            z-index: 1000;
        }
        .sidebar a { 
            color: #aaa; 
            text-decoration: none; 
            padding: 14px 20px; 
            display: block; 
            transition: all 0.3s ease; 
            border-left: 4px solid transparent;
        }
        .sidebar a:hover, .sidebar a.active { 
            background-color: rgba(255, 255, 255, 0.04) !important; 
            color: var(--accent-color) !important; 
            border-left: 4px solid var(--accent-color); 
        }
        .sidebar-brand { 
            padding: 25px 20px; 
            font-size: 1.5rem; 
            font-weight: bold; 
            color: var(--accent-color); 
            text-align: center; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.05); 
            margin-bottom: 20px; 
            letter-spacing: 2px;
        }
        
        .main-content { 
            padding: 30px 40px; 
            margin-left: 250px; 
            width: calc(100% - 250px); 
            min-height: 100vh; 
        }

        /* Top Nav Glassmorphism */
        .top-nav { 
            background: rgba(255, 255, 255, 0.03) !important; 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            padding: 15px 25px; 
            box-shadow: none; 
            margin-bottom: 25px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-radius: 12px; 
            color: #fff;
        }

        /* Widget Stats Card */
        .stat-card { 
            background: rgba(255, 255, 255, 0.03) !important; 
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border) !important;
            border-radius: 12px; 
            padding: 20px; 
            box-shadow: var(--glass-shadow); 
            border-bottom: 4px solid var(--accent-color) !important; 
            transition: transform 0.3s, box-shadow 0.3s; 
            color: #fff;
        }
        .stat-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.15);
        }
        .stat-icon { 
            font-size: 2.5rem; 
            color: var(--accent-color); 
            opacity: 0.9; 
        }

        /* Card Custom Headers */
        .card-header-custom { 
            background-color: rgba(255, 255, 255, 0.02) !important; 
            border-bottom: 1px solid var(--glass-border) !important;
            color: var(--accent-color) !important; 
            font-weight: 600; 
            padding: 15px 20px !important;
        }
        
        /* Glass Cards and Overrides */
        .card {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border) !important;
            border-radius: 16px !important;
            box-shadow: var(--glass-shadow) !important;
            color: var(--text-main) !important;
        }

        .card-body {
            color: var(--text-main) !important;
        }

        /* Buttons styling */
        .btn-luxury { 
            background-color: var(--accent-color) !important; 
            color: #000000 !important; 
            border: 1px solid var(--accent-color) !important; 
            font-weight: 600; 
            border-radius: 8px !important;
            padding: 10px 20px;
            transition: all 0.3s ease; 
        }
        .btn-luxury:hover { 
            background-color: transparent !important; 
            color: var(--accent-color) !important; 
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.3);
        }
        .btn-outline-luxury { 
            background-color: transparent !important; 
            color: var(--accent-color) !important; 
            border: 1px solid var(--accent-color) !important; 
            font-weight: 600; 
            border-radius: 8px !important;
            padding: 10px 20px;
            transition: all 0.3s ease; 
        }
        .btn-outline-luxury:hover { 
            background-color: var(--accent-color) !important; 
            color: #000000 !important; 
        }

        /* Admin Form Inputs */
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            border-radius: 8px !important;
            padding: 10px 15px !important;
            transition: all 0.3s ease;
        }
        .form-select option, select option {
            background-color: #16151f !important;
            color: #ffffff !important;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.05) !important;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.2) !important;
            border-color: var(--accent-color) !important;
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4) !important;
        }

        /* Tables Override */
        .table {
            color: var(--text-main) !important;
            border-color: rgba(255, 255, 255, 0.06) !important;
        }
        .table th, .table td {
            background: transparent !important;
            color: var(--text-main) !important;
            border-color: rgba(255, 255, 255, 0.06) !important;
            padding: 14px !important;
        }
        .table thead th {
            color: #fff !important;
            font-weight: 600;
        }
        .table-hover tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02) !important;
            color: #fff !important;
        }
        .table-light {
            background-color: rgba(255, 255, 255, 0.02) !important;
        }
        
        /* Badges Override */
        .badge {
            padding: 6px 12px !important;
            font-weight: 500 !important;
            border-radius: 6px !important;
        }
        .badge-pending { background-color: rgba(255, 193, 7, 0.15) !important; color: #ffc107 !important; border: 1px solid rgba(255, 193, 7, 0.3); }
        .badge-processing { background-color: rgba(23, 162, 184, 0.15) !important; color: #17a2b8 !important; border: 1px solid rgba(23, 162, 184, 0.3); }
        .badge-shipped { background-color: rgba(0, 123, 255, 0.15) !important; color: #007bff !important; border: 1px solid rgba(0, 123, 255, 0.3); }
        .badge-delivered { background-color: rgba(40, 167, 69, 0.15) !important; color: #28a745 !important; border: 1px solid rgba(40, 167, 69, 0.3); }
        .badge-cancelled { background-color: rgba(220, 53, 69, 0.15) !important; color: #dc3545 !important; border: 1px solid rgba(220, 53, 69, 0.3); }
        
        .badge-active { background-color: rgba(40, 167, 69, 0.15) !important; color: #28a745 !important; border: 1px solid rgba(40, 167, 69, 0.3); }
        .badge-inactive { background-color: rgba(108, 117, 125, 0.15) !important; color: #6c757d !important; border: 1px solid rgba(108, 117, 125, 0.3); }
        .badge-banned { background-color: rgba(220, 53, 69, 0.15) !important; color: #dc3545 !important; border: 1px solid rgba(220, 53, 69, 0.3); }

        .text-dark { color: #fff !important; }
        .text-muted { color: var(--text-muted) !important; }
        .bg-light { background-color: rgba(255, 255, 255, 0.02) !important; }
        .img-thumbnail { background-color: rgba(255,255,255,0.05) !important; border-color: rgba(255,255,255,0.1) !important; }

        /* Custom alerts inside admin panel */
        .alert {
            background: rgba(255, 255, 255, 0.04) !important;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.06) !important;
            color: var(--text-main) !important;
            border-radius: 12px !important;
        }
        .alert-danger { border-left: 4px solid #dc3545 !important; }
        .alert-success { border-left: 4px solid #28a745 !important; }

        /* Fix scrollbar layout for premium look */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar flex-shrink-0">
        <div class="sidebar-brand">
            Velvet Vogue<br><span style="font-size: 0.9rem; color: #fff;">ADMIN</span>
        </div>
        <ul class="list-unstyled">
            <li><a href="index.php" class="<?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li><a href="orders.php" class="<?php echo ($active_page ?? '') === 'orders' ? 'active' : ''; ?>"><i class="fas fa-shopping-bag me-2"></i> Orders</a></li>
            <li><a href="products.php" class="<?php echo ($active_page ?? '') === 'products' ? 'active' : ''; ?>"><i class="fas fa-tshirt me-2"></i> Products</a></li>
            <li><a href="categories.php" class="<?php echo ($active_page ?? '') === 'categories' ? 'active' : ''; ?>"><i class="fas fa-tags me-2"></i> Categories</a></li>
            <li><a href="customers.php" class="<?php echo ($active_page ?? '') === 'customers' ? 'active' : ''; ?>"><i class="fas fa-users me-2"></i> Customers</a></li>
            <li><a href="reviews.php" class="<?php echo ($active_page ?? '') === 'reviews' ? 'active' : ''; ?>"><i class="fas fa-star me-2"></i> Reviews</a></li>
            <li><a href="messages.php" class="<?php echo ($active_page ?? '') === 'messages' ? 'active' : ''; ?>"><i class="fas fa-envelope me-2"></i> Messages</a></li>
            <li><a href="logout.php" class="text-danger mt-5"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Nav -->
        <div class="top-nav">
            <h4 class="mb-0"><?php echo htmlspecialchars($page_title ?? 'Dashboard Overview'); ?></h4>
            <div>
                <span class="me-3">Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></strong></span>
                <i class="fas fa-user-circle fs-4 text-secondary"></i>
            </div>
        </div>
        
        <!-- Flash Messages -->
        <?php display_flash_messages(); ?>
