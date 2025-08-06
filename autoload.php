<?php
// Simple autoloader for PhpSpreadsheet
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'PhpOffice\\PhpSpreadsheet\\';
    $base_dir = __DIR__ . '/vendor/phpoffice/phpspreadsheet/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Also include some common dependencies
$commonFiles = [
    'vendor/phpoffice/phpspreadsheet/Spreadsheet.php',
    'vendor/phpoffice/phpspreadsheet/IOFactory.php',
    'vendor/phpoffice/phpspreadsheet/Worksheet/Worksheet.php',
    'vendor/phpoffice/phpspreadsheet/Cell/Cell.php',
    'vendor/phpoffice/phpspreadsheet/Writer/BaseWriter.php',
    'vendor/phpoffice/phpspreadsheet/Writer/IWriter.php',
    'vendor/phpoffice/phpspreadsheet/Writer/Xlsx.php',
    'vendor/phpoffice/phpspreadsheet/Writer/Xlsx/Writer.php',
    'vendor/phpoffice/phpspreadsheet/Reader/IReader.php',
    'vendor/phpoffice/phpspreadsheet/Reader/Xlsx.php'
];

foreach ($commonFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}
?> 