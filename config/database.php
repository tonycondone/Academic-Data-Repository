<?php
class Database {
    private ?PDO $pdo = null;

    public function getConnection(): PDO {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = defined('DB_DSN') ? DB_DSN : '';
        $user = defined('DB_USER') ? DB_USER : '';
        $pass = defined('DB_PASS') ? DB_PASS : '';

        // Auto-configure from environment for Vercel/Supabase deployment
        if (empty($dsn) || empty($user)) {
            $envUrl = getenv('DATABASE_URL')
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
            throw new PDOException('Database not configured');
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new PDO($dsn, $user, $pass ?? '', $options);
        return $this->pdo;
    }
}
