<?php
// diagnostic.php - Database Connection Diagnostic Tool

// Security check: Only allow in development or if explicitly enabled
// For Vercel, we can assume it's safe if the user is accessing it, but ideally we should protect it.
// For now, we'll just show masked credentials.

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$results = [];

// 1. Check PHP Extensions
$results['extensions'] = [
    'pdo' => extension_loaded('pdo'),
    'pdo_pgsql' => extension_loaded('pdo_pgsql'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
];

// 2. Check Environment Variables
$envVars = [
    'DATABASE_URL',
    'POSTGRES_URL',
    'POSTGRES_PRISMA_URL',
    'POSTGRES_URL_NON_POOLING',
    'SUPABASE_DB_URL',
    'SUPABASE_DB_CONNECTION_STRING',
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASS',
    'DB_DRIVER',
    'DB_PORT',
    'DB_SSLMODE',
    'POSTGRES_HOST',
    'POSTGRES_USER',
    'POSTGRES_PASSWORD',
    'POSTGRES_DATABASE'
];

$results['env'] = [];
foreach ($envVars as $var) {
    $val = getenv($var);
    if ($val) {
        if (strpos($var, 'PASS') !== false || strpos($var, 'KEY') !== false || strpos($var, 'SECRET') !== false || strpos($var, 'URL') !== false || strpos($var, 'STRING') !== false) {
             // Mask sensitive info
             $len = strlen($val);
             $visible = $len > 10 ? substr($val, 0, 7) . '...' : '***';
             $results['env'][$var] = "Set (Length: $len, Starts with: $visible)";
        } else {
            $results['env'][$var] = $val;
        }
    } else {
        $results['env'][$var] = 'Not Set';
    }
}

// 3. Test Connection Logic
$db = new Database();
$connectionStatus = "Pending";
$connectionError = null;
$parsedConfig = [];

try {
    // We access the private method by reflection or just try to connect
    // Since we can't reflect easily on private without deeper hacks, we will just try to connect
    // and catch the error to analyze.
    
    // Let's try to simulate the parsing logic here to show what it resolves to
    $dsn = '';
    $user = '';
    
    // Copy-paste logic from Database::getConnection for diagnostic display
    
    $url = getenv('DATABASE_URL') 
        ?: getenv('POSTGRES_URL') 
        ?: getenv('POSTGRES_PRISMA_URL') 
        ?: getenv('SUPABASE_DB_URL') 
        ?: getenv('SUPABASE_DB_CONNECTION_STRING');
        
    if ($url) {
        $parts = parse_url($url);
        $parsedConfig['source'] = 'URL Environment Variable';
        $parsedConfig['host'] = $parts['host'] ?? 'Not found';
        $parsedConfig['port'] = $parts['port'] ?? 'Default';
        $parsedConfig['user'] = $parts['user'] ?? 'Not found';
        $parsedConfig['pass'] = isset($parts['pass']) ? '****' : 'Not found';
        $parsedConfig['path'] = $parts['path'] ?? 'Not found';
        $parsedConfig['scheme'] = $parts['scheme'] ?? 'Not found';
    } else {
        $parsedConfig['source'] = 'Individual Environment Variables';
        $parsedConfig['host'] = getenv('DB_HOST');
        $parsedConfig['user'] = getenv('DB_USER');
    }

    $pdo = $db->getConnection();
    $connectionStatus = "Success";
    $attributes = [
        "Server Info" => $pdo->getAttribute(PDO::ATTR_SERVER_INFO),
        "Driver Name" => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
        "Client Version" => $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION)
    ];
} catch (PDOException $e) {
    $connectionStatus = "Failed";
    $connectionError = $e->getMessage();
    $code = $e->getCode();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Diagnostic</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .card { margin-bottom: 20px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
        .status-success { color: green; font-weight: bold; }
        .status-failed { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Diagnostic Tool</h1>
        
        <div class="card">
            <div class="card-header">Connection Status</div>
            <div class="card-body">
                <p>Status: <span class="<?php echo $connectionStatus === 'Success' ? 'status-success' : 'status-failed'; ?>"><?php echo $connectionStatus; ?></span></p>
                <?php if ($connectionError): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo htmlspecialchars($connectionError); ?>
                    </div>
                    <p><strong>Tip:</strong> "Tenant or user not found" usually means the User or Database name in your connection string is incorrect. For Supabase transaction poolers (port 6543), verify if you need to use <code>user.project_ref</code> format.</p>
                <?php endif; ?>
                <?php if (isset($attributes)): ?>
                    <h5>Connection Attributes:</h5>
                    <ul>
                        <?php foreach ($attributes as $k => $v): ?>
                            <li><?php echo htmlspecialchars($k); ?>: <?php echo htmlspecialchars($v); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Parsed Configuration</div>
            <div class="card-body">
                <table class="table">
                    <?php foreach ($parsedConfig as $key => $val): ?>
                    <tr>
                        <th><?php echo htmlspecialchars($key); ?></th>
                        <td><?php echo htmlspecialchars($val); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Environment Variables</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Variable</th>
                            <th>Value Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['env'] as $key => $val): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key); ?></td>
                            <td><code><?php echo htmlspecialchars($val); ?></code></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">PHP Extensions</div>
            <div class="card-body">
                <ul>
                    <?php foreach ($results['extensions'] as $ext => $loaded): ?>
                    <li><?php echo $ext; ?>: <?php echo $loaded ? '<span class="text-success">Loaded</span>' : '<span class="text-danger">Not Loaded</span>'; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="index.php" class="btn btn-primary">Back to Home</a>
        </div>
    </div>
</body>
</html>
