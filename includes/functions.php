<?php
/**
 * Radhe Fashion — Core Helper Functions
 * File: includes/functions.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Escape HTML output securely.
 */
function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate URL-friendly slug from string.
 */
function slugify(string $text): string {
    // Replace non-letter/digits with hyphens
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    return empty($text) ? 'item-' . time() : $text;
}

/**
 * Fetch all key-value settings from the database.
 */
function get_all_settings(PDO $pdo): array {
    try {
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (PDOException $e) {
        error_log('Failed to fetch settings: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a specific setting value with fallback default.
 */
function get_setting(array $settings, string $key, string $default = ''): string {
    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
}

/**
 * Update or insert a setting value.
 */
function save_setting(PDO $pdo, string $key, string $value): bool {
    $stmt = $pdo->prepare('
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (:key, :value)
        ON DUPLICATE KEY UPDATE setting_value = :value_update
    ');
    return $stmt->execute([
        ':key' => $key,
        ':value' => $value,
        ':value_update' => $value,
    ]);
}

/**
 * Build standard WhatsApp inquiry URL with prefilled product message.
 */
function build_whatsapp_inquiry_url(string $whatsappNumber, string $productName, string $productCode = ''): string {
    // Sanitize phone: keep only numbers
    $cleanNumber = preg_replace('/\D/', '', $whatsappNumber);
    if (empty($cleanNumber)) {
        $cleanNumber = '919586914546'; // Default fallback placeholder
    }

    $codePrefix = !empty($productCode) ? ' (Code: ' . trim($productCode) . ')' : '';
    $message = sprintf(
        'Hello Radhe Fashion! I am interested in the "%s"%s. Please share more details and available sizes.',
        trim($productName),
        $codePrefix
    );

    return 'https://wa.me/' . $cleanNumber . '?text=' . rawurlencode($message);
}

/**
 * Validate and process an uploaded product image.
 * 
 * @param array $file The $_FILES['image'] array
 * @return array ['success' => bool, 'path' => ?string, 'error' => ?string]
 */
function handle_image_upload(array $file): array {
    // Check for upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'Image exceeds server upload size limit.',
            UPLOAD_ERR_FORM_SIZE  => 'Image exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'Image was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No image was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write image to disk.',
            UPLOAD_ERR_EXTENSION  => 'A server extension stopped the file upload.',
        ];
        return [
            'success' => false,
            'path' => null,
            'error' => $errors[$file['error']] ?? 'Unknown upload error.'
        ];
    }

    // Validate file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'Image file exceeds maximum allowed size (5MB).'
        ];
    }

    // Inspect server-side MIME type via finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!array_key_exists($mimeType, ALLOWED_MIME_TYPES)) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'Invalid file format. Only JPG, PNG, and WebP images are allowed.'
        ];
    }

    $extension = ALLOWED_MIME_TYPES[$mimeType];

    // Ensure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Generate safe, unique unguessable filename
    $uniqueName = 'product_' . bin2hex(random_bytes(10)) . '_' . time() . '.' . $extension;
    $destinationPath = UPLOAD_DIR . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'Failed to save uploaded image. Check folder permissions.'
        ];
    }

    return [
        'success' => true,
        'path' => UPLOAD_URL_PREFIX . $uniqueName,
        'error' => null
    ];
}

/**
 * Safely delete an uploaded product image if it exists and is not used elsewhere.
 */
function safely_delete_product_image(PDO $pdo, string $imagePath, int $currentProductId = 0): void {
    // Only delete files inside uploads/products/ (prevent deleting assets/ or system files)
    if (strpos($imagePath, UPLOAD_URL_PREFIX) !== 0) {
        return;
    }

    // Check if any OTHER product still uses this image
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE image_path = ? AND id != ?');
    $stmt->execute([$imagePath, $currentProductId]);
    $stillInUse = (int)$stmt->fetchColumn() > 0;

    if (!$stillInUse) {
        $fullPath = dirname(__DIR__) . '/' . $imagePath;
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
