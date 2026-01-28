<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('ROOT_PATH', dirname(__DIR__) . '/');
define('APP_NAME', 'Dataset Sharing and Collaboration Platform');
define('APP_VERSION', '1.0.0');
define('PASSWORD_MIN_LENGTH', 8);
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024);
define('ALLOWED_FILE_TYPES', [
    'csv', 'xlsx', 'xls', 'json', 'pdf', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'doc', 'docx'
]);
if (!defined('DB_DSN')) {
    define('DB_DSN', '');
}
if (!defined('DB_USER')) {
    define('DB_USER', '');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'includes/functions.php';
require_once ROOT_PATH . 'includes/auth.php';
if (!file_exists(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}
