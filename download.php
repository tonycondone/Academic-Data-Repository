<?php
session_start();
require_once __DIR__ . '/config/config.php';
$db = new Database();

if (isset($_GET['action']) && $_GET['action'] === 'get_download_count') {
    // AJAX endpoint to get current download count
    $datasetId = (int)($_GET['id'] ?? 0);
    if (!$datasetId) {
        echo json_encode(['success' => false, 'message' => 'Invalid dataset ID']);
        exit;
    }

    try {
        $pdo = $db->getConnection();

        $stmt = $pdo->prepare("SELECT download_count FROM datasets WHERE id = ?");
        $stmt->execute([$datasetId]);
        $count = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'download_count' => (int)$count]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// Check if user is logged in
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

try {
    $pdo = $db->getConnection();
    
    // Get dataset details
    $stmt = $pdo->prepare("SELECT * FROM datasets WHERE id = ? AND is_active = 1");
    $stmt->execute([$datasetId]);
    $dataset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dataset) {
        header('Location: index.php');
        exit;
    }
    
    // Check if file exists
    if (!file_exists($dataset['file_path'])) {
        $_SESSION['error'] = 'File not found on server.';
        header('Location: index.php');
        exit;
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
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for file download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $dataset['filename'] . '"');
    header('Content-Length: ' . filesize($dataset['file_path']));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output file content
    readfile($dataset['file_path']);
    exit;
    
} catch(PDOException $e) {
    error_log("Download error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while downloading the file.';
    header('Location: index.php');
    exit;
}
?>
