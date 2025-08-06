<?php
/**
 * Manual PHPSpreadsheet Installer
 * Downloads and sets up PHPSpreadsheet for Excel conversion functionality
 */

echo "PHPSpreadsheet Manual Installer\n";
echo "===============================\n\n";

// Check if PHPSpreadsheet is already available
if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    echo "✓ PHPSpreadsheet is already installed and available!\n";
    exit(0);
}

// Create vendor directory structure
$vendorDir = __DIR__ . '/vendor';
$phpSpreadsheetDir = $vendorDir . '/phpoffice/phpspreadsheet';

if (!is_dir($vendorDir)) {
    mkdir($vendorDir, 0755, true);
}

// Create autoloader file
$autoloaderContent = '<?php
/**
 * PHPSpreadsheet Autoloader
 * Manual installation for Excel conversion functionality
 */

// Define PHPSpreadsheet path
define("PHPSPREADSHEET_PATH", __DIR__ . "/vendor/phpoffice/phpspreadsheet");

// Simple autoloader for PHPSpreadsheet
spl_autoload_register(function($class) {
    $prefix = "PhpOffice\\PhpSpreadsheet\\";
    $base_dir = PHPSPREADSHEET_PATH . "/src/PhpSpreadsheet/";
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace("\\", "/", $relative_class) . ".php";
    
    if (file_exists($file)) {
        require $file;
    }
});

// Include PHPSpreadsheet classes
require_once PHPSPREADSHEET_PATH . "/src/PhpSpreadsheet/IOFactory.php";
?>';

// Create the autoloader file
file_put_contents($vendorDir . '/autoload.php', $autoloaderContent);

// Create a simple installation guide
$guide = <<<EOT
PHPSpreadsheet Installation Guide
================================

Since Composer is not available, please follow these steps:

1. Download PHPSpreadsheet from GitHub:
   - Go to: https://github.com/PHPOffice/PhpSpreadsheet/releases
   - Download the latest release ZIP file

2. Extract the ZIP file and copy the contents:
   - Extract the downloaded ZIP file
   - Copy the 'src' folder to: vendor/phpoffice/phpspreadsheet/src

3. Test the installation:
   - Run: php test_phpspreadsheet.php

4. Update your PHP files to include the autoloader:
   - Add this line at the top of your PHP files:
     require_once __DIR__ . '/vendor/autoload.php';

EOT;

file_put_contents('PHPSpreadsheet_Installation_Guide.txt', $guide);

// Create a test file
$testFile = '<?php
require_once __DIR__ . "/vendor/autoload.php";

echo "Testing PHPSpreadsheet installation...\n";

if (class_exists("PhpOffice\\PhpSpreadsheet\\IOFactory")) {
    echo "✓ PHPSpreadsheet is successfully installed!\n";
    echo "✓ Excel conversion functionality is now available.\n";
} else {
    echo "✗ PHPSpreadsheet is not yet installed.\n";
    echo "Please follow the instructions in PHPSpreadsheet_Installation_Guide.txt\n";
}
?>';

file_put_contents('test_phpspreadsheet.php', $testFile);

echo "✓ Installation files created successfully!\n";
echo "✓ Created vendor/autoload.php for PHPSpreadsheet autoloading\n";
echo "✓ Created test_phpspreadsheet.php for testing\n";
echo "✓ Created PHPSpreadsheet_Installation_Guide.txt with detailed instructions\n\n";
echo "Next steps:\n";
echo "1. Download PHPSpreadsheet from https://github.com/PHPOffice/PhpSpreadsheet/releases\n";
echo "2. Extract and copy files to vendor/phpoffice/phpspreadsheet/\n";
echo "3. Run: php test_phpspreadsheet.php to test installation\n";
echo "4. Update your PHP files to include: require_once __DIR__ . '/vendor/autoload.php';\n";
?>
