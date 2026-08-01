<?php
$active_page = 'products';
$page_title = 'Manage Products';
require_once 'includes/header.php';

// Filtering and Search parameters
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Fetch all categories for dropdown filter
$cat_stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll();

// Build SQL query
$sql = "SELECT p.*, c.name as category_name, pi.image_url 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1";

$where_clauses = [];
$params = [];

if ($category_filter > 0) {
    $where_clauses[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_filter;
}

if (!empty($search_query)) {
    $where_clauses[] = "(p.name LIKE :search OR p.sku LIKE :search OR p.brand LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!-- Action Buttons & Search Control Panel -->
<div class="row g-3 mb-4">
    <div class="col-md-9">
        <div class="card shadow-sm border-0">
            <div class="card-body py-3">
                <form method="GET" action="products.php" class="row g-3 align-items-center mb-0">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search by Name, SKU, Brand..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter === intval($cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-luxury w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <a href="product_add.php" class="btn btn-luxury w-100 h-100 d-flex align-items-center justify-content-center py-2">
            <i class="fas fa-plus-circle me-2"></i> Add New Product
        </a>
    </div>
</div>

<!-- Products Table -->
<div class="card shadow-sm border-0">
    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
        <span class="fs-5"><i class="fas fa-tshirt me-2"></i> Products List (<?php echo count($products); ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 80px;">Image</th>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Featured</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-box-open fs-2 mb-2 d-block"></i>
                                No products found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $prod): ?>
                            <tr>
                                <td class="ps-3">
                                    <?php 
                                    if (!empty($prod['image_url'])) {
                                        if (filter_var($prod['image_url'], FILTER_VALIDATE_URL)) {
                                            $img_src = $prod['image_url'];
                                        } else {
                                            $img_src = '../' . $prod['image_url'];
                                        }
                                    } else {
                                        $img_src = '../assets/images/placeholder.jpg';
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Product" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td class="fw-semibold text-secondary small"><?php echo htmlspecialchars($prod['sku']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prod['name']); ?></strong>
                                    <?php if (!empty($prod['brand'])): ?>
                                        <span class="badge bg-light text-dark ms-1"><?php echo htmlspecialchars($prod['brand']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($prod['category_name'] ?: 'None'); ?></td>
                                <td>
                                    <?php if ($prod['sale_price'] > 0 && $prod['sale_price'] < $prod['price']): ?>
                                        <span class="text-danger fw-semibold"><?php echo format_price($prod['sale_price']); ?></span>
                                        <del class="text-muted small ms-1"><?php echo format_price($prod['price']); ?></del>
                                    <?php else: ?>
                                        <span class="fw-semibold"><?php echo format_price($prod['price']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($prod['stock_quantity'] <= 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php elseif ($prod['stock_quantity'] <= 10): ?>
                                        <span class="badge bg-warning text-dark"><?php echo $prod['stock_quantity']; ?> Low</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?php echo $prod['stock_quantity']; ?> pcs</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($prod['is_featured']): ?>
                                        <span class="badge bg-primary"><i class="fas fa-star text-warning"></i> Featured</span>
                                    <?php else: ?>
                                        <span class="text-muted small">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'bg-secondary';
                                    if ($prod['status'] === 'active') $status_class = 'bg-success';
                                    elseif ($prod['status'] === 'inactive') $status_class = 'bg-danger';
                                    elseif ($prod['status'] === 'draft') $status_class = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?> text-uppercase"><?php echo $prod['status']; ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="product_edit.php?id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-dark">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $prod['id']; ?>, '<?php echo addslashes($prod['name']); ?>')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="deleteForm" method="POST" action="product_delete.php">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="deleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Confirm Delete</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the product <strong id="deleteProductName"></strong>?</p>
          <p class="text-danger small mb-0"><i class="fas fa-info-circle me-1"></i> This action cannot be undone and will remove all variants and images associated with this product.</p>
          <input type="hidden" name="product_id" id="deleteProductId">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete Product</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteProductId').value = id;
    document.getElementById('deleteProductName').innerText = name;
    var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    myModal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
