<?php
// Enhanced download.php with comprehensive fixes
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);
ini_set('error_log', 'download_errors.log');

// Database configuration
$host = 'localhost';
$dbname = 'dataset_platform';
$username = 'root';
$password = '1212';

// Custom error handler
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    error_log("[$timestamp] $message $contextStr\n", 3, 'download_errors.log');
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        logError("Download attempt without login");
        header('Location: login.php');
        exit;
    }

    $datasetId = (int)($_GET['id'] ?? 0);

    if (!$datasetId) {
        logError("Invalid dataset ID provided", ['id' => $_GET['id'] ?? 'missing']);
        header('Location: index.php');
        exit;
    }

    // Database connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);

    // Start transaction
    $pdo->beginTransaction();

    // Get dataset details
    $stmt = $pdo->prepare("SELECT * FROM datasets WHERE id = ? AND is_active = 1");
    $stmt->execute([$datasetId]);
    $dataset = $stmt->fetch();

    if (!$dataset) {
        throw new Exception("Dataset not found");
    }

    // Check if file exists
    if (!file_exists($dataset['file_path'])) {
        throw new Exception("File not found");
    }

    // Update download count
    $stmt = $pdo->prepare("UPDATE datasets SET download_count = download_count + 1 WHERE id = ?");
    $stmt->execute([$datasetId]);

    // Log download
    $stmt = $pdo->prepare("INSERT INTO downloads (dataset_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $datasetId,
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    $pdo->commit();

    // Prepare file download
    $fileExtension = strtolower(pathinfo($dataset['filename'], PATHINFO_EXTENSION));
    $mimeTypes = [
        'csv' => 'text/csv',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
        'json' => 'application/json',
        'txt' => 'text/plain',
        'pdf' => 'application/pdf',
        'zip' => 'application/zip'
    ];

    $mimeType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $dataset['filename'] . '"');
    header('Content-Length: ' . filesize($dataset['file_path']));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($dataset['file_path']);
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = 'Download failed: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>
