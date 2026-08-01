<?php
$active_page = 'categories';
$page_title = 'Manage Categories';
require_once 'includes/header.php';

// Handle Add Category
if (isset($_POST['add_category'])) {
    $name = sanitize_input($_POST['name']);
    $slug = sanitize_input($_POST['slug']);
    $description = sanitize_input($_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    $errors = [];
    if (empty($name)) $errors[] = "Category name is required.";

    // Check slug uniqueness
    $slug_stmt = $conn->prepare("SELECT id FROM categories WHERE slug = :slug LIMIT 1");
    $slug_stmt->execute([':slug' => $slug]);
    if ($slug_stmt->fetch()) {
        $errors[] = "Slug already exists. Please choose a unique name or slug.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, parent_id, status) VALUES (:name, :slug, :description, :parent_id, :status)");
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':description' => $description,
            ':parent_id' => $parent_id,
            ':status' => $status
        ]);
        
        // Log activity
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
        $log_stmt->execute([
            ':admin_id' => $_SESSION['admin_id'],
            ':action' => "Created category '{$name}'"
        ]);

        set_flash_message('success', 'Category created successfully.');
        redirect('categories.php');
    }
}

// Handle Edit Category
if (isset($_POST['edit_category'])) {
    $id = intval($_POST['category_id']);
    $name = sanitize_input($_POST['name']);
    $slug = sanitize_input($_POST['slug']);
    $description = sanitize_input($_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    $errors = [];
    if (empty($name)) $errors[] = "Category name is required.";

    // Check slug uniqueness excluding current category
    $slug_stmt = $conn->prepare("SELECT id FROM categories WHERE slug = :slug AND id != :id LIMIT 1");
    $slug_stmt->execute([':slug' => $slug, ':id' => $id]);
    if ($slug_stmt->fetch()) {
        $errors[] = "Slug already exists on another category.";
    }

    // Prevent making a category its own parent
    if ($parent_id === $id) {
        $errors[] = "A category cannot be its own parent.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE categories SET name = :name, slug = :slug, description = :description, parent_id = :parent_id, status = :status WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':description' => $description,
            ':parent_id' => $parent_id,
            ':status' => $status,
            ':id' => $id
        ]);

        // Log activity
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
        $log_stmt->execute([
            ':admin_id' => $_SESSION['admin_id'],
            ':action' => "Edited category '{$name}' (ID: {$id})"
        ]);

        set_flash_message('success', 'Category updated successfully.');
        redirect('categories.php');
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Check if category exists
    $cat_check = $conn->prepare("SELECT name FROM categories WHERE id = :id LIMIT 1");
    $cat_check->execute([':id' => $delete_id]);
    $cat = $cat_check->fetch();
    
    if ($cat) {
        // Check if there are any products in this category
        $prod_check = $conn->prepare("SELECT id FROM products WHERE category_id = :category_id LIMIT 1");
        $prod_check->execute([':category_id' => $delete_id]);
        
        if ($prod_check->fetch()) {
            set_flash_message('error', 'Category "' . htmlspecialchars($cat['name']) . '" cannot be deleted because it contains products. Move the products to another category first.');
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute([':id' => $delete_id]);
            
            // Log activity
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (:admin_id, 'admin', :action)");
            $log_stmt->execute([
                ':admin_id' => $_SESSION['admin_id'],
                ':action' => "Deleted category '{$cat['name']}'"
            ]);
            
            set_flash_message('success', 'Category deleted successfully.');
        }
    }
    redirect('categories.php');
}

// Fetch Category for Editing (if active)
$edit_mode = false;
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
    $edit_stmt->execute([':id' => $edit_id]);
    $edit_category = $edit_stmt->fetch();
    if ($edit_category) {
        $edit_mode = true;
    }
}

// Fetch all categories with parent names
$stmt = $conn->query("
    SELECT c.*, p.name as parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY c.name ASC
");
$all_categories = $stmt->fetchAll();
?>

<div class="row">
    <!-- Categories List (Left Side) -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header card-header-custom">
                <i class="fas fa-tags me-2"></i> Categories List
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 50px;">ID</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Parent</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_categories)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No categories found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_categories as $c): ?>
                                    <tr>
                                        <td class="ps-3 text-secondary"><?php echo $c['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($c['name']); ?></strong>
                                            <?php if (!empty($c['description'])): ?>
                                                <small class="text-muted d-block text-truncate" style="max-width: 250px;">
                                                    <?php echo htmlspecialchars($c['description']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small font-monospace"><?php echo htmlspecialchars($c['slug']); ?></td>
                                        <td><?php echo htmlspecialchars($c['parent_name'] ?: 'None'); ?></td>
                                        <td>
                                            <?php if ($c['status']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="categories.php?edit=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-dark">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="categories.php?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this category? Note: It must contain no products.')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Form (Right Side) -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header card-header-custom">
                <i class="fas <?php echo $edit_mode ? 'fa-edit' : 'fa-plus-circle'; ?> me-2"></i> 
                <?php echo $edit_mode ? 'Edit Category' : 'Add Category'; ?>
            </div>
            <div class="card-body">
                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="categories.php">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="e.g., Summer Collection" 
                               value="<?php echo $edit_mode ? htmlspecialchars($edit_category['name']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label fw-semibold">URL Slug <span class="text-secondary small">(Optional)</span></label>
                        <input type="text" name="slug" id="slug" class="form-control" placeholder="e.g., summer-collection" 
                               value="<?php echo $edit_mode ? htmlspecialchars($edit_category['slug']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label fw-semibold">Parent Category</label>
                        <select name="parent_id" id="parent_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($all_categories as $c): ?>
                                <!-- Exclude self if in edit mode -->
                                <?php if ($edit_mode && $c['id'] == $edit_category['id']) continue; ?>
                                <option value="<?php echo $c['id']; ?>" 
                                    <?php 
                                    if ($edit_mode && $edit_category['parent_id'] == $c['id']) echo 'selected';
                                    ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter short description..."><?php echo $edit_mode ? htmlspecialchars($edit_category['description'] ?? '') : ''; ?></textarea>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="status" id="status" 
                            <?php echo (!$edit_mode || $edit_category['status']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="status">Active Category</label>
                    </div>

                    <div class="d-grid gap-2">
                        <?php if ($edit_mode): ?>
                            <button type="submit" name="edit_category" class="btn btn-luxury">Update Category</button>
                            <a href="categories.php" class="btn btn-outline-secondary">Cancel Edit</a>
                        <?php else: ?>
                            <button type="submit" name="add_category" class="btn btn-luxury">Add Category</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
