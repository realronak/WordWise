<?php
/**
 * WordWise Database Connection and Prepared-Statement Abstraction
 * Supports both mysqli (legacy compatibility) and PDO (prepared statements)
 */

require_once __DIR__ . '/env.php';

$db_host = env('DB_HOST', 'localhost');
$db_user = env('DB_USER', 'root');
$db_pass = env('DB_PASS', '');
$db_name = env('DB_NAME', 'wordwise');

if (file_exists(__DIR__ . '/db.local.php')) {
    include __DIR__ . '/db.local.php';
}

// 1. Initialize mysqli connection for backward-compatibility
$connection = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($connection, "utf8mb4");

// 2. Initialize PDO connection with strict error handling and prepared statements
try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Database PDO connection error: " . $e->getMessage());
}

/**
 * Execute parameterized query using PDO
 */
function db_query(string $sql, array $params = []): PDOStatement {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch all matching rows
 */
function db_fetch_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

/**
 * Fetch a single row
 */
function db_fetch_one(string $sql, array $params = []): ?array {
    $result = db_query($sql, $params)->fetch();
    return $result ?: null;
}

/**
 * Execute INSERT, UPDATE, DELETE query
 */
function db_execute(string $sql, array $params = []): bool {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Get last inserted ID
 */
function db_last_id(): string {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Get setting value from database
 */
function get_setting(string $key, string $default = ''): string {
    $row = db_fetch_one("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $row['setting_value'] ?? $default;
}

/**
 * Update or insert setting
 */
function set_setting(string $key, string $value): bool {
    return db_execute(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [$key, $value]
    );
}

// Automatically load security helpers
require_once __DIR__ . '/security.php';
