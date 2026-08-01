<?php
// Enable error logging but disable display to prevent malformed JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../functions/helpers.php';

// Check authentication
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Please log in to manage your wishlist.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit();
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid product.']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Verify product exists
    $prod_stmt = $conn->prepare("SELECT id, name FROM products WHERE id = :id");
    $prod_stmt->execute([':id' => $product_id]);
    $product = $prod_stmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
        exit();
    }

    $customer_id = $_SESSION['customer_id'];

    // Check if already in wishlist
    $check_stmt = $conn->prepare("SELECT id FROM wishlist WHERE customer_id = :customer_id AND product_id = :product_id");
    $check_stmt->execute([
        ':customer_id' => $customer_id,
        ':product_id' => $product_id
    ]);
    $wishlist_item = $check_stmt->fetch();

    if ($wishlist_item) {
        // Toggle off: remove
        $del_stmt = $conn->prepare("DELETE FROM wishlist WHERE id = :id");
        $del_stmt->execute([':id' => $wishlist_item['id']]);
        echo json_encode([
            'status' => 'removed',
            'message' => $product['name'] . ' removed from wishlist.',
            'product_id' => $product_id
        ]);
    } else {
        // Toggle on: add
        $ins_stmt = $conn->prepare("INSERT INTO wishlist (customer_id, product_id) VALUES (:customer_id, :product_id)");
        $ins_stmt->execute([
            ':customer_id' => $customer_id,
            ':product_id' => $product_id
        ]);
        echo json_encode([
            'status' => 'added',
            'message' => $product['name'] . ' added to wishlist.',
            'product_id' => $product_id
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error. Please try again later.']);
}
?>
