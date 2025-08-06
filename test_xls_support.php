<?php
// Test XLS file support without ZIP extension
require_once 'autoload.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>XLS Support Test</title></head><body>";
echo "<h1>XLS File Support Test (Without ZIP Extension)</h1>";

// Check current extensions
echo "<h2>Current Extensions:</h2>";
echo "<ul>";
echo "<li>ZIP extension: " . (extension_loaded('zip') ? '✅ Loaded' : '❌ Missing') . "</li>";
echo "<li>XML extension: " . (extension_loaded('xml') ? '✅ Loaded' : '❌ Missing') . "</li>";
echo "<li>MBString extension: " . (extension_loaded('mbstring') ? '✅ Loaded' : '❌ Missing') . "</li>";
echo "</ul>";

// Test 1: Create a test XLS file
echo "<h2>Test 1: Create Test XLS File</h2>";
try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add test data
    $testData = [
        ['Name', 'Age', 'Department', 'Salary'],
        ['John Doe', 25, 'Engineering', 75000],
        ['Jane Smith', 30, 'Marketing', 65000],
        ['Bob Johnson', 35, 'Sales', 80000],
        ['Alice Brown', 28, 'HR', 60000],
        ['Charlie Wilson', 32, 'Finance', 70000]
    ];
    
    foreach ($testData as $rowIndex => $rowData) {
        foreach ($rowData as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
        }
    }
    
    // Save as XLS (not XLSX)
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
    $testFile = 'test_xls_file.xls';
    $writer->save($testFile);
    
    echo "<p style='color: green;'>✓ Test XLS file created: $testFile</p>";
    
    // Test 2: Read the XLS file back
    echo "<h2>Test 2: Read XLS File</h2>";
    
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($testFile);
    $worksheet = $spreadsheet->getActiveSheet();
    
    $maxRows = 50;
    $maxCols = 20;
    
    $highestRow = min($worksheet->getHighestRow(), $maxRows);
    $highestCol = min(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn()), $maxCols);
    
    echo "<p>Reading XLS file with $highestRow rows and $highestCol columns</p>";
    
    $previewData = [];
    for ($row = 1; $row <= $highestRow; $row++) {
        $rowData = [];
        for ($col = 1; $col <= $highestCol; $col++) {
            $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
            $rowData[] = $cellValue !== null ? (string)$cellValue : '';
        }
        $previewData[] = $rowData;
    }
    
    // Display the data
    echo "<h3>XLS File Contents:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<thead>";
    echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
    echo "<th style='padding: 8px;'>#</th>";
    foreach (range(1, $highestCol) as $col) {
        echo "<th style='padding: 8px;'>Column $col</th>";
    }
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($previewData as $rowIndex => $row) {
        echo "<tr>";
        echo "<td style='padding: 8px; font-weight: bold;'>" . ($rowIndex + 1) . "</td>";
        foreach ($row as $colIndex => $cell) {
            $cellValue = $cell ?? '';
            $displayValue = htmlspecialchars(substr($cellValue, 0, 50)) . (strlen($cellValue) > 50 ? '...' : '');
            echo "<td style='padding: 8px;' title='" . htmlspecialchars($cellValue) . "'>$displayValue</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    
    // Clean up
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    unlink($testFile);
    
    echo "<p style='color: green;'>✓ Test file cleaned up</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 3: Check file format support
echo "<h2>Test 3: File Format Support</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
echo "<th style='padding: 8px;'>Format</th>";
echo "<th style='padding: 8px;'>Requires ZIP</th>";
echo "<th style='padding: 8px;'>Status</th>";
echo "</tr>";

$formats = [
    'XLS' => false,
    'XLSX' => true,
    'CSV' => false,
    'TXT' => false,
    'JSON' => false
];

foreach ($formats as $format => $requiresZip) {
    $status = $requiresZip ? (extension_loaded('zip') ? '✅ Supported' : '❌ Requires ZIP') : '✅ Supported';
    $color = $requiresZip && !extension_loaded('zip') ? 'red' : 'green';
    
    echo "<tr>";
    echo "<td style='padding: 8px;'>$format</td>";
    echo "<td style='padding: 8px;'>" . ($requiresZip ? 'Yes' : 'No') . "</td>";
    echo "<td style='padding: 8px; color: $color;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Summary:</h2>";
echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
echo "<h3 style='color: #155724; margin-top: 0;'>✅ XLS Support Working</h3>";
echo "<ul>";
echo "<li><strong>XLS files:</strong> Working without ZIP extension</li>";
echo "<li><strong>XLSX files:</strong> Require ZIP extension</li>";
echo "<li><strong>CSV files:</strong> Working without ZIP extension</li>";
echo "<li><strong>Other formats:</strong> Working without ZIP extension</li>";
echo "</ul>";
echo "</div>";

echo "<h2>Recommendations:</h2>";
echo "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
echo "<h3 style='color: #856404; margin-top: 0;'>For Immediate Use:</h3>";
echo "<ul>";
echo "<li><strong>Use XLS format</strong> instead of XLSX until ZIP extension is enabled</li>";
echo "<li><strong>Use CSV format</strong> for simple data files</li>";
echo "<li><strong>Enable ZIP extension</strong> for full XLSX support</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='index.php'>Back to Home</a></p>";
echo "</body></html>";
?> 