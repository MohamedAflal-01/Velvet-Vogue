<?php
$active_page = 'products';
$page_title = 'Edit Product';
require_once 'includes/header.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product details
$stmt = $conn->prepare("
    SELECT p.*, pi.image_url 
    FROM products p 
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1 
    WHERE p.id = :id LIMIT 1
");
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash_message('error', 'Product not found.');
    redirect('products.php');
}

// Fetch categories for select
$cat_stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input
    $name = sanitize_input($_POST['name']);
    $slug = sanitize_input($_POST['slug']);
    $description = sanitize_input($_POST['description']);
    $price = floatval($_POST['price']);
    $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $sku = sanitize_input($_POST['sku']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $brand = sanitize_input($_POST['brand']);
    $gender = sanitize_input($_POST['gender']);
    $category_id = intval($_POST['category_id']);
    $status = sanitize_input($_POST['status']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_new = isset($_POST['is_new']) ? 1 : 0;

    // Initialize image_url with existing database value
    $image_url = $product['image_url'];

    // Handle File Upload if a new file was selected
    $errors = [];
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['product_image']['tmp_name'];
        $fileName = $_FILES['product_image']['name'];
        $fileSize = $_FILES['product_image']['size'];
        $fileType = $_FILES['product_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '', $fileName);
            $uploadFileDir = __DIR__ . '/../assets/uploads/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $image_url = 'assets/uploads/' . $newFileName;
            } else {
                $errors[] = 'Error moving the uploaded image to the server directory.';
            }
        } else {
            $errors[] = 'Upload failed. Allowed image types: ' . implode(', ', $allowedfileExtensions);
        }
    }

    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }



    // Simple validation
    if (empty($name)) $errors[] = "Product name is required.";
    if (empty($sku)) $errors[] = "Product SKU is required.";
    if ($price <= 0) $errors[] = "Product price must be greater than 0.";
    if ($category_id <= 0) $errors[] = "Please select a valid category.";

    // Check if SKU exists in another product
    $sku_stmt = $conn->prepare("SELECT id FROM products WHERE sku = :sku AND id != :id LIMIT 1");
    $sku_stmt->execute([':sku' => $sku, ':id' => $product_id]);
    if ($sku_stmt->fetch()) {
        $errors[] = "SKU already exists on another product.";
    }

    // Check if Slug exists in another product
    $slug_stmt = $conn->prepare("SELECT id FROM products WHERE slug = :slug AND id != :id LIMIT 1");
    $slug_stmt->execute([':slug' => $slug, ':id' => $product_id]);
    if ($slug_stmt->fetch()) {
        $errors[] = "Slug already exists on another product. Please choose a unique name or slug.";
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            $update_query = "UPDATE products SET 
                                category_id = :category_id, 
                                name = :name, 
                                slug = :slug, 
                                description = :description, 
                                price = :price, 
                                sale_price = :sale_price, 
                                sku = :sku, 
                                stock_quantity = :stock_quantity, 
                                brand = :brand, 
                                gender = :gender, 
                                is_featured = :is_featured, 
                                is_new = :is_new, 
                                status = :status
                             WHERE id = :id";
            
            $stmt = $conn->prepare($update_query);
            $stmt->execute([
                ':category_id' => $category_id,
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description,
                ':price' => $price,
                ':sale_price' => $sale_price,
                ':sku' => $sku,
                ':stock_quantity' => $stock_quantity,
                ':brand' => $brand,
                ':gender' => $gender,
                ':is_featured' => $is_featured,
                ':is_new' => $is_new,
                ':status' => $status,
                ':id' => $product_id
            ]);

            // Handle primary image update
            $img_check = $conn->prepare("SELECT id FROM product_images WHERE product_id = :product_id AND is_primary = 1 LIMIT 1");
            $img_check->execute([':product_id' => $product_id]);
            $img_row = $img_check->fetch();

            if ($img_row) {
                if (empty($image_url)) {
                    $del_img = $conn->prepare("DELETE FROM product_images WHERE id = :id");
                    $del_img->execute([':id' => $img_row['id']]);
                } else {
                    $up_img = $conn->prepare("UPDATE product_images SET image_url = :image_url WHERE id = :id");
                    $up_img->execute([':image_url' => $image_url, ':id' => $img_row['id']]);
                }
            } else {
                if (!empty($image_url)) {
                    $ins_img = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (:product_id, :image_url, 1)");
                    $ins_img->execute([':product_id' => $product_id, ':image_url' => $image_url]);
                }
            }

            // Log activity
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
            $log_stmt->execute([
                ':admin_id' => $_SESSION['admin_id'],
                ':action' => "Edited product '{$name}' (ID: {$product_id})"
            ]);

            $conn->commit();
            set_flash_message('success', 'Product updated successfully.');
            redirect('products.php');
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="row mb-3">
    <div class="col-12">
        <a href="products.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Products</a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header card-header-custom">
        <i class="fas fa-edit me-2"></i> Edit Product: <?php echo htmlspecialchars($product['name']); ?>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="product_edit.php?id=<?php echo $product['id']; ?>" enctype="multipart/form-data" class="row g-3">
            <!-- Product Name -->
            <div class="col-md-6">
                <label for="name" class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" required value="<?php echo htmlspecialchars($product['name']); ?>">
            </div>

            <!-- Custom Slug -->
            <div class="col-md-6">
                <label for="slug" class="form-label fw-semibold">URL Slug</label>
                <input type="text" name="slug" id="slug" class="form-control" value="<?php echo htmlspecialchars($product['slug']); ?>">
            </div>

            <!-- Category -->
            <div class="col-md-4">
                <label for="category_id" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                <select name="category_id" id="category_id" class="form-select" required>
                    <option value="">Choose Category...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (intval($product['category_id']) === intval($cat['id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- SKU -->
            <div class="col-md-4">
                <label for="sku" class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
                <input type="text" name="sku" id="sku" class="form-control" required value="<?php echo htmlspecialchars($product['sku']); ?>">
            </div>

            <!-- Stock Quantity -->
            <div class="col-md-4">
                <label for="stock_quantity" class="form-label fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
                <input type="number" name="stock_quantity" id="stock_quantity" class="form-control" min="0" required value="<?php echo intval($product['stock_quantity']); ?>">
            </div>

            <!-- Price -->
            <div class="col-md-4">
                <label for="price" class="form-label fw-semibold">Regular Price (LKR) <span class="text-danger">*</span></label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" min="0" required value="<?php echo htmlspecialchars($product['price']); ?>">
            </div>

            <!-- Sale Price -->
            <div class="col-md-4">
                <label for="sale_price" class="form-label fw-semibold">Sale Price (LKR)</label>
                <input type="number" name="sale_price" id="sale_price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($product['sale_price'] ?? ''); ?>">
            </div>

            <!-- Brand -->
            <div class="col-md-4">
                <label for="brand" class="form-label fw-semibold">Brand</label>
                <input type="text" name="brand" id="brand" class="form-control" value="<?php echo htmlspecialchars($product['brand']); ?>">
            </div>

            <!-- Gender -->
            <div class="col-md-4">
                <label for="gender" class="form-label fw-semibold">Gender target</label>
                <select name="gender" id="gender" class="form-select">
                    <option value="Unisex" <?php echo ($product['gender'] === 'Unisex') ? 'selected' : ''; ?>>Unisex</option>
                    <option value="Men" <?php echo ($product['gender'] === 'Men') ? 'selected' : ''; ?>>Men</option>
                    <option value="Women" <?php echo ($product['gender'] === 'Women') ? 'selected' : ''; ?>>Women</option>
                    <option value="Kids" <?php echo ($product['gender'] === 'Kids') ? 'selected' : ''; ?>>Kids</option>
                </select>
            </div>

            <!-- Status -->
            <div class="col-md-4">
                <label for="status" class="form-label fw-semibold">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="active" <?php echo ($product['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($product['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="draft" <?php echo ($product['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>

            <!-- Product Image Upload -->
            <div class="col-md-4">
                <label for="product_image" class="form-label fw-semibold">Product Image <span class="text-secondary small">(Leave empty to keep current)</span></label>
                <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*">
                <?php if (!empty($product['image_url'])): ?>
                    <div class="form-text">
                        Current Image: 
                        <?php 
                        $img_preview = filter_var($product['image_url'], FILTER_VALIDATE_URL) ? $product['image_url'] : '../' . $product['image_url']; 
                        ?>
                        <a href="<?php echo htmlspecialchars($img_preview); ?>" target="_blank" class="text-luxury fw-semibold text-decoration-none">View Current Image</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="col-12">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <!-- Switches -->
            <div class="col-12 d-flex gap-4 my-2">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_featured" id="is_featured" <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="is_featured">Featured Product</label>
                </div>
                
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_new" id="is_new" <?php echo $product['is_new'] ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="is_new">New Arrival</label>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-luxury px-4 py-2">Update Product</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
