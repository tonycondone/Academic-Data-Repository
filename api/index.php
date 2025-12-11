<?php
// Vercel entry point

// Define the root directory of the  project
define('APP_ROOT', dirname(__DIR__));

// If you use Composer dependencies, include the autoloader:
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

require_once APP_ROOT . '/index.php';
