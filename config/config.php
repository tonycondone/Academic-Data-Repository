<?php
/**
 * Main Configuration File
 * Academic Dataset Collaboration Platform
 * Environment-based configuration for local and production deployments
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables (if using vlucas/phpdotenv)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ .  '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/. .');
    $dotenv->load();
}

// Helper function to get environment variables
function getEnvVar($key, $default = null) {
    return $_ENV[$key] ?? $_SERVER[$key] ??  getenv($key) ?: $default;
}

// Error reporting (disable in production)
$app_env = getEnvVar('APP_ENV', 'development');
error_reporting($app_env === 'production' ? E_ALL & ~E_NOTICE : E_ALL);
ini_set('display_errors', getEnvVar('DISPLAY_ERRORS', '1'));

// Application settings
define('APP_NAME', getEnvVar('APP_NAME', 'Academic Dataset Collaboration Platform'));
define('APP_VERSION', getEnvVar('APP_VERSION', '1.0.0'));
define('BASE_URL', getEnvVar('BASE_URL', 'http://localhost/academic-collaboration/'));
define('ROOT_PATH', dirname(__DIR__) . '/');
define('APP_ENV', $app_env);

// Database settings (from environment variables)
define('DB_HOST', getEnvVar('DB_HOST', 'localhost'));
define('DB_NAME', getEnvVar('DB_NAME', 'dataset_platform'));
define('DB_USER', getEnvVar('DB_USER', 'root'));
define('DB_PASS', getEnvVar('DB_PASS', '1212'));
define('DB_PORT', getEnvVar('DB_PORT', '3306'));

// File upload settings
define('UPLOAD_PATH', ROOT_PATH . getEnvVar('UPLOAD_PATH', 'uploads/'));
define('MAX_FILE_SIZE', (int)getEnvVar('MAX_FILE_SIZE', 52428800)); // 50MB
$allowed_types = explode(',', getEnvVar('ALLOWED_FILE_TYPES', 'csv,xlsx,xls,json,pdf,png,jpg,jpeg,gif,txt,doc,docx'));
define('ALLOWED_FILE_TYPES', array_map('trim', $allowed_types));

// Security settings
define('CSRF_TOKEN_NAME', getEnvVar('CSRF_TOKEN_NAME', 'csrf_token'));
define('SESSION_LIFETIME', (int)getEnvVar('SESSION_LIFETIME', 28800)); // 8 hours
define('PASSWORD_MIN_LENGTH', (int)getEnvVar('PASSWORD_MIN_LENGTH', 8));

// Pagination settings
define('ITEMS_PER_PAGE', (int)getEnvVar('ITEMS_PER_PAGE', 20));
define('FILES_PER_PAGE', (int)getEnvVar('FILES_PER_PAGE', 15));

// Version control settings
define('MAX_VERSIONS_PER_FILE', (int)getEnvVar('MAX_VERSIONS_PER_FILE', 100));
define('VERSION_STORAGE_PATH', ROOT_PATH . getEnvVar('VERSION_STORAGE_PATH', 'versions/'));

// Email settings (for notifications)
define('SMTP_HOST', getEnvVar('SMTP_HOST', 'localhost'));
define('SMTP_PORT', (int)getEnvVar('SMTP_PORT', 587));
define('SMTP_USERNAME', getEnvVar('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', getEnvVar('SMTP_PASSWORD', ''));
define('FROM_EMAIL', getEnvVar('FROM_EMAIL', 'noreply@university.edu'));
define('FROM_NAME', getEnvVar('FROM_NAME', 'Academic Collaboration Platform'));

// Timezone
date_default_timezone_set(getEnvVar('TIMEZONE', 'UTC'));

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
    if (! isset($_SESSION[CSRF_TOKEN_NAME])) {
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
    header('Location: login.php? timeout=1');
    exit();
}
$_SESSION['last_activity'] = time();
?>