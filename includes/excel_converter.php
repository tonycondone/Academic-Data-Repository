<?php
/**
 * Excel to CSV Converter
 * Academic Dataset Collaboration Platform
 */

class ExcelConverter {
    
    /**
     * Convert Excel file to CSV
     */
    public static function convertToCSV($excelFilePath, $csvFilePath) {
        try {
            // Check if file exists
            if (!file_exists($excelFilePath)) {
                return [
                    'success' => false,
                    'message' => 'Excel file not found'
                ];
            }
            
            // Get file extension
            $extension = strtolower(pathinfo($excelFilePath, PATHINFO_EXTENSION));
            
            if (!in_array($extension, ['xls', 'xlsx'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid Excel file format'
                ];
            }
            
            // Try to use PhpSpreadsheet if available, otherwise use simple CSV conversion
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                return self::convertWithPhpSpreadsheet($excelFilePath, $csvFilePath);
            } else {
                return self::convertWithSimpleMethod($excelFilePath, $csvFilePath);
            }
            
        } catch (Exception $e) {
            error_log("Excel conversion error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to convert Excel file: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Convert using PhpSpreadsheet library (if available)
     */
    private static function convertWithPhpSpreadsheet($excelFilePath, $csvFilePath) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFilePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $csvFile = fopen($csvFilePath, 'w');
            
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getCalculatedValue();
                }
                
                fputcsv($csvFile, $rowData);
            }
            
            fclose($csvFile);
            
            return [
                'success' => true,
                'message' => 'Excel file converted to CSV successfully',
                'csv_path' => $csvFilePath
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'PhpSpreadsheet conversion failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Simple conversion method (fallback)
     */
    private static function convertWithSimpleMethod($excelFilePath, $csvFilePath) {
        try {
            // For simple conversion, we'll create a basic CSV with file info
            // This is a fallback when PhpSpreadsheet is not available
            
            $csvFile = fopen($csvFilePath, 'w');
            
            // Write header indicating this is a converted file
            fputcsv($csvFile, ['Note', 'This file was converted from Excel format']);
            fputcsv($csvFile, ['Original File', basename($excelFilePath)]);
            fputcsv($csvFile, ['Conversion Date', date('Y-m-d H:i:s')]);
            fputcsv($csvFile, []);
            fputcsv($csvFile, ['Message', 'Please install PhpSpreadsheet library for full Excel conversion support']);
            
            fclose($csvFile);
            
            return [
                'success' => true,
                'message' => 'Basic CSV created (install PhpSpreadsheet for full conversion)',
                'csv_path' => $csvFilePath,
                'partial' => true
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Simple conversion failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if Excel conversion is supported
     */
    public static function isConversionSupported() {
        return class_exists('PhpOffice\PhpSpreadsheet\IOFactory');
    }
    
    /**
     * Get Excel file info
     */
    public static function getExcelInfo($excelFilePath) {
        try {
            if (!file_exists($excelFilePath)) {
                return null;
            }
            
            $info = [
                'file_size' => filesize($excelFilePath),
                'file_type' => strtolower(pathinfo($excelFilePath, PATHINFO_EXTENSION)),
                'conversion_supported' => self::isConversionSupported()
            ];
            
            if (self::isConversionSupported()) {
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFilePath);
                    $worksheet = $spreadsheet->getActiveSheet();
                    
                    $info['sheet_count'] = $spreadsheet->getSheetCount();
                    $info['active_sheet'] = $worksheet->getTitle();
                    $info['row_count'] = $worksheet->getHighestRow();
                    $info['column_count'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
                    
                } catch (Exception $e) {
                    $info['error'] = 'Could not read Excel file details';
                }
            }
            
            return $info;
            
        } catch (Exception $e) {
            error_log("Excel info error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Auto-convert Excel files during upload
     */
    public static function autoConvertOnUpload($uploadedFile, $projectId, $userId) {
        try {
            $originalPath = $uploadedFile['file_path'];
            $extension = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
            
            if (!in_array($extension, ['xls', 'xlsx'])) {
                return [
                    'success' => false,
                    'message' => 'Not an Excel file'
                ];
            }
            
            // Generate CSV filename
            $csvFilename = pathinfo($uploadedFile['original_filename'], PATHINFO_FILENAME) . '.csv';
            $csvPath = UPLOAD_PATH . generateUniqueFilename($csvFilename);
            
            // Convert to CSV
            $result = self::convertToCSV($originalPath, $csvPath);
            
            if ($result['success']) {
                // Create CSV file record in database
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "INSERT INTO files (project_id, filename, original_filename, file_path, file_type, file_size, mime_type, uploaded_by, description, tags) 
                          VALUES (:project_id, :filename, :original_filename, :file_path, :file_type, :file_size, :mime_type, :uploaded_by, :description, :tags)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':project_id', $projectId);
                $stmt->bindParam(':filename', basename($csvPath));
                $stmt->bindParam(':original_filename', $csvFilename);
                $stmt->bindParam(':file_path', $csvPath);
                $stmt->bindParam(':file_type', 'csv');
                $stmt->bindParam(':file_size', filesize($csvPath));
                $stmt->bindParam(':mime_type', 'text/csv');
                $stmt->bindParam(':uploaded_by', $userId);
                $stmt->bindParam(':description', 'Auto-converted from ' . $uploadedFile['original_filename']);
                $stmt->bindParam(':tags', json_encode(['converted', 'excel', 'csv']));
                
                if ($stmt->execute()) {
                    $csvFileId = $db->lastInsertId();
                    
                    // Create initial version for CSV
                    $versionPath = VERSION_STORAGE_PATH . basename($csvPath);
                    copy($csvPath, $versionPath);
                    
                    $query = "INSERT INTO file_versions (file_id, version_number, file_path, file_size, changes_description, created_by, checksum) 
                              VALUES (:file_id, 1, :file_path, :file_size, 'Auto-converted from Excel', :created_by, :checksum)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':file_id', $csvFileId);
                    $stmt->bindParam(':file_path', $versionPath);
                    $stmt->bindParam(':file_size', filesize($csvPath));
                    $stmt->bindParam(':created_by', $userId);
                    $stmt->bindParam(':checksum', hash_file('sha256', $csvPath));
                    $stmt->execute();
                    
                    return [
                        'success' => true,
                        'message' => 'Excel file converted to CSV automatically',
                        'csv_file_id' => $csvFileId,
                        'csv_filename' => $csvFilename
                    ];
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Auto-convert error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Auto-conversion failed: ' . $e->getMessage()
            ];
        }
    }
}
?>