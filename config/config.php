<?php
/**
 * Main Configuration File
 * Academic Dataset Collaboration Platform
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application settings
define('APP_NAME', 'Academic Dataset Collaboration Platform');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/academic-collaboration/');
define('ROOT_PATH', dirname(__DIR__) . '/');

// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'academic_collaboration');
define('DB_USER', 'root');
define('DB_PASS', '');

// File upload settings
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', [
    'csv', 'xlsx', 'xls', 'json', 'pdf', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'doc', 'docx'
]);

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('PASSWORD_MIN_LENGTH', 8);

// Pagination settings
define('ITEMS_PER_PAGE', 20);
define('FILES_PER_PAGE', 15);

// Version control settings
define('MAX_VERSIONS_PER_FILE', 100);
define('VERSION_STORAGE_PATH', ROOT_PATH . 'versions/');

// Email settings (for notifications)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@university.edu');
define('FROM_NAME', 'Academic Collaboration Platform');

// Timezone
date_default_timezone_set('UTC');

// Include required files
require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'includes/functions.php';
require_once ROOT_PATH . 'includes/auth.php';

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(VERSION_STORAGE_PATH)) {
    mkdir(VERSION_STORAGE_PATH, 0755, true);
}

// CSRF Token generation
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Verify CSRF Token
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Auto-logout on session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['last_activity'] = time();
?>