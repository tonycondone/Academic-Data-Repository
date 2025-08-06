<?php
/**
 * PHPSpreadsheet Autoloader
 * Manual installation for Excel conversion functionality
 */

// Define PHPSpreadsheet path
define("PHPSPREADSHEET_PATH", __DIR__ . "/vendor/phpoffice/phpspreadsheet");

// Simple autoloader for PHPSpreadsheet
spl_autoload_register(function($class) {
    $prefix = "PhpOffice\PhpSpreadsheet\";
    $base_dir = PHPSPREADSHEET_PATH . "/src/PhpSpreadsheet/";
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace("\", "/", $relative_class) . ".php";
    
    if (file_exists($file)) {
        require $file;
    }
});

// Include PHPSpreadsheet classes
require_once PHPSPREADSHEET_PATH . "/src/PhpSpreadsheet/IOFactory.php";
?>