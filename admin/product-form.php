<?php
/**
 * Radhe Fashion — Product Create & Edit Form
 * File: admin/product-form.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$pdo = get_db_connection();

// Determine Add vs Edit mode
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $productId > 0;
$pageTitle = $isEdit ? 'Edit Product' : 'Add New Product';

$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC')->fetchAll();

// Default form state
$product = [
    'name' => '',
    'product_code' => '',
    'category_id' => $categories[0]['id'] ?? 0,
    'description' => '',
    'price' => '',
    'show_price' => 0,
    'availability_status' => 'in_stock',
    'is_active' => 1,
    'image_path' => '',
];

$errors = [];

// If editing, load current record from database
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        $_SESSION['flash_error'] = 'Product not found.';
        header('Location: products.php');
        exit;
    }

    $product = array_merge($product, $existing);
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $name = trim((string)($_POST['name'] ?? ''));
    $productCode = trim((string)($_POST['product_code'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim((string)($_POST['description'] ?? ''));
    $rawPrice = trim((string)($_POST['price'] ?? ''));
    $showPrice = isset($_POST['show_price']) ? 1 : 0;
    $availability = (string)($_POST['availability_status'] ?? 'in_stock');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if ($name === '') {
        $errors[] = 'Product name is required.';
    }

    if ($categoryId <= 0) {
        $errors[] = 'Please select a valid category.';
    }

    $price = null;
    if ($rawPrice !== '') {
        if (!is_numeric($rawPrice) || (float)$rawPrice < 0) {
            $errors[] = 'Price must be a valid positive number.';
        } else {
            $price = (float)$rawPrice;
        }
    }

    if (!in_array($availability, ['in_stock', 'made_to_order', 'out_of_stock'], true)) {
        $availability = 'in_stock';
    }

    // Check unique product code if provided
    if ($productCode !== '') {
        $codeSql = 'SELECT id FROM products WHERE product_code = ?' . ($isEdit ? ' AND id != ?' : '');
        $codeParams = $isEdit ? [$productCode, $productId] : [$productCode];
        $codeStmt = $pdo->prepare($codeSql);
        $codeStmt->execute($codeParams);
        if ($codeStmt->fetch()) {
            $errors[] = 'A product with this product code already exists. Please use a unique code.';
        }
    }

    // Handle Image Upload if new file provided
    $uploadedImagePath = $product['image_path'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = handle_image_upload($_FILES['image']);
        if ($uploadResult['success']) {
            $uploadedImagePath = $uploadResult['path'];

            // If updating and previous image was an uploaded file, safely remove old file
            if ($isEdit && !empty($product['image_path'])) {
                safely_delete_product_image($pdo, $product['image_path'], $productId);
            }
        } else {
            $errors[] = $uploadResult['error'];
        }
    }

    // If no errors, persist to database
    if (empty($errors)) {
        $slug = slugify($name);

        if ($isEdit) {
            $updateSql = '
                UPDATE products SET 
                    name = :name,
                    slug = :slug,
                    product_code = :product_code,
                    category_id = :category_id,
                    description = :description,
                    price = :price,
                    show_price = :show_price,
                    availability_status = :availability,
                    is_active = :is_active,
                    image_path = :image_path
                WHERE id = :id
            ';
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':product_code' => $productCode !== '' ? $productCode : null,
                ':category_id' => $categoryId,
                ':description' => $description,
                ':price' => $price,
                ':show_price' => $showPrice,
                ':availability' => $availability,
                ':is_active' => $isActive,
                ':image_path' => $uploadedImagePath,
                ':id' => $productId,
            ]);

            $_SESSION['flash_success'] = 'Product "' . e($name) . '" updated successfully.';
        } else {
            $insertSql = '
                INSERT INTO products (
                    name, slug, product_code, category_id, description, 
                    price, show_price, availability_status, is_active, image_path
                ) VALUES (
                    :name, :slug, :product_code, :category_id, :description,
                    :price, :show_price, :availability, :is_active, :image_path
                )
            ';
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':product_code' => $productCode !== '' ? $productCode : null,
                ':category_id' => $categoryId,
                ':description' => $description,
                ':price' => $price,
                ':show_price' => $showPrice,
                ':availability' => $availability,
                ':is_active' => $isActive,
                ':image_path' => $uploadedImagePath,
            ]);

            $_SESSION['flash_success'] = 'Product "' . e($name) . '" added successfully to the boutique.';
        }

        header('Location: products.php');
        exit;
    }

    // Repopulate form with submitted values if errors occurred
    $product['name'] = $name;
    $product['product_code'] = $productCode;
    $product['category_id'] = $categoryId;
    $product['description'] = $description;
    $product['price'] = $rawPrice;
    $product['show_price'] = $showPrice;
    $product['availability_status'] = $availability;
    $product['is_active'] = $isActive;
}

require_once __DIR__ . '/header.php';
?>

<div style="margin-bottom: 1.5rem;">
  <a href="products.php" style="color: var(--admin-text-muted); text-decoration: none; font-size: 0.9rem;">
    &larr; Back to Products List
  </a>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger" style="flex-direction: column; align-items: flex-start;">
    <strong>Please fix the following issues:</strong>
    <ul style="margin-top: 0.5rem; padding-left: 1.25rem;">
      <?php foreach ($errors as $err): ?>
        <li><?php echo e($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title"><?php echo $isEdit ? 'Edit Product Details' : 'Create New Kurti / Ethnic Piece'; ?></h3>
  </div>
  <div class="card-body">
    <form method="POST" action="product-form.php<?php echo $isEdit ? '?id=' . $productId : ''; ?>" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>

      <div class="form-grid form-grid-2" style="margin-bottom: 1.5rem;">
        
        <!-- Product Name -->
        <div class="form-group">
          <label for="name" class="form-label">Product Name *</label>
          <input type="text" id="name" name="name" class="form-input" required 
                 value="<?php echo e($product['name']); ?>" placeholder="e.g. Royal Maroon Flared Anarkali Suit">
        </div>

        <!-- Product Code -->
        <div class="form-group">
          <label for="product_code" class="form-label">Product Code (Optional)</label>
          <input type="text" id="product_code" name="product_code" class="form-input" 
                 value="<?php echo e($product['product_code']); ?>" placeholder="e.g. RF-102">
          <span class="form-hint">Unique identifier used for customer inquiries.</span>
        </div>

      </div>

      <div class="form-grid form-grid-2" style="margin-bottom: 1.5rem;">
        
        <!-- Category Selection -->
        <div class="form-group">
          <label for="category_id" class="form-label">Category *</label>
          <select id="category_id" name="category_id" class="form-select" required>
            <option value="">-- Choose Category --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo (int)$cat['id']; ?>" <?php echo (int)$product['category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
                <?php echo e($cat['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Availability Status -->
        <div class="form-group">
          <label for="availability_status" class="form-label">Availability</label>
          <select id="availability_status" name="availability_status" class="form-select">
            <option value="in_stock" <?php echo $product['availability_status'] === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
            <option value="made_to_order" <?php echo $product['availability_status'] === 'made_to_order' ? 'selected' : ''; ?>>Made to Order</option>
            <option value="out_of_stock" <?php echo $product['availability_status'] === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
          </select>
        </div>

      </div>

      <div class="form-grid form-grid-2" style="margin-bottom: 1.5rem;">
        
        <!-- Price -->
        <div class="form-group">
          <label for="price" class="form-label">Price in ₹ (Optional)</label>
          <input type="number" step="0.01" min="0" id="price" name="price" class="form-input" 
                 value="<?php echo e((string)$product['price']); ?>" placeholder="e.g. 1899.00">
          <span class="form-hint">Leave blank if price is on inquiry only.</span>
        </div>

        <!-- Price Display Toggle -->
        <div class="form-group" style="justify-content: flex-end; padding-bottom: 0.5rem;">
          <label class="form-checkbox-label">
            <input type="checkbox" name="show_price" value="1" <?php echo !empty($product['show_price']) ? 'checked' : ''; ?>>
            <span><strong>Display price publicly on website</strong></span>
          </label>
          <span class="form-hint" style="margin-left: 1.6rem;">When unchecked, customer sees &ldquo;Inquire for Price&rdquo; button.</span>
        </div>

      </div>

      <!-- Description -->
      <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="description" class="form-label">Description / Styling Notes</label>
        <textarea id="description" name="description" rows="4" class="form-textarea" 
                  placeholder="Describe the fabric, embroidery, drape, occasions, and matching recommendations..."><?php echo e($product['description']); ?></textarea>
      </div>

      <!-- Image Upload & Preview -->
      <div class="form-group" style="margin-bottom: 1.75rem;">
        <label for="image" class="form-label">Product Photograph</label>
        
        <?php if (!empty($product['image_path'])): ?>
          <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; padding: 0.75rem; background: var(--admin-bg); border-radius: var(--radius-sm);">
            <img src="../<?php echo e($product['image_path']); ?>" alt="Current product" style="width: 60px; height: 75px; object-fit: cover; border-radius: 4px;">
            <div>
              <span style="font-size: 0.85rem; font-weight: 600;">Current image:</span>
              <p style="font-size: 0.78rem; color: var(--admin-text-muted); word-break: break-all;"><?php echo e($product['image_path']); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <input type="file" id="image" name="image" class="form-input" accept="image/jpeg,image/png,image/webp">
        <span class="form-hint">Allowed formats: JPG, PNG, WebP. Maximum size: 5MB. Select a new file only to replace the existing photo.</span>
      </div>

      <!-- Active / Public Visibility -->
      <div class="form-group" style="margin-bottom: 2rem; padding: 1rem; background: #FFFDF9; border: 1px solid #F0E8DC; border-radius: var(--radius-sm);">
        <label class="form-checkbox-label">
          <input type="checkbox" name="is_active" value="1" <?php echo !empty($product['is_active']) ? 'checked' : ''; ?>>
          <span><strong>Active &amp; Visible on Public Website</strong></span>
        </label>
        <span class="form-hint" style="margin-left: 1.6rem;">Uncheck this to hide the product from customers without deleting it.</span>
      </div>

      <!-- Form Actions -->
      <div style="display: flex; gap: 1rem;">
        <button type="submit" class="btn-admin btn-admin-primary">
          <?php echo $isEdit ? 'Save Product Changes' : 'Publish Product to Store'; ?>
        </button>
        <a href="products.php" class="btn-admin btn-admin-outline">Cancel</a>
      </div>

    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
