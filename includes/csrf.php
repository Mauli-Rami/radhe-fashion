<?php
/**
 * Radhe Fashion — CSRF Protection
 * File: includes/csrf.php
 */

declare(strict_types=1);

/**
 * Generate or retrieve the existing CSRF token for this session.
 */
function get_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate a hidden HTML input containing the CSRF token.
 */
function csrf_field(): string {
    $token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate submitted CSRF token using constant-time comparison.
 */
function validate_csrf(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Guard state-changing request with CSRF verification.
 */
function verify_csrf_or_die(): void {
    if (!validate_csrf()) {
        http_response_code(403);
        die('Security Error: Invalid or expired CSRF submission token. Please refresh the page and try again.');
    }
}
