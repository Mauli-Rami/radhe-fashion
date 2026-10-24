<?php
/**
 * Radhe Fashion — Authentication & Session Management
 * File: includes/auth.php
 */

declare(strict_types=1);

/**
 * Start session with secure cookie parameters.
 */
function start_secure_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    session_set_cookie_params([
        'lifetime' => 0,              // Valid until browser closes
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,      // True on HTTPS
        'httponly' => true,            // Protect against XSS theft
        'samesite' => 'Lax'           // Protect against CSRF
    ]);

    session_name('RF_ADMIN_SESS');
    session_start();
}

/**
 * Check if current user is logged in as an administrator.
 */
function is_admin_authenticated(): bool {
    start_secure_session();
    return !empty($_SESSION['admin_user_id']) && !empty($_SESSION['admin_username']);
}

/**
 * Enforce authentication for protected admin pages.
 */
function require_admin(): void {
    if (!is_admin_authenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Record a failed login attempt and enforce rate-limiting.
 */
function check_login_rate_limit(): bool {
    start_secure_session();
    $maxAttempts = 5;
    $lockoutTime = 300; // 5 minutes

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = time();
    }

    // Reset attempts if lockout duration has passed
    if (time() - ($_SESSION['last_attempt_time'] ?? 0) > $lockoutTime) {
        $_SESSION['login_attempts'] = 0;
    }

    if ($_SESSION['login_attempts'] >= $maxAttempts) {
        return false; // Locked out
    }

    return true;
}

/**
 * Register failed attempt.
 */
function record_failed_login(): void {
    start_secure_session();
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['last_attempt_time'] = time();
}

/**
 * Reset rate limit counter upon success.
 */
function reset_login_attempts(): void {
    start_secure_session();
    unset($_SESSION['login_attempts'], $_SESSION['last_attempt_time']);
}

/**
 * Attempt admin authentication against database.
 */
function authenticate_admin(PDO $pdo, string $username, string $password): array {
    if (!check_login_rate_limit()) {
        return [
            'success' => false,
            'message' => 'Too many failed login attempts. Please wait 5 minutes before trying again.'
        ];
    }

    $stmt = $pdo->prepare('SELECT id, username, password_hash, full_name FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([trim($username)]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Successful login
        reset_login_attempts();
        
        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['admin_user_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_full_name'] = $admin['full_name'] ?: $admin['username'];
        $_SESSION['logged_in_at'] = time();

        return ['success' => true, 'message' => 'Login successful'];
    }

    record_failed_login();
    // Generic failure message to prevent username enumeration
    return ['success' => false, 'message' => 'Invalid username or password.'];
}

/**
 * Log out admin securely.
 */
function logout_admin(): void {
    start_secure_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
