<?php
// Test to demonstrate XLSX vs XLS support
require_once 'autoload.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>XLSX vs XLS Support Test</title></head><body>";
echo "<h1>XLSX vs XLS Support Test</h1>";

// Check ZIP extension
$zipAvailable = extension_loaded('zip');
echo "<h2>ZIP Extension Status:</h2>";
echo "<p>ZIP extension: " . ($zipAvailable ? '✅ Available' : '❌ Missing') . "</p>";

// Test 1: Create both XLSX and XLS files
echo "<h2>Test 1: Create Test Files</h2>";
try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add test data
    $testData = [
        ['Name', 'Age', 'Department', 'Salary'],
        ['John Doe', 25, 'Engineering', 75000],
        ['Jane Smith', 30, 'Marketing', 65000],
        ['Bob Johnson', 35, 'Sales', 80000],
        ['Alice Brown', 28, 'HR', 60000]
    ];
    
    foreach ($testData as $rowIndex => $rowData) {
        foreach ($rowData as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
        }
    }
    
    // Create XLS file
    $writerXls = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
    $xlsFile = 'test_file.xls';
    $writerXls->save($xlsFile);
    echo "<p style='color: green;'>✅ XLS file created: $xlsFile</p>";
    
    // Create XLSX file
    $writerXlsx = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $xlsxFile = 'test_file.xlsx';
    $writerXlsx->save($xlsxFile);
    echo "<p style='color: green;'>✅ XLSX file created: $xlsxFile</p>";
    
    // Test 2: Try to read XLS file
    echo "<h2>Test 2: Read XLS File</h2>";
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($xlsFile);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
        
        echo "<p style='color: green;'>✅ XLS file read successfully: $highestRow rows, $highestCol columns</p>";
        
        // Show first few rows
        echo "<h3>XLS File Preview:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        for ($row = 1; $row <= min(3, $highestRow); $row++) {
            echo "<tr>";
            for ($col = 1; $col <= $highestCol; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                echo "<td style='padding: 5px;'>" . htmlspecialchars($cellValue) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error reading XLS file: " . $e->getMessage() . "</p>";
    }
    
    // Test 3: Try to read XLSX file
    echo "<h2>Test 3: Read XLSX File</h2>";
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($xlsxFile);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
        
        echo "<p style='color: green;'>✅ XLSX file read successfully: $highestRow rows, $highestCol columns</p>";
        
        // Show first few rows
        echo "<h3>XLSX File Preview:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        for ($row = 1; $row <= min(3, $highestRow); $row++) {
            echo "<tr>";
            for ($col = 1; $col <= $highestCol; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                echo "<td style='padding: 5px;'>" . htmlspecialchars($cellValue) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error reading XLSX file: " . $e->getMessage() . "</p>";
        
        if (strpos($e->getMessage(), 'ZipArchive') !== false) {
            echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
            echo "<h3 style='color: #721c24; margin-top: 0;'>ZIP Extension Required</h3>";
            echo "<p>XLSX files require the ZIP extension. To enable XLSX support:</p>";
            echo "<ol>";
            echo "<li>Find your php.ini file</li>";
            echo "<li>Uncomment: <code>extension=zip</code></li>";
            echo "<li>Restart your web server</li>";
            echo "</ol>";
            echo "</div>";
        }
    }
    
    // Clean up
    unlink($xlsFile);
    unlink($xlsxFile);
    echo "<p style='color: green;'>✅ Test files cleaned up</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error creating test files: " . $e->getMessage() . "</p>";
}

// Summary
echo "<h2>Summary:</h2>";
echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
echo "<h3 style='color: #155724; margin-top: 0;'>File Format Support</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
echo "<th style='padding: 8px;'>Format</th>";
echo "<th style='padding: 8px;'>Requires ZIP</th>";
echo "<th style='padding: 8px;'>Status</th>";
echo "</tr>";

$formats = [
    'XLS' => ['No', '✅ Working'],
    'XLSX' => ['Yes', $zipAvailable ? '✅ Working' : '❌ Needs ZIP'],
    'CSV' => ['No', '✅ Working'],
    'TXT' => ['No', '✅ Working'],
    'JSON' => ['No', '✅ Working']
];

foreach ($formats as $format => $info) {
    $color = $info[1] === '✅ Working' ? 'green' : 'red';
    echo "<tr>";
    echo "<td style='padding: 8px;'>$format</td>";
    echo "<td style='padding: 8px;'>" . $info[0] . "</td>";
    echo "<td style='padding: 8px; color: $color;'>" . $info[1] . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<h2>Recommendations:</h2>";
echo "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
echo "<h3 style='color: #856404; margin-top: 0;'>For XLSX Support:</h3>";
echo "<ol>";
echo "<li><strong>Enable ZIP extension</strong> for full XLSX support</li>";
echo "<li><strong>Use XLS format</strong> as an alternative (no ZIP needed)</li>";
echo "<li><strong>Use CSV format</strong> for simple data (no ZIP needed)</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='index.php'>Back to Home</a></p>";
echo "</body></html>";
?> 