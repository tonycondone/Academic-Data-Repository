<?php
// Check PHP extensions required for PhpSpreadsheet
echo "<!DOCTYPE html>";
echo "<html><head><title>PHP Extensions Check</title></head><body>";
echo "<h1>PHP Extensions Check for PhpSpreadsheet</h1>";

// Required extensions for PhpSpreadsheet
$requiredExtensions = [
    'zip' => 'ZIP extension (required for XLSX files)',
    'xml' => 'XML extension (required for Excel file parsing)',
    'mbstring' => 'Multibyte String extension (required for text handling)',
    'gd' => 'GD extension (optional, for charts and images)',
    'iconv' => 'Iconv extension (optional, for character encoding)'
];

echo "<h2>Required Extensions:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th style='padding: 8px;'>Extension</th>";
echo "<th style='padding: 8px;'>Description</th>";
echo "<th style='padding: 8px;'>Status</th>";
echo "<th style='padding: 8px;'>Version</th>";
echo "</tr>";

foreach ($requiredExtensions as $ext => $description) {
    $loaded = extension_loaded($ext);
    $version = $loaded ? phpversion($ext) : 'Not installed';
    $status = $loaded ? '✅ Loaded' : '❌ Missing';
    $color = $loaded ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td style='padding: 8px;'>$ext</td>";
    echo "<td style='padding: 8px;'>$description</td>";
    echo "<td style='padding: 8px; color: $color;'>$status</td>";
    echo "<td style='padding: 8px;'>$version</td>";
    echo "</tr>";
}

echo "</table>";

// Check if ZipArchive class is available
echo "<h2>ZipArchive Class Check:</h2>";
if (class_exists('ZipArchive')) {
    echo "<p style='color: green;'>✅ ZipArchive class is available</p>";
} else {
    echo "<p style='color: red;'>❌ ZipArchive class is NOT available</p>";
    echo "<p>This is required for reading XLSX files.</p>";
}

// Check PHP version
echo "<h2>PHP Version:</h2>";
echo "<p>Current PHP version: " . phpversion() . "</p>";

// Check if we can create a simple ZIP file
echo "<h2>ZIP Functionality Test:</h2>";
try {
    if (class_exists('ZipArchive')) {
        $testZip = new ZipArchive();
        echo "<p style='color: green;'>✅ ZipArchive can be instantiated</p>";
    } else {
        echo "<p style='color: red;'>❌ ZipArchive class not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error with ZipArchive: " . $e->getMessage() . "</p>";
}

// Check if we can use PhpSpreadsheet with available extensions
echo "<h2>PhpSpreadsheet Compatibility:</h2>";
if (extension_loaded('zip') && extension_loaded('xml') && extension_loaded('mbstring')) {
    echo "<p style='color: green;'>✅ All required extensions are available for PhpSpreadsheet</p>";
} else {
    echo "<p style='color: red;'>❌ Missing required extensions for PhpSpreadsheet</p>";
    echo "<ul>";
    if (!extension_loaded('zip')) echo "<li>ZIP extension is missing</li>";
    if (!extension_loaded('xml')) echo "<li>XML extension is missing</li>";
    if (!extension_loaded('mbstring')) echo "<li>Multibyte String extension is missing</li>";
    echo "</ul>";
}

// Provide solutions
echo "<h2>Solutions:</h2>";
echo "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
echo "<h3 style='color: #856404; margin-top: 0;'>To Fix the ZipArchive Error:</h3>";
echo "<ol>";
echo "<li><strong>Enable ZIP extension in php.ini:</strong></li>";
echo "<ul>";
echo "<li>Find your php.ini file</li>";
echo "<li>Uncomment the line: <code>;extension=zip</code> → <code>extension=zip</code></li>";
echo "<li>Restart your web server</li>";
echo "</ul>";
echo "<li><strong>Alternative: Use XLS format instead of XLSX</strong></li>";
echo "<ul>";
echo "<li>XLS files don't require ZIP extension</li>";
echo "<li>Convert your Excel files to XLS format</li>";
echo "</ul>";
echo "<li><strong>Alternative: Use CSV format</strong></li>";
echo "<ul>";
echo "<li>CSV files work without ZIP extension</li>";
echo "<li>Export your Excel files as CSV</li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

echo "<h2>Current Status:</h2>";
if (extension_loaded('zip') && class_exists('ZipArchive')) {
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Ready for XLSX Files</h3>";
    echo "<p>All required extensions are available. XLSX files should work properly.</p>";
    echo "</div>";
} else {
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ ZIP Extension Missing</h3>";
    echo "<p>XLSX files cannot be read without the ZIP extension. Please enable it or use XLS/CSV files.</p>";
    echo "</div>";
}

echo "<p><a href='index.php'>Back to Home</a></p>";
echo "</body></html>";
?> 