<?php
/**
 * Radhe Fashion — Category Management
 * File: admin/categories.php
 */

declare(strict_types=1);

$pageTitle = 'Manage Categories';
require_once __DIR__ . '/header.php';

$pdo = get_db_connection();

$errors = [];
$editCategoryId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editingCategory = null;

// Handle Form Submissions (Create, Update, Delete, Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $action = $_POST['action'] ?? '';

    // Create or Update Category
    if ($action === 'save_category') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            $errors[] = 'Category name cannot be empty.';
        } else {
            $slug = slugify($name);

            // Check unique slug
            $slugSql = 'SELECT id FROM categories WHERE slug = ?' . ($catId > 0 ? ' AND id != ?' : '');
            $slugParams = $catId > 0 ? [$slug, $catId] : [$slug];
            $slugStmt = $pdo->prepare($slugSql);
            $slugStmt->execute($slugParams);

            if ($slugStmt->fetch()) {
                $slug .= '-' . time();
            }

            if ($catId > 0) {
                // Update
                $stmt = $pdo->prepare('
                    UPDATE categories SET 
                        name = :name, 
                        slug = :slug, 
                        description = :description, 
                        display_order = :display_order, 
                        is_active = :is_active 
                    WHERE id = :id
                ');
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':description' => $description !== '' ? $description : null,
                    ':display_order' => $displayOrder,
                    ':is_active' => $isActive,
                    ':id' => $catId,
                ]);
                $_SESSION['flash_success'] = 'Category "' . e($name) . '" updated successfully.';
            } else {
                // Insert
                $stmt = $pdo->prepare('
                    INSERT INTO categories (name, slug, description, display_order, is_active)
                    VALUES (:name, :slug, :description, :display_order, :is_active)
                ');
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':description' => $description !== '' ? $description : null,
                    ':display_order' => $displayOrder,
                    ':is_active' => $isActive,
                ]);
                $_SESSION['flash_success'] = 'Category "' . e($name) . '" created successfully.';
            }

            header('Location: categories.php');
            exit;
        }
    }

    // Toggle Category Visibility
    if ($action === 'toggle_active') {
        $catId = (int)($_POST['category_id'] ?? 0);
        if ($catId > 0) {
            $stmt = $pdo->prepare('UPDATE categories SET is_active = NOT is_active WHERE id = ?');
            $stmt->execute([$catId]);
            $_SESSION['flash_success'] = 'Category visibility toggled.';
            header('Location: categories.php');
            exit;
        }
    }

    // Safe Delete Category
    if ($action === 'delete_category') {
        $catId = (int)($_POST['category_id'] ?? 0);
        if ($catId > 0) {
            // Check if products exist in this category
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $stmt->execute([$catId]);
            $count = (int)$stmt->fetchColumn();

            if ($count > 0) {
                $_SESSION['flash_error'] = "Cannot delete this category because it contains {$count} product(s). Please reassign or delete the products first.";
            } else {
                $delStmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
                $delStmt->execute([$catId]);
                $_SESSION['flash_success'] = 'Category deleted successfully.';
            }

            header('Location: categories.php');
            exit;
        }
    }
}

// If in Edit Mode, fetch current category
if ($editCategoryId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editCategoryId]);
    $editingCategory = $stmt->fetch();
}

// Fetch all categories with product counts
$sql = '
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.display_order ASC, c.name ASC
';
$categories = $pdo->query($sql)->fetchAll();
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul>
      <?php foreach ($errors as $err): ?>
        <li><?php echo e($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="form-grid form-grid-2">
  
  <!-- Left Column: Add / Edit Category Form -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><?php echo $editingCategory ? 'Edit Category: ' . e($editingCategory['name']) : 'Create New Category'; ?></h3>
      <?php if ($editingCategory): ?>
        <a href="categories.php" style="font-size: 0.85rem; color: var(--admin-text-muted); text-decoration: none;">&times; Cancel Edit</a>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <form method="POST" action="categories.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save_category">
        <input type="hidden" name="category_id" value="<?php echo $editingCategory ? (int)$editingCategory['id'] : 0; ?>">

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="name" class="form-label">Category Name *</label>
          <input type="text" id="name" name="name" class="form-input" required 
                 value="<?php echo e($editingCategory['name'] ?? ''); ?>" placeholder="e.g. Daily Wear Kurtis">
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="display_order" class="form-label">Display Order</label>
          <input type="number" id="display_order" name="display_order" class="form-input" 
                 value="<?php echo e((string)($editingCategory['display_order'] ?? 0)); ?>">
          <span class="form-hint">Lower numbers appear first on the public website filter.</span>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="description" class="form-label">Description (Optional)</label>
          <textarea id="description" name="description" rows="3" class="form-textarea" 
                    placeholder="Short summary for this category..."><?php echo e($editingCategory['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label class="form-checkbox-label">
            <input type="checkbox" name="is_active" value="1" <?php echo !isset($editingCategory['is_active']) || $editingCategory['is_active'] ? 'checked' : ''; ?>>
            <span>Active &amp; Visible on Public Website</span>
          </label>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary">
          <?php echo $editingCategory ? 'Update Category' : 'Create Category'; ?>
        </button>
      </form>
    </div>
  </div>

  <!-- Right Column: Current Categories List -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Existing Categories (<?php echo count($categories); ?>)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Order</th>
              <th>Category Name</th>
              <th>Products</th>
              <th>Status</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td><strong>#<?php echo (int)$cat['display_order']; ?></strong></td>
                <td>
                  <strong><?php echo e($cat['name']); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--admin-text-muted); font-family: monospace;">
                    /<?php echo e($cat['slug']); ?>
                  </div>
                </td>
                <td>
                  <span class="badge" style="background: #EFEFEF; color: #333;">
                    <?php echo (int)$cat['product_count']; ?> products
                  </span>
                </td>
                <td>
                  <form method="POST" action="categories.php" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="category_id" value="<?php echo (int)$cat['id']; ?>">
                    <button type="submit" class="badge <?php echo $cat['is_active'] ? 'badge-active' : 'badge-inactive'; ?>" 
                            style="cursor: pointer; border: none;" title="Toggle visibility">
                      <?php echo $cat['is_active'] ? 'Active' : 'Hidden'; ?>
                    </button>
                  </form>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <a href="categories.php?edit=<?php echo (int)$cat['id']; ?>" class="btn-admin btn-admin-outline btn-sm">
                    Edit
                  </a>

                  <form method="POST" action="categories.php" style="display: inline;" 
                        onsubmit="return confirm('Are you sure you want to delete category \'<?php echo addslashes($cat['name']); ?>\'?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" value="<?php echo (int)$cat['id']; ?>">
                    <button type="submit" class="btn-admin btn-admin-danger btn-sm" 
                            <?php echo $cat['product_count'] > 0 ? 'title="Cannot delete while products are assigned"' : ''; ?>>
                      Delete
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
