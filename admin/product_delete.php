<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';


// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($product_id > 0) {
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            // Check if product exists
            $stmt = $conn->prepare("SELECT name, sku FROM products WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $product_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                // Check if this product is part of any orders (restricted constraint)
                $check_ordered = $conn->prepare("SELECT id FROM order_items WHERE product_id = :product_id LIMIT 1");
                $check_ordered->execute([':product_id' => $product_id]);
                
                if ($check_ordered->fetch()) {
                    // Cannot delete because it's referenced in order items, set status to inactive instead
                    $deactivate = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id = :id");
                    $deactivate->execute([':id' => $product_id]);
                    
                    // Log activity
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
                    $log_stmt->execute([
                        ':admin_id' => $_SESSION['admin_id'],
                        ':action' => "Deactivated product '{$product['name']}' because it has historical orders"
                    ]);
                    
                    set_flash_message('warning', 'Product cannot be permanently deleted because it has historical customer orders. Its status has been set to "inactive" instead.');
                } else {
                    // Safe to delete (images and variants will cascade delete)
                    $delete = $conn->prepare("DELETE FROM products WHERE id = :id");
                    $delete->execute([':id' => $product_id]);
                    
                    // Log activity
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
                    $log_stmt->execute([
                        ':admin_id' => $_SESSION['admin_id'],
                        ':action' => "Deleted product '{$product['name']}' (SKU: {$product['sku']})"
                    ]);
                    
                    set_flash_message('success', 'Product deleted successfully.');
                }
            } else {
                set_flash_message('error', 'Product not found.');
            }
        } catch (Exception $e) {
            set_flash_message('error', 'Error deleting product: ' . $e->getMessage());
        }
    }
}

redirect('products.php');
?>
