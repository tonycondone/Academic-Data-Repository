<?php
require_once __DIR__ . "/vendor/autoload.php";

echo "Testing PHPSpreadsheet installation...\n";

if (class_exists("PhpOffice\PhpSpreadsheet\IOFactory")) {
    echo "✓ PHPSpreadsheet is successfully installed!\n";
    echo "✓ Excel conversion functionality is now available.\n";
} else {
    echo "✗ PHPSpreadsheet is not yet installed.\n";
    echo "Please follow the instructions in PHPSpreadsheet_Installation_Guide.txt\n";
}
?>