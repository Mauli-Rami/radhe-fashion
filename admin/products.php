<?php
/**
 * Radhe Fashion — Product Management (List & Actions)
 * File: admin/products.php
 */

declare(strict_types=1);

$pageTitle = 'Manage Products';
require_once __DIR__ . '/header.php';

$pdo = get_db_connection();

// Handle State-Changing POST Actions (Toggle status, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'toggle_active' && $productId > 0) {
        $stmt = $pdo->prepare('UPDATE products SET is_active = NOT is_active WHERE id = ?');
        $stmt->execute([$productId]);
        $_SESSION['flash_success'] = 'Product visibility status updated successfully.';
        header('Location: products.php');
        exit;
    }

    if ($action === 'delete' && $productId > 0) {
        // Fetch image path to safely delete image if not used elsewhere
        $stmt = $pdo->prepare('SELECT image_path FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $imagePath = $stmt->fetchColumn();

        $delStmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $delStmt->execute([$productId]);

        if ($imagePath) {
            safely_delete_product_image($pdo, (string)$imagePath, $productId);
        }

        $_SESSION['flash_success'] = 'Product has been permanently deleted.';
        header('Location: products.php');
        exit;
    }
}

// Search and Filter parameters
$search = trim((string)($_GET['q'] ?? ''));
$filterCategory = (int)($_GET['category'] ?? 0);
$filterStatus = $_GET['status'] ?? '';

// Build dynamic WHERE clause with prepared statement parameters
$whereConditions = [];
$queryParams = [];

if ($search !== '') {
    $whereConditions[] = '(p.name LIKE :search OR p.product_code LIKE :search OR p.description LIKE :search)';
    $queryParams[':search'] = '%' . $search . '%';
}

if ($filterCategory > 0) {
    $whereConditions[] = 'p.category_id = :cat_id';
    $queryParams[':cat_id'] = $filterCategory;
}

if ($filterStatus === 'active') {
    $whereConditions[] = 'p.is_active = 1';
} elseif ($filterStatus === 'inactive') {
    $whereConditions[] = 'p.is_active = 0';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Fetch all categories for filter dropdown
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY display_order ASC, name ASC')->fetchAll();

// Fetch products
$sql = "
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    {$whereClause}
    ORDER BY p.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($queryParams);
$products = $stmt->fetchAll();
?>

<!-- Action Bar -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
  <form method="GET" action="products.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap; flex-grow: 1; max-width: 720px;">
    <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Search product name or code..." class="form-input" style="max-width: 260px;">
    
    <select name="category" class="form-select" style="max-width: 200px;">
      <option value="0">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?php echo (int)$cat['id']; ?>" <?php echo $filterCategory === (int)$cat['id'] ? 'selected' : ''; ?>>
          <?php echo e($cat['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="status" class="form-select" style="max-width: 140px;">
      <option value="">All Status</option>
      <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active Only</option>
      <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Hidden Only</option>
    </select>

    <button type="submit" class="btn-admin btn-admin-outline">Filter</button>
    <?php if ($search !== '' || $filterCategory > 0 || $filterStatus !== ''): ?>
      <a href="products.php" class="btn-admin btn-admin-outline" style="color: var(--admin-text-muted);">Reset</a>
    <?php endif; ?>
  </form>

  <a href="product-form.php" class="btn-admin btn-admin-primary">
    &#43; Add New Kurti / Product
  </a>
</div>

<!-- Products Table Card -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Products List (<?php echo count($products); ?> items found)</h3>
  </div>
  <div class="card-body" style="padding: 0;">
    <?php if (empty($products)): ?>
      <div style="padding: 3rem 1.5rem; text-align: center; color: var(--admin-text-muted);">
        <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">No products match your criteria.</p>
        <p style="font-size: 0.88rem;">Try clearing your search filters or click &ldquo;Add New Kurti / Product&rdquo; to create one.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 60px;">Photo</th>
              <th>Code</th>
              <th>Product Details</th>
              <th>Category</th>
              <th>Price Display</th>
              <th>Stock</th>
              <th>Status</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $prod): ?>
              <tr>
                <td>
                  <?php 
                    $imgSrc = !empty($prod['image_path']) ? '../' . $prod['image_path'] : '../' . FALLBACK_IMAGE;
                  ?>
                  <img src="<?php echo e($imgSrc); ?>" alt="<?php echo e($prod['name']); ?>" class="table-thumb">
                </td>
                <td>
                  <code style="font-size: 0.85rem; background: #EFEFEF; padding: 2px 6px; border-radius: 3px;">
                    <?php echo e($prod['product_code'] ?: '—'); ?>
                  </code>
                </td>
                <td>
                  <strong style="font-size: 0.95rem; color: var(--admin-text-main); display: block;">
                    <?php echo e($prod['name']); ?>
                  </strong>
                  <span style="font-size: 0.8rem; color: var(--admin-text-muted); display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                    <?php echo e($prod['description'] ?: 'No description'); ?>
                  </span>
                </td>
                <td><?php echo e($prod['category_name'] ?: 'Unassigned'); ?></td>
                <td>
                  <?php if ($prod['price'] !== null): ?>
                    <strong>₹<?php echo number_format((float)$prod['price'], 2); ?></strong>
                    <div style="font-size: 0.72rem; color: <?php echo $prod['show_price'] ? 'var(--admin-success)' : 'var(--admin-text-muted)'; ?>;">
                      <?php echo $prod['show_price'] ? '&#10003; Visible on site' : '&#10005; Hidden on site'; ?>
                    </div>
                  <?php else: ?>
                    <span style="color: var(--admin-text-muted);">Inquire for price</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php 
                    $availLabels = [
                      'in_stock' => 'In Stock',
                      'made_to_order' => 'Made to Order',
                      'out_of_stock' => 'Out of Stock'
                    ];
                    echo e($availLabels[$prod['availability_status']] ?? 'In Stock');
                  ?>
                </td>
                <td>
                  <!-- Quick Toggle Active Status -->
                  <form method="POST" action="products.php" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="product_id" value="<?php echo (int)$prod['id']; ?>">
                    <button type="submit" class="badge <?php echo $prod['is_active'] ? 'badge-active' : 'badge-inactive'; ?>" 
                            style="cursor: pointer; border: none;" title="Click to toggle active status">
                      <?php echo $prod['is_active'] ? 'Active' : 'Hidden'; ?>
                    </button>
                  </form>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <a href="product-form.php?id=<?php echo (int)$prod['id']; ?>" class="btn-admin btn-admin-outline btn-sm">
                    Edit
                  </a>
                  
                  <!-- Safe Delete Form with Confirmation -->
                  <form method="POST" action="products.php" style="display: inline;" 
                        onsubmit="return confirm('Are you sure you want to permanently delete \'<?php echo addslashes($prod['name']); ?>\'?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="product_id" value="<?php echo (int)$prod['id']; ?>">
                    <button type="submit" class="btn-admin btn-admin-danger btn-sm" style="margin-left: 4px;">
                      Delete
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
