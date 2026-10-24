<?php
/**
 * Radhe Fashion — Admin Dashboard
 * File: admin/index.php
 */

declare(strict_types=1);

$pageTitle = 'Dashboard Overview';
require_once __DIR__ . '/header.php';

try {
    $pdo = get_db_connection();

    // Query statistics
    $totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $activeProducts = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
    $inactiveProducts = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 0')->fetchColumn();
    $totalCategories = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

    // Fetch 5 most recently updated products
    $recentStmt = $pdo->query('
        SELECT p.*, c.name AS category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.updated_at DESC
        LIMIT 5
    ');
    $recentProducts = $recentStmt->fetchAll();

} catch (PDOException $e) {
    $errorMsg = 'Database error: ' . $e->getMessage();
}
?>

<!-- Statistics Overview -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-num"><?php echo $totalProducts ?? 0; ?></div>
    <div class="stat-label">Total Products</div>
  </div>

  <div class="stat-card">
    <div class="stat-num" style="color: var(--admin-success);"><?php echo $activeProducts ?? 0; ?></div>
    <div class="stat-label">Active / Visible</div>
  </div>

  <div class="stat-card">
    <div class="stat-num" style="color: var(--admin-text-muted);"><?php echo $inactiveProducts ?? 0; ?></div>
    <div class="stat-label">Hidden / Draft</div>
  </div>

  <div class="stat-card">
    <div class="stat-num" style="color: var(--admin-gold);"><?php echo $totalCategories ?? 0; ?></div>
    <div class="stat-label">Categories</div>
  </div>
</div>

<!-- Quick Action Shortcuts -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Quick Boutique Actions</h3>
  </div>
  <div class="card-body" style="display: flex; flex-wrap: wrap; gap: 1rem;">
    <a href="product-form.php" class="btn-admin btn-admin-primary">
      &#43; Add New Kurti / Product
    </a>
    <a href="products.php" class="btn-admin btn-admin-outline">
      &#9733; Manage All Products
    </a>
    <a href="categories.php" class="btn-admin btn-admin-outline">
      &#9776; Manage Categories
    </a>
    <a href="settings.php" class="btn-admin btn-admin-outline">
      &#9881; Update Shop Settings
    </a>
    <a href="../index.php" target="_blank" class="btn-admin btn-admin-outline" style="margin-left: auto;">
      &#8599; View Public Storefront
    </a>
  </div>
</div>

<!-- Recently Updated Showcase Items -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Recently Updated Products</h3>
    <a href="products.php" style="font-size: 0.85rem; color: var(--admin-accent); font-weight: 600; text-decoration: none;">
      View All &rarr;
    </a>
  </div>
  <div class="card-body" style="padding: 0;">
    <?php if (empty($recentProducts)): ?>
      <p style="padding: 2rem; color: var(--admin-text-muted); text-align: center;">
        No products added yet. Click &ldquo;Add New Kurti / Product&rdquo; above to add your first item.
      </p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Image</th>
              <th>Code</th>
              <th>Product Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentProducts as $item): ?>
              <tr>
                <td>
                  <?php 
                    $imgSrc = !empty($item['image_path']) ? '../' . $item['image_path'] : '../' . FALLBACK_IMAGE;
                  ?>
                  <img src="<?php echo e($imgSrc); ?>" alt="<?php echo e($item['name']); ?>" class="table-thumb">
                </td>
                <td>
                  <code style="font-size: 0.8rem; background: #EEE; padding: 2px 5px; border-radius: 3px;">
                    <?php echo e($item['product_code'] ?: '—'); ?>
                  </code>
                </td>
                <td>
                  <strong><?php echo e($item['name']); ?></strong>
                </td>
                <td><?php echo e($item['category_name'] ?: 'Unassigned'); ?></td>
                <td>
                  <?php if ($item['price'] !== null): ?>
                    ₹<?php echo number_format((float)$item['price'], 2); ?>
                    <?php if (!$item['show_price']): ?>
                      <span style="font-size: 0.72rem; color: var(--admin-text-muted); display: block;">(Hidden on site)</span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span style="color: var(--admin-text-muted);">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($item['is_active']): ?>
                    <span class="badge badge-active">Active</span>
                  <?php else: ?>
                    <span class="badge badge-inactive">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="product-form.php?id=<?php echo (int)$item['id']; ?>" class="btn-admin btn-admin-outline btn-sm">
                    Edit
                  </a>
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
