<?php
// Basic application config (bootstrap)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Development error reporting (adjust for production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Paths
define('ROOT_PATH', dirname(__DIR__) . '/');

// App metadata
define('APP_NAME', 'Dataset Sharing and Collaboration Platform');
define('APP_VERSION', '1.0.0');

// Security and validation
define('PASSWORD_MIN_LENGTH', 8);

// Uploads
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024);
define('ALLOWED_FILE_TYPES', [
    'csv', 'xlsx', 'xls', 'json', 'pdf', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'doc', 'docx'
]);

// Optional database constants (empty until install.php runs)
if (!defined('DB_DSN')) {
    // Example: 'mysql:host=localhost;dbname=academic_collaboration;charset=utf8mb4'
    define('DB_DSN', '');
}
if (!defined('DB_USER')) {
    define('DB_USER', '');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

// Include shared code
require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'includes/functions.php';
require_once ROOT_PATH . 'includes/auth.php';

// Ensure upload directories exist
if (!file_exists(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}
