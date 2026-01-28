<?php
// Vercel entry point with basic router

define('APP_ROOT', dirname(__DIR__));
chdir(APP_ROOT);

if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Whitelist of PHP pages that can be served
$allowed = [
    'index.php',
    'login.php',
    'register.php',
    'admin.php',
    'admin_dashboard.php',
    'dashboard.php',
    'user_dashboard.php',
    'browse.php',
    'datasets.php',
    'download.php',
    'preview.php',
    'projects.php',
    'project.php',
    'review.php',
    'profile.php',
    'diagnostic.php',
    'install.php',
    'logout.php'
];

function resolveTarget(string $path, array $allowed): string {
    if ($path === '/' || $path === '') {
        return 'index.php';
    }
    $clean = ltrim($path, '/');
    // Normalize directory-like paths (e.g., "/login")
    if (pathinfo($clean, PATHINFO_EXTENSION) !== 'php') {
        $candidate = $clean . '.php';
        if (in_array($candidate, $allowed, true) && file_exists(APP_ROOT . '/' . $candidate)) {
            return $candidate;
        }
    }
    // Direct PHP file
    if (in_array($clean, $allowed, true) && file_exists(APP_ROOT . '/' . $clean)) {
        return $clean;
    }
    return 'index.php';
}

$target = resolveTarget($path, $allowed);
require_once APP_ROOT . '/' . $target;
