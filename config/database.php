<?php
/**
 * Radhe Fashion — Database Connection (PDO)
 * File: config/database.php
 * 
 * Secure PDO connection using prepared statements and UTF-8 collation.
 */

declare(strict_types=1);

// Database configuration constants (editable for your host environment or XAMPP)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'radhe_fashion');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''); // XAMPP default is empty

/**
 * Get or create the singleton PDO connection.
 * 
 * @return PDO
 * @throws PDOException if connection fails
 */
function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Native prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log full error for administrator on server
            error_log('Database connection error: ' . $e->getMessage());
            
            // Do not expose credentials or internal paths to visitors
            throw new PDOException('Could not connect to the database. Please verify MySQL service is running.');
        }
    }

    return $pdo;
}
