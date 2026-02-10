<?php
// Load .env or .env.local if environment variables are missing (Local Development)
if (!getenv('POSTGRES_HOST') && !getenv('DATABASE_URL')) {
    $envFiles = [__DIR__ . '/../.env.local', __DIR__ . '/../.env'];
    foreach ($envFiles as $file) {
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    $value = trim($value, '"\'');
                    if (!getenv($key)) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                    }
                }
            }
            break; // Prefer .env.local if found
        }
    }
}

class Database {
    private ?PDO $pdo = null;

    public function getConnection(): PDO {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = defined('DB_DSN') ? DB_DSN : '';
        $user = defined('DB_USER') ? DB_USER : '';
        $pass = defined('DB_PASS') ? DB_PASS : '';

        if (empty($dsn) || empty($user)) {
            // Check for standard Vercel Postgres / Supabase variables
            $envUrl = getenv('DATABASE_URL') 
                ?: getenv('POSTGRES_URL') 
                ?: getenv('POSTGRES_PRISMA_URL') 
                ?: getenv('SUPABASE_DB_URL') 
                ?: getenv('SUPABASE_DB_CONNECTION_STRING') 
                ?: '';
            
            if (!empty($envUrl)) {
                $parts = parse_url($envUrl);
                if ($parts && isset($parts['scheme'], $parts['host'], $parts['path'])) {
                    $scheme = strtolower($parts['scheme']);
                    $host = $parts['host'];
                    $port = $parts['port'] ?? ($scheme === 'postgres' ? 5432 : 3306);
                    $dbname = ltrim($parts['path'], '/');
                    $query = [];
                    if (!empty($parts['query'])) {
                        parse_str($parts['query'], $query);
                    }
                    $user = $user ?: ($parts['user'] ?? '');
                    $pass = $pass ?: ($parts['pass'] ?? '');
                    if ($scheme === 'postgres' || $scheme === 'postgresql') {
                        $sslmode = $query['sslmode'] ?? 'require';
                        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
                    } elseif ($scheme === 'mysql') {
                        $charset = $query['charset'] ?? 'utf8mb4';
                        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                    }
                }
            }
        }

        if (empty($dsn) || empty($user)) {
            $driver = strtolower(getenv('DB_DRIVER') ?: 'postgres');
            
            // Support Vercel standard POSTGRES_* variables
            $host = getenv('DB_HOST') ?: getenv('POSTGRES_HOST') ?: (defined('DB_HOST') ? DB_HOST : '');
            $port = getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : ($driver === 'postgres' ? '5432' : '3306'));
            $dbname = getenv('DB_NAME') ?: getenv('POSTGRES_DATABASE') ?: (defined('DB_NAME') ? DB_NAME : '');
            $user = $user ?: (getenv('DB_USER') ?: getenv('POSTGRES_USER') ?: (defined('DB_USER') ? DB_USER : ''));
            $pass = $pass ?: (getenv('DB_PASS') ?: getenv('POSTGRES_PASSWORD') ?: (defined('DB_PASS') ? DB_PASS : ''));
            
            if ($driver === 'postgres' && $host && $dbname && $user) {
                $sslmode = getenv('DB_SSLMODE') ?: 'require';
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
            } elseif ($driver === 'mysql' && $host && $dbname && $user) {
                $charset = getenv('DB_CHARSET') ?: 'utf8mb4';
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            }
        }

        if (empty($dsn) || empty($user)) {
            throw new PDOException('Database not configured');
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Enable emulated prepares for Supabase Transaction Poolers (port 6543) support
            PDO::ATTR_EMULATE_PREPARES => true, 
        ];

        $this->pdo = new PDO($dsn, $user, $pass ?? '', $options);
        return $this->pdo;
    }
}
