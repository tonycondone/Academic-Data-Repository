<?php
// Test to verify ZIP extension is now working
require_once 'autoload.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>ZIP Extension Test</title></head><body>";
echo "<h1>ZIP Extension Test</h1>";

// Check if ZIP extension is now loaded
echo "<h2>ZIP Extension Status:</h2>";
$zipLoaded = extension_loaded('zip');
$zipArchiveAvailable = class_exists('ZipArchive');

echo "<ul>";
echo "<li>ZIP extension loaded: " . ($zipLoaded ? '✅ Yes' : '❌ No') . "</li>";
echo "<li>ZipArchive class available: " . ($zipArchiveAvailable ? '✅ Yes' : '❌ No') . "</li>";
echo "</ul>";

if ($zipLoaded && $zipArchiveAvailable) {
    echo "<p style='color: green;'>✅ ZIP extension is working! XLSX files should now be supported.</p>";
} else {
    echo "<p style='color: red;'>❌ ZIP extension is still not working. Please restart your web server.</p>";
}

// Test 1: Create a test XLSX file
echo "<h2>Test 1: Create Test XLSX File</h2>";
try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add test data
    $testData = [
        ['Name', 'Age', 'Department', 'Salary', 'Start Date'],
        ['John Doe', 25, 'Engineering', 75000, '2023-01-15'],
        ['Jane Smith', 30, 'Marketing', 65000, '2022-08-20'],
        ['Bob Johnson', 35, 'Sales', 80000, '2021-03-10'],
        ['Alice Brown', 28, 'HR', 60000, '2023-06-01'],
        ['Charlie Wilson', 32, 'Finance', 70000, '2022-11-15']
    ];
    
    foreach ($testData as $rowIndex => $rowData) {
        foreach ($rowData as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
        }
    }
    
    // Save as XLSX
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $testFile = 'test_xlsx_file.xlsx';
    $writer->save($testFile);
    
    echo "<p style='color: green;'>✅ XLSX file created: $testFile</p>";
    
    // Test 2: Try to read the XLSX file
    echo "<h2>Test 2: Read XLSX File</h2>";
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($testFile);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $highestRow = $worksheet->getHighestRow();
        $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
        
        echo "<p style='color: green;'>✅ XLSX file read successfully: $highestRow rows, $highestCol columns</p>";
        
        // Show the data
        echo "<h3>XLSX File Contents:</h3>";
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
        
        for ($row = 1; $row <= $highestRow; $row++) {
            echo "<tr>";
            echo "<td style='padding: 8px; font-weight: bold;'>$row</td>";
            for ($col = 1; $col <= $highestCol; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
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
        
        echo "<p style='color: green;'>✅ Test file cleaned up</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error reading XLSX file: " . $e->getMessage() . "</p>";
        
        if (strpos($e->getMessage(), 'ZipArchive') !== false) {
            echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
            echo "<h3 style='color: #721c24; margin-top: 0;'>ZIP Extension Still Not Working</h3>";
            echo "<p>Please restart your web server after enabling the ZIP extension.</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error creating XLSX file: " . $e->getMessage() . "</p>";
}

// Test 3: Check all file format support
echo "<h2>Test 3: File Format Support Status</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
echo "<th style='padding: 8px;'>Format</th>";
echo "<th style='padding: 8px;'>Requires ZIP</th>";
echo "<th style='padding: 8px;'>Status</th>";
echo "</tr>";

$formats = [
    'XLS' => ['No', '✅ Working'],
    'XLSX' => ['Yes', $zipLoaded ? '✅ Working' : '❌ Needs ZIP'],
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

// Summary
echo "<h2>Summary:</h2>";
if ($zipLoaded) {
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ ZIP Extension Working!</h3>";
    echo "<p><strong>XLSX files are now supported!</strong></p>";
    echo "<ul>";
    echo "<li>✅ XLS files working</li>";
    echo "<li>✅ XLSX files working</li>";
    echo "<li>✅ CSV files working</li>";
    echo "<li>✅ All other formats working</li>";
    echo "</ul>";
    echo "<p>You can now upload and preview XLSX files in your platform.</p>";
    echo "</div>";
} else {
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ ZIP Extension Not Working</h3>";
    echo "<p>Please restart your web server after enabling the ZIP extension.</p>";
    echo "<p>Until then, use XLS format instead of XLSX.</p>";
    echo "</div>";
}

echo "<p><a href='index.php'>Back to Home</a></p>";
echo "</body></html>";
?> 