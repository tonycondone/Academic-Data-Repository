<?php
session_start();

// Enforce authentication: only logged-in users or admins can download datasets
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get dataset ID
$datasetId = (int)($_GET['id'] ?? 0);

if (!$datasetId) {
    header('Location: index.php');
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'dataset_platform';
$username = 'root';
$password = '1212';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get dataset details
    $stmt = $pdo->prepare("SELECT * FROM datasets WHERE id = ?");
    $stmt->execute([$datasetId]);
    $dataset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dataset) {
        header('Location: index.php');
        exit;
    }
    
    // Check if file exists
    if (!file_exists($dataset['file_path'])) {
        header('Location: index.php');
        exit;
    }
    
    // Update download count
    $stmt = $pdo->prepare("UPDATE datasets SET download_count = download_count + 1 WHERE id = ?");
    $stmt->execute([$datasetId]);
    
    // Get file extension to determine MIME type
    $fileExtension = strtolower(pathinfo($dataset['filename'], PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'csv' => 'text/csv',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
        'json' => 'application/json',
        'txt' => 'text/plain',
        'pdf' => 'application/pdf'
    ];
    
    $mimeType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';
    
    // Set headers for file download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $dataset['filename'] . '"');
    header('Content-Length: ' . $dataset['file_size']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output file content
    readfile($dataset['file_path']);
    exit;
    
} catch(PDOException $e) {
    header('Location: index.php');
    exit;
}
?>