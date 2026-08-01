<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | Velvet Vogue' : 'Velvet Vogue | Luxury Fashion'; ?></title>
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
    
    <!-- Custom Theme Colors & Design Requirements (Glassmorphism etc) handled in style.css -->
</head>
<body>
<?php include __DIR__ . '/../components/navbar.php'; ?>
<!-- Display any global flash messages -->
<div class="container mt-3">
    <?php display_flash_messages(); ?>
</div>
