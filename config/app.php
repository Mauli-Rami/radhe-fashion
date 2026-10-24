<?php
/**
 * Radhe Fashion — Application Configuration
 * File: config/app.php
 */

declare(strict_types=1);

// Application metadata
define('APP_NAME', 'Radhe Fashion');
define('APP_TAGLINE', 'Elegance in Every Thread');
define('APP_ENV', getenv('APP_ENV') ?: 'production'); // 'development' or 'production'

// Error reporting settings based on environment
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
}

// Uploads configuration
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/products/');
define('UPLOAD_URL_PREFIX', 'uploads/products/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB max per image
define('ALLOWED_MIME_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
]);

// Fallback product image if product photo is missing
define('FALLBACK_IMAGE', 'assets/aistudio/WhatsApp_Image_2026-10-20_at_6.34.00_PM_8c4b7f84.jpeg');
