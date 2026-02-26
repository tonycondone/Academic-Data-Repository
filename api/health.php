<?php
// api/health.php - Health check endpoint
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

$response = [
    'status' => 'healthy',
    'timestamp' => time(),
    'version' => APP_VERSION,
    'php_version' => PHP_VERSION,
    'database' => 'unknown'
];

try {
    $pdo = SupabaseService::getConnection();
    if ($pdo) {
        $response['database'] = 'connected';
    }
} catch (Exception $e) {
    $response['database'] = 'error';
    $response['status'] = 'unhealthy';
    $response['error'] = $e->getMessage();
    http_response_code(503);
}

echo json_encode($response);
