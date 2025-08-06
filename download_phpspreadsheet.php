<?php
/**
 * Automated PHPSpreadsheet Downloader
 * Downloads and extracts PHPSpreadsheet for Excel conversion
 */

echo "PHPSpreadsheet Automated Downloader\n";
echo "==================================\n\n";

// Check if PHPSpreadsheet is already available
if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    echo "✓ PHPSpreadsheet is already installed and available!\n";
    exit(0);
}

// Create directory structure
$vendorDir = __DIR__ . '/vendor';
$phpSpreadsheetDir = $vendorDir . '/phpoffice/phpspreadsheet';

if (!is_dir($phpSpreadsheetDir)) {
    mkdir($phpSpreadsheetDir, 0755, true);
}

// Download PHPSpreadsheet (using a simplified approach)
$downloadUrl = 'https://github.com/PHPOffice/PhpSpreadsheet/archive/refs/heads/master.zip';
$zipFile = $vendorDir . '/phpspreadsheet.zip';

echo "Downloading PHPSpreadsheet...\n";

// Use file_get_contents to download (if allow_url_fopen is enabled)
if (ini_get('allow_url_fopen')) {
    $zipContent = @file_get_contents($downloadUrl);
    
    if ($zipContent !== false) {
        file_put_contents($zipFile, $zipContent);
        
        // Check if we have zip extension
        if (class_exists('ZipArchive')) {
            echo "Extracting PHPSpreadsheet...\n";
            
            $zip = new ZipArchive();
            if ($zip->open($zipFile) === TRUE) {
                $zip->extractTo($vendorDir);
                $zip->close();
                
                // Rename extracted folder
                $extractedFolder = $vendorDir . '/PhpSpreadsheet-master';
                if (is_dir($extractedFolder)) {
                    rename($extractedFolder, $phpSpreadsheetDir);
                    echo "✓ PHPSpreadsheet extracted successfully!\n";
                }
                
                // Clean up zip file
                unlink($zipFile);
                
                // Create autoloader
                createAutoloader();
                
            } else {
                echo "✗ Failed to extract ZIP file\n";
            }
        } else {
            echo "✗ ZIP extension not available. Please extract manually.\n";
        }
    } else {
        echo "✗ Failed to download PHPSpreadsheet\n";
    }
} else {
    echo "✗ allow_url_fopen is disabled. Cannot download automatically.\n";
    echo "Please download manually from: https://github.com/PHPOffice/PhpSpreadsheet\n";
}

function createAutoloader() {
    $autoloaderContent = '<?php
/**
 * PHPSpreadsheet Autoloader
 * Manual installation for Excel conversion functionality
 */

// Define PHPSpreadsheet path
define("PHPSPREADSHEET_PATH", __DIR__ . "/phpoffice/phpspreadsheet");

// Include PHPSpreadsheet classes
if (file_exists(PHPSPREADSHEET_PATH . "/src/Bootstrap.php")) {
    require_once PHPSPREADSHEET_PATH . "/src/Bootstrap.php";
} else {
    // Simple autoloader
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
}

// Include IOFactory
if (file_exists(PHPSPREADSHEET_PATH . "/src/PhpSpreadsheet/IOFactory.php")) {
    require_once PHPSPREADSHEET_PATH . "/src/PhpSpreadsheet/IOFactory.php";
}
?>';

    file_put_contents(__DIR__ . '/vendor/autoload.php', $autoloaderContent);
    echo "✓ Autoloader created at vendor/autoload.php\n";
}

// Test installation
echo "\nTesting installation...\n";
if (file_exists($phpSpreadsheetDir . '/src/PhpSpreadsheet/IOFactory.php')) {
    echo "✓ PHPSpreadsheet files found!\n";
    echo "✓ Ready for Excel conversion functionality\n";
} else {
    echo "✗ PHPSpreadsheet files not found\n";
}
?>
