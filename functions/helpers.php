<?php
/**
 * Helper functions for Velvet Vogue
 */

// Start session and output buffering
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

// Dynamically determine BASE_URL
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $projectFolder = (strpos($scriptName, '/VelvetVogue/') !== false) ? '/VelvetVogue/' : '/';
    define('BASE_URL', $protocol . $domainName . $projectFolder);
}


/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['customer_id']);
}

/**
 * Redirect function
 */
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $url . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        echo '</noscript>';
        exit();
    }
}

/**
 * Set flash message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

/**
 * Display flash messages
 */
function display_flash_messages() {
    if (isset($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $msg) {
            $alertClass = $msg['type'] === 'error' ? 'alert-danger' : 'alert-success';
            echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($msg['message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
        unset($_SESSION['flash_messages']);
    }
}

/**
 * Get customer wishlist product IDs with static cache
 */
function get_customer_wishlist_ids() {
    if (!is_logged_in()) {
        return [];
    }
    
    static $wishlist_ids = null;
    if ($wishlist_ids !== null) {
        return $wishlist_ids;
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT product_id FROM wishlist WHERE customer_id = :customer_id");
        $stmt->execute([':customer_id' => $_SESSION['customer_id']]);
        $wishlist_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $wishlist_ids = [];
    }
    
    return $wishlist_ids;
}

/**
 * Format price to currency (LKR)
 */
function format_price($price) {
    return 'LKR ' . number_format($price, 2);
}
?>
