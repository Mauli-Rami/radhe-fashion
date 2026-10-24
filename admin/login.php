<?php
declare(strict_types=1);

/**
 * Radhe Fashion — Admin Login (temporary hardcoded login)
 * File: admin/login.php
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

start_secure_session();

// If already logged in, go to dashboard
if (is_admin_authenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === 'mauli' && $password === 'mauli') {
            session_regenerate_id(true);

            $_SESSION['admin_user_id']   = 1;
            $_SESSION['admin_username']  = 'mauli';
            $_SESSION['admin_full_name'] = 'Boutique Owner';
            $_SESSION['logged_in_at']    = time();

            header('Location: index.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}
?>