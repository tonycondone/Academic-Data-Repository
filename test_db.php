<?php
// require_once __DIR__ . '/config/config.php';

echo "Testing PostgreSQL connection...\n";

// Manual .env loading since composer dependencies might be missing
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '5432';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: 'postgres';
$dbname = getenv('DB_NAME') ?: 'dataset_platform';

echo "Host: $host\n";
echo "Port: $port\n";
echo "User: $user\n";
echo "DbName: $dbname\n";

// Try connecting to the default 'postgres' database first to check credentials and create our db if needed
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=postgres";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✅ Connection to default 'postgres' database successful!\n";

    // Check if our database exists
    $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '$dbname'");
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        echo "Database '$dbname' does not exist. Creating...\n";
        try {
            $pdo->exec("CREATE DATABASE \"$dbname\"");
            echo "✅ Database '$dbname' created successfully.\n";
        } catch (PDOException $e) {
            echo "❌ Failed to create database: " . $e->getMessage() . "\n";
            exit(1);
        }
    } else {
        echo "ℹ️ Database '$dbname' already exists.\n";
    }

} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "Please check your PostgreSQL credentials in .env\n";
    exit(1);
}

// Now connect to our specific database and run schema
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✅ Connection to '$dbname' successful!\n";

    // Check if tables exist
    $stmt = $pdo->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public'");
    $tableCount = $stmt->fetchColumn();
    echo "Current table count: $tableCount\n";

    if ($tableCount == 0) {
        echo "Importing schema from database/schema_postgres.sql...\n";
        $sql = file_get_contents(__DIR__ . '/database/schema_postgres.sql');
        $pdo->exec($sql);
        echo "✅ Schema imported successfully!\n";
    } else {
        echo "ℹ️ Tables already exist. Skipping schema import.\n";
    }

} catch (PDOException $e) {
    echo "❌ Failed to connect to '$dbname': " . $e->getMessage() . "\n";
    exit(1);
}
