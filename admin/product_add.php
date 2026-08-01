<?php
$active_page = 'products';
$page_title = 'Add New Product';
require_once 'includes/header.php';

// Fetch categories for the select field
$cat_stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
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

    // Generate slug from name if empty
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    $errors = [];
    $image_url = '';

    // Handle File Upload
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
    } else {
        $errors[] = 'Product image is required.';
    }

    // Simple validation
    if (empty($name)) $errors[] = "Product name is required.";
    if (empty($sku)) $errors[] = "Product SKU is required.";
    if ($price <= 0) $errors[] = "Product price must be greater than 0.";
    if ($category_id <= 0) $errors[] = "Please select a valid category.";

    // Check if SKU already exists
    $sku_stmt = $conn->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
    $sku_stmt->execute([':sku' => $sku]);
    if ($sku_stmt->fetch()) {
        $errors[] = "SKU already exists.";
    }

    // Check if Slug already exists
    $slug_stmt = $conn->prepare("SELECT id FROM products WHERE slug = :slug LIMIT 1");
    $slug_stmt->execute([':slug' => $slug]);
    if ($slug_stmt->fetch()) {
        $errors[] = "Slug already exists. Please choose a unique name or custom slug.";
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            $insert_query = "INSERT INTO products (category_id, name, slug, description, price, sale_price, sku, stock_quantity, brand, gender, is_featured, is_new, status) 
                             VALUES (:category_id, :name, :slug, :description, :price, :sale_price, :sku, :stock_quantity, :brand, :gender, :is_featured, :is_new, :status)";
            
            $stmt = $conn->prepare($insert_query);
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
                ':status' => $status
            ]);
            
            $product_id = $conn->lastInsertId();

            // Insert primary image if provided
            if (!empty($image_url)) {
                $img_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (:product_id, :image_url, 1)");
                $img_stmt->execute([
                    ':product_id' => $product_id,
                    ':image_url' => $image_url
                ]);
            }

            // Log activity
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
            $log_stmt->execute([
                ':admin_id' => $_SESSION['admin_id'],
                ':action' => "Created product '{$name}' (SKU: {$sku})"
            ]);

            $conn->commit();
            set_flash_message('success', 'Product added successfully.');
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
        <i class="fas fa-plus-circle me-2"></i> Add New Product
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

        <form method="POST" action="product_add.php" enctype="multipart/form-data" class="row g-3">
            <!-- Product Name -->
            <div class="col-md-6">
                <label for="name" class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="e.g., Slim Fit Cotton Shirt" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <!-- Custom Slug (Optional) -->
            <div class="col-md-6">
                <label for="slug" class="form-label fw-semibold">URL Slug <span class="text-secondary small">(Leave empty to auto-generate)</span></label>
                <input type="text" name="slug" id="slug" class="form-control" placeholder="e.g., slim-fit-cotton-shirt" value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>">
            </div>

            <!-- Category -->
            <div class="col-md-4">
                <label for="category_id" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                <select name="category_id" id="category_id" class="form-select" required>
                    <option value="">Choose Category...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && intval($_POST['category_id']) === intval($cat['id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- SKU -->
            <div class="col-md-4">
                <label for="sku" class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
                <input type="text" name="sku" id="sku" class="form-control" placeholder="e.g., VV-SH-001" required value="<?php echo isset($_POST['sku']) ? htmlspecialchars($_POST['sku']) : ''; ?>">
            </div>

            <!-- Stock Quantity -->
            <div class="col-md-4">
                <label for="stock_quantity" class="form-label fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
                <input type="number" name="stock_quantity" id="stock_quantity" class="form-control" min="0" required value="<?php echo isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : '0'; ?>">
            </div>

            <!-- Price -->
            <div class="col-md-4">
                <label for="price" class="form-label fw-semibold">Regular Price (LKR) <span class="text-danger">*</span></label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" min="0" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
            </div>

            <!-- Sale Price -->
            <div class="col-md-4">
                <label for="sale_price" class="form-label fw-semibold">Sale Price (LKR) <span class="text-secondary small">(Optional)</span></label>
                <input type="number" name="sale_price" id="sale_price" class="form-control" step="0.01" min="0" value="<?php echo isset($_POST['sale_price']) ? htmlspecialchars($_POST['sale_price']) : ''; ?>">
            </div>

            <!-- Brand -->
            <div class="col-md-4">
                <label for="brand" class="form-label fw-semibold">Brand</label>
                <input type="text" name="brand" id="brand" class="form-control" placeholder="e.g., Velvet Vogue" value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : 'Velvet Vogue'; ?>">
            </div>

            <!-- Gender Filter -->
            <div class="col-md-4">
                <label for="gender" class="form-label fw-semibold">Gender target</label>
                <select name="gender" id="gender" class="form-select">
                    <option value="Unisex" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Unisex') ? 'selected' : ''; ?>>Unisex</option>
                    <option value="Men" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Men') ? 'selected' : ''; ?>>Men</option>
                    <option value="Women" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Women') ? 'selected' : ''; ?>>Women</option>
                    <option value="Kids" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Kids') ? 'selected' : ''; ?>>Kids</option>
                </select>
            </div>

            <!-- Status -->
            <div class="col-md-4">
                <label for="status" class="form-label fw-semibold">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>

            <!-- Product Image Upload -->
            <div class="col-md-4">
                <label for="product_image" class="form-label fw-semibold">Product Image <span class="text-danger">*</span></label>
                <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*" required>
            </div>

            <!-- Description -->
            <div class="col-12">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4" placeholder="Enter product description..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <!-- Checkboxes -->
            <div class="col-12 d-flex gap-4 my-2">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_featured" id="is_featured" <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="is_featured">Featured Product</label>
                </div>
                
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_new" id="is_new" checked <?php echo (isset($_POST['is_new']) || !isset($_POST['name'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="is_new">New Arrival</label>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-luxury px-4 py-2">Create Product</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
