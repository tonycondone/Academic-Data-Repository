<?php
// Database diagnostic script
require_once __DIR__ . '/config/config.php';
$db = new Database();

echo "<h1>Dataset Platform Diagnostic Report</h1>\n";

try {
    $pdo = $db->getConnection();
    
    echo "<h2>Database Connection</h2>\n";
    echo "<p>✅ Database connection successful</p>\n";
    
    // Check table structure
    echo "<h2>Table Structure Check</h2>\n";
    $stmt = $pdo->query("DESCRIBE datasets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasDownloadCount = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'download_count') {
            $hasDownloadCount = true;
            echo "<p>✅ download_count column exists: {$column['Type']}</p>\n";
            break;
        }
    }
    
    if (!$hasDownloadCount) {
        echo "<p>❌ download_count column missing</p>\n";
    }
    
    // Check sample data
    echo "<h2>Sample Data Check</h2>\n";
    $stmt = $pdo->query("SELECT id, title, download_count FROM datasets LIMIT 5");
    $datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($datasets as $dataset) {
        echo "<p>Dataset {$dataset['id']}: {$dataset['title']} - Downloads: {$dataset['download_count']}</p>\n";
    }
    
    // Test update
    echo "<h2>Update Test</h2>\n";
    $stmt = $pdo->prepare("UPDATE datasets SET download_count = download_count + 1 WHERE id = 1");
    $result = $stmt->execute();
    
    if ($result) {
        echo "<p>✅ Update query executed successfully</p>\n";
        
        // Check updated count
        $count = $pdo->query("SELECT download_count FROM datasets WHERE id = 1")->fetchColumn();
        echo "<p>Updated download count for dataset 1: {$count}</p>\n";
    } else {
        echo "<p>❌ Update query failed</p>\n";
    }
    
    // Check file paths
    echo "<h2>File Path Validation</h2>\n";
    $stmt = $pdo->query("SELECT id, title, file_path, filename FROM datasets");
    $datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($datasets as $dataset) {
        $exists = file_exists($dataset['file_path']) ? "✅" : "❌";
        echo "<p>{$exists} {$dataset['title']}: {$dataset['file_path']}</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: {$e->getMessage()}</p>\n";
}
?>
