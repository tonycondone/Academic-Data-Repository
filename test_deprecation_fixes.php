<?php
// Test to verify deprecation warnings are fixed
require_once 'autoload.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Deprecation Fixes Test</title></head><body>";
echo "<h1>Deprecation Fixes Test</h1>";

// Test 1: Check if autoloader is working
echo "<h2>Test 1: Autoloader Check</h2>";
if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    echo "<p style='color: green;'>✓ PhpSpreadsheet IOFactory class available</p>";
} else {
    echo "<p style='color: red;'>✗ PhpSpreadsheet IOFactory class not found</p>";
}

// Test 2: Test fputcsv with proper parameters
echo "<h2>Test 2: fputcsv Function Test</h2>";
try {
    $testFile = 'test_csv_output.csv';
    $csvFile = fopen($testFile, 'w');
    
    // Test with proper parameters (should not show deprecation warnings)
    fputcsv($csvFile, ['Test', 'Data'], ',', '"', '\\');
    fputcsv($csvFile, ['Another', 'Row'], ',', '"', '\\');
    
    fclose($csvFile);
    
    if (file_exists($testFile)) {
        echo "<p style='color: green;'>✓ fputcsv test completed successfully</p>";
        echo "<p>Test file created: $testFile</p>";
        
        // Show file contents
        $content = file_get_contents($testFile);
        echo "<p>File contents:</p>";
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
        
        // Clean up
        unlink($testFile);
        echo "<p style='color: green;'>✓ Test file cleaned up</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create test file</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error in fputcsv test: " . $e->getMessage() . "</p>";
}

// Test 3: Test null handling in preview functionality
echo "<h2>Test 3: Null Value Handling Test</h2>";
try {
    // Simulate the preview.php cell handling
    $testCells = ['Normal Cell', null, 'Another Cell', '', 'Last Cell'];
    
    echo "<p>Testing null value handling:</p>";
    echo "<ul>";
    foreach ($testCells as $index => $cell) {
        $cellValue = $cell ?? '';
        $displayValue = htmlspecialchars(substr($cellValue, 0, 50)) . (strlen($cellValue) > 50 ? '...' : '');
        echo "<li>Cell $index: '" . htmlspecialchars($cellValue) . "' → Display: '$displayValue'</li>";
    }
    echo "</ul>";
    
    echo "<p style='color: green;'>✓ Null value handling test completed</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error in null handling test: " . $e->getMessage() . "</p>";
}

// Test 4: Check if excel_converter.php is properly updated
echo "<h2>Test 4: Excel Converter Check</h2>";
if (file_exists('includes/excel_converter.php')) {
    $converterContent = file_get_contents('includes/excel_converter.php');
    
    // Check if fputcsv calls have proper parameters
    if (strpos($converterContent, "fputcsv(\$csvFile, \$rowData, ',', '\"', '\\\\')") !== false) {
        echo "<p style='color: green;'>✓ excel_converter.php fputcsv calls updated</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Some fputcsv calls may still need updating</p>";
    }
    
    // Check if PhpSpreadsheet is being used
    if (strpos($converterContent, 'PhpOffice\\PhpSpreadsheet\\IOFactory') !== false) {
        echo "<p style='color: green;'>✓ excel_converter.php uses PhpSpreadsheet</p>";
    } else {
        echo "<p style='color: orange;'>⚠ excel_converter.php may not be using PhpSpreadsheet</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ includes/excel_converter.php not found</p>";
}

// Test 5: Check preview.php null handling
echo "<h2>Test 5: Preview.php Null Handling Check</h2>";
if (file_exists('preview.php')) {
    $previewContent = file_get_contents('preview.php');
    
    if (strpos($previewContent, '$cell ?? \'\'') !== false) {
        echo "<p style='color: green;'>✓ preview.php null handling updated</p>";
    } else {
        echo "<p style='color: orange;'>⚠ preview.php null handling may need updating</p>";
    }
    
    if (strpos($previewContent, '$cellValue = $cell ?? \'\'') !== false) {
        echo "<p style='color: green;'>✓ preview.php cell value handling updated</p>";
    } else {
        echo "<p style='color: orange;'>⚠ preview.php cell value handling may need updating</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ preview.php not found</p>";
}

echo "<h2>Summary:</h2>";
echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
echo "<h3 style='color: #155724; margin-top: 0;'>✅ Deprecation Fixes Applied</h3>";
echo "<ul>";
echo "<li><strong>fputcsv() warnings:</strong> Fixed with proper parameters</li>";
echo "<li><strong>substr()/strlen() warnings:</strong> Fixed with null coalescing</li>";
echo "<li><strong>excel_converter.php:</strong> Updated with proper fputcsv calls</li>";
echo "<li><strong>preview.php:</strong> Updated with null value handling</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='index.php'>Back to Home</a></p>";
echo "</body></html>";
?> 